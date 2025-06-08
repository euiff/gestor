<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cliente;
use App\Models\Template;
use App\Models\Plano;
use App\Models\User;
use App\Models\CompanyDetail;
use App\Models\ScheduleSetting;
use App\Models\Pagamento;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\SendMessageController;
use Illuminate\Http\Request;
use MercadoPago\Client\Common\RequestOptions;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\MercadoPagoConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class VerificarClientesVencidos extends Command
{
    protected $signature = 'clientes:verificar-vencidos';
    protected $description = 'Verifica os clientes vencidos e envia notificações de cobrança';
    
    const QPANEL_SYNC_CACHE_KEY = 'last_qpanel_sync';
    protected $templatesCache = [];
    protected $companyDetailsCache = [];
    protected $planosCache = [];
    protected $ownersCache = [];
    protected $adminUser = null;

    public function handle()
    {
        $startTime = microtime(true);
        Log::info('Iniciando verificação de clientes vencidos');
        
        // 1. Sincronização inicial com QPanel (em lotes)
        $this->sincronizarClientesQPanel();
        
        // 2. Processar configurações agendadas
        $this->processarConfiguracoesAgendadas();
        
        $executionTime = round(microtime(true) - $startTime, 2);
        Log::info("Verificação concluída em {$executionTime} segundos");
        $this->info("Verificação concluída em {$executionTime} segundos");
    }

    protected function processarConfiguracoesAgendadas()
    {
        $hoje = Carbon::now();
        $horaAtual = $hoje->format('H:i');
        
        // Pré-calcular todas as datas necessárias
        $datas = [
            'cobranca_3_dias_atras' => $hoje->copy()->subDays(3)->toDateString(),
            'cobranca_5_dias_atras' => $hoje->copy()->subDays(5)->toDateString(),
            'cobranca_hoje' => $hoje->toDateString(),
            'cobranca_3_dias_futuro' => $hoje->copy()->addDays(3)->toDateString()
        ];

        // Carregar todas as configurações de uma vez com relacionamento
        $settings = ScheduleSetting::with('user')->get();
        Log::info('Total de configurações agendadas encontradas: '.$settings->count());

        if ($settings->isEmpty()) {
            Log::info('Nenhuma configuração agendada encontrada');
            return;
        }

        // Agrupar configurações por horário de execução
        $settingsPorHorario = $settings->groupBy(function($item) {
            return Carbon::createFromFormat('H:i:s', $item->execution_time)->format('H:i');
        });

        // Processar apenas os horários que correspondem ao atual
        if (!$settingsPorHorario->has($horaAtual)) {
            Log::info("Nenhuma configuração para executar às {$horaAtual}");
            return;
        }

        // Processar em lotes por finalidade
        foreach ($settingsPorHorario->get($horaAtual) as $setting) {
            if (empty($datas[$setting->finalidade])) {
                Log::warning('Finalidade não reconhecida', ['finalidade' => $setting->finalidade]);
                continue;
            }

            Log::info('Processando configuração', [
                'setting_id' => $setting->id,
                'user_id' => $setting->user_id,
                'finalidade' => $setting->finalidade
            ]);

            // Buscar clientes em uma única consulta otimizada
            $clientes = Cliente::where('user_id', $setting->user_id)
                ->whereDate('vencimento', $datas[$setting->finalidade])
                ->get();

            if ($clientes->isEmpty()) {
                Log::debug('Nenhum cliente encontrado para processamento', [
                    'user_id' => $setting->user_id,
                    'finalidade' => $setting->finalidade
                ]);
                continue;
            }

            $this->processarClientes($clientes, $setting->finalidade, $setting);
            $this->info('Verificação concluída para user_id: ' . $setting->user_id . ' com finalidade: ' . $setting->finalidade);
        }
    }

    protected function processarClientes($clientes, $finalidade, $setting)
    {
        Log::info('Processando ' . $clientes->count() . ' clientes para finalidade: ' . $finalidade);
    
        // Dividir clientes que precisam de sincronização com QPanel
        $clientesParaSincronizar = $clientes->filter(function($cliente) {
            return $cliente->sync_qpanel == 1 && !empty($cliente->iptv_nome);
        });

        // Sincronizar em lote se necessário
        if ($clientesParaSincronizar->isNotEmpty()) {
            $this->sincronizarClientesEmLote($clientesParaSincronizar);
        }
    
        // Processar notificações e cobranças
        foreach ($clientes as $cliente) {
            try {
                $this->processarCobrancaCliente($cliente, $finalidade, $setting);
            } catch (\Exception $e) {
                Log::error('Erro ao processar cliente', [
                    'cliente_id' => $cliente->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
    }
    
    protected function processarCobrancaCliente($cliente, $finalidade, $setting)
    {
        $company = $this->getCompanyDetailWithCache($cliente->user_id);
        
        // Verificar se já existe cobrança pendente para este cliente
        $cobrancaPendente = Pagamento::where('cliente_id', $cliente->id)
            ->where('status', 'pending')
            ->first();

        // Se existir cobrança pendente, cancelar antes de criar nova
        if ($cobrancaPendente) {
            $this->cancelarCobrancaExistente($cobrancaPendente, $company);
        }

        // Criar nova cobrança
        $resultadoCobranca = $this->criarNovaCobranca($cliente, $company);
        
        if (!$resultadoCobranca['sucesso']) {
            throw new \Exception('Falha ao criar cobrança: ' . $resultadoCobranca['mensagem']);
        }

        // Enviar notificação
        $enviado = $this->notifyClient($cliente, $finalidade, $setting, $resultadoCobranca['dados']);
        
        Log::debug('Resultado notificação', [
            'cliente_id' => $cliente->id,
            'status' => $enviado ? 'enviado' : 'falha'
        ]);

        return $enviado;
    }
    
    protected function cancelarCobrancaExistente($cobranca, $company)
    {
        try {
            if (!$company->not_gateway && $cobranca->mercado_pago_id) {
                $this->cancelarCobrancaMercadoPago($company, $cobranca->mercado_pago_id);
            }
            $cobranca->delete();
            Log::info('Cobrança pendente removida', ['cobranca_id' => $cobranca->id]);
        } catch (\Exception $e) {
            Log::error('Erro ao cancelar cobrança existente', [
                'cobranca_id' => $cobranca->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    private function processarPagamento($cliente, $plano, $company)
    {
        $adminUser = $this->getAdminUser();
        $adminCompanyDetail = CompanyDetail::where('user_id', $adminUser->id)->first();

        if (!$adminCompanyDetail) {
            throw new \Exception('Detalhes da empresa do admin não encontrados');
        }

        MercadoPagoConfig::setAccessToken($company->access_token);
        MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::LOCAL);
    
        $paymentClient = new PaymentClient();
        $valorPlano = (float) $plano->preco;
        
        if ($valorPlano <= 0) {
            throw new \Exception('Valor do plano inválido');
        }

        $preference = [
            'transaction_amount' => $valorPlano,
            'description' => $plano->nome,
            'payment_method_id' => 'pix',
            'notification_url' => $adminCompanyDetail->notification_url,
            'payer' => [
                'email' => 'cliente@cliente.com',
                'first_name' => $cliente->nome,
                'identification' => [
                    'type' => 'CPF',
                    'number' => '12345678909'
                ]
            ]
        ];

        $requestOptions = new RequestOptions();
        $requestOptions->setCustomHeaders(["X-Idempotency-Key: " . uniqid()]);
    
        try {
            $response = $paymentClient->create($preference, $requestOptions);
            
            $paymentLink = $response->point_of_interaction->transaction_data->ticket_url;
            $payloadPix = $response->point_of_interaction->transaction_data->qr_code;

            Pagamento::create([
                'cliente_id' => $cliente->id,
                'user_id' => $cliente->user_id,
                'mercado_pago_id' => $response->id,
                'valor' => $valorPlano,
                'status' => 'pending',
                'plano_id' => $cliente->plano_id,
                'isAnual' => false,
            ]);

            return [
                'payment_link' => $paymentLink,
                'payload_pix' => $payloadPix,
            ];
        } catch (MPApiException $e) {
            throw new \Exception('Erro na API do MercadoPago: ' . $e->getMessage());
        } catch (\Exception $e) {
            throw new \Exception('Erro ao criar pagamento: ' . $e->getMessage());
        }
    }

    protected function criarNovaCobranca($cliente, $company)
    {
        $plano = $this->getPlanoWithCache($cliente->plano_id);
        
        if (!$plano) {
            return [
                'sucesso' => false,
                'mensagem' => 'Plano não encontrado'
            ];
        }

        try {
            if ($company->not_gateway) {
                // Cobrança manual (PIX)
                Pagamento::create([
                    'cliente_id' => $cliente->id,
                    'user_id' => $cliente->user_id,
                    'mercado_pago_id' => uniqid(),
                    'valor' => $plano->preco,
                    'status' => 'pending',
                    'plano_id' => $cliente->plano_id,
                    'isAnual' => false,
                ]);

                return [
                    'sucesso' => true,
                    'mensagem' => 'Cobrança manual criada',
                    'dados' => [
                        'payment_link' => null,
                        'pix_manual' => $company->pix_manual ?? 'Chave PIX não configurada'
                    ]
                ];
            } else {
                // Cobrança via Mercado Pago
                $pagamentoData = $this->processarPagamento($cliente, $plano, $company);
                
                if (!$pagamentoData['payment_link']) {
                    return [
                        'sucesso' => false,
                        'mensagem' => 'Falha ao criar pagamento no Mercado Pago'
                    ];
                }

                return [
                    'sucesso' => true,
                    'mensagem' => 'Cobrança MP criada',
                    'dados' => $pagamentoData
                ];
            }
        } catch (\Exception $e) {
            Log::error('Erro ao criar cobrança', [
                'cliente_id' => $cliente->id,
                'error' => $e->getMessage()
            ]);
            return [
                'sucesso' => false,
                'mensagem' => $e->getMessage()
            ];
        }
    }
    
    protected function sincronizarClientesEmLote($clientes)
    {
        $atualizados = 0;
        $erros = 0;
        
        // Agrupar por user_id para otimizar busca de dados de usuário
        $clientesPorUsuario = $clientes->groupBy('user_id');
        
        foreach ($clientesPorUsuario as $userId => $clientesGrupo) {
            // Carregar dados do usuário uma vez para o grupo
            $user = User::find($userId);
            
            foreach ($clientesGrupo as $cliente) {
                try {
                    $dadosQPanel = $this->buscarDadosQPanel($cliente->iptv_nome);
                    
                    if ($dadosQPanel) {
                        $this->aplicarDadosQPanel($cliente, $dadosQPanel, $user);
                        $atualizados++;
                    } else {
                        $erros++;
                    }
                } catch (\Exception $e) {
                    $erros++;
                    Log::error('Erro ao sincronizar cliente com QPanel', [
                        'cliente_id' => $cliente->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }
        }
        
        Log::info('Sincronização em lote concluída', [
            'total_clientes' => $clientes->count(),
            'atualizados' => $atualizados,
            'erros' => $erros
        ]);
    }
    
    protected function aplicarDadosQPanel($cliente, $dadosQPanel, $user)
    {
        try {
            if (empty($dadosQPanel['expires_at_tz'])) {
                Log::error('Data de expiração inválida do QPanel', [
                    'cliente_id' => $cliente->id,
                    'expires_at_tz' => $dadosQPanel['expires_at_tz'] ?? null
                ]);
                return false;
            }
    
            $vencimento = Carbon::parse($dadosQPanel['expires_at_tz'])->format('Y-m-d');
            $atualizacoes = [];
            
            if ($cliente->vencimento != $vencimento) {
                $atualizacoes['vencimento'] = $vencimento;
            }
    
            if (!empty($dadosQPanel['package_id'])) {
                $atualizacoes['plano_qpanel'] = $dadosQPanel['package_id'];
            }
    
            if (!empty($dadosQPanel['password'])) {
                $atualizacoes['iptv_senha'] = $dadosQPanel['password'];
            }
    
            if (!empty($atualizacoes)) {
                $cliente->update($atualizacoes);
            }
    
            if (!empty($dadosQPanel['user_id']) && $user->id_qpanel != $dadosQPanel['user_id']) {
                $user->id_qpanel = $dadosQPanel['user_id'];
                $user->save();
            }
    
            return true;
        } catch (\Exception $e) {
            Log::error('Falha ao aplicar dados do QPanel', [
                'cliente_id' => $cliente->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    private function notifyClient($cliente, $finalidade, $setting, $dadosCobranca)
    {
        $template = $this->getTemplateForFinalidade($finalidade, $cliente->user_id);
        
        if (!$template) {
            Log::error('Template não encontrado', [
                'finalidade' => $finalidade,
                'user_id' => $cliente->user_id
            ]);
            return false;
        }
    
        // Log para depuração do template encontrado
        Log::info('Template encontrado', [
            'id' => $template->id,
            'tipo_mensagem' => $template->tipo_mensagem,
            'possui_imagem' => !empty($template->imagem)
        ]);
    
        $company = $this->getCompanyDetailWithCache($cliente->user_id);
        $plano = $this->getPlanoWithCache($cliente->plano_id);
    
        $dadosTemplate = $this->prepararDadosTemplate($cliente, $plano, $company, $finalidade, $dadosCobranca);
        $mensagem = $this->substituirPlaceholders($template->conteudo, $dadosTemplate);
    
        // Verifica se é template com imagem e se tem imagem definida
        $imagem = null;
        if ($template->tipo_mensagem === 'texto_com_imagem' && !empty($template->imagem)) {
            $imagem = $template->imagem;
            Log::info('Template configurado com imagem', [
                'caminho_imagem' => $imagem
            ]);
        }
    
        return $this->sendMessage($cliente->whatsapp, $mensagem, $cliente->user_id, $imagem);
    }

    private function prepararDadosTemplate($cliente, $plano, $company, $finalidade, $dadosCobranca)
    {
        $dados = [
            '{nome_cliente}' => $cliente->nome,
            '{telefone_cliente}' => $cliente->whatsapp,
            '{notas}' => $cliente->notas ?? 'Sem notas',
            '{vencimento_cliente}' => Carbon::parse($cliente->vencimento)->format('d/m/Y'),
            '{plano_nome}' => $plano ? $plano->nome : 'Plano não encontrado',
            '{plano_valor}' => $plano ? number_format($plano->preco, 2, ',', '.') : '0,00',
            '{data_atual}' => Carbon::now()->format('d/m/Y'),
            '{text_expirate}' => $this->getTextExpirate($cliente->vencimento),
            '{saudacao}' => $this->getSaudacao(),
            '{nome_empresa}' => $company ? $company->company_name : 'Empresa não configurada',
            '{whatsapp_empresa}' => $company ? $company->company_phone : '',
            '{iptv_nome}' => $cliente->iptv_nome ?? '',
            '{iptv_senha}' => $cliente->iptv_senha ?? '',
            '{status_pagamento}' => 'Pendente'
        ];

        // Adicionar dados específicos da cobrança
        if ($company->not_gateway) {
            $dados['{plano_link}'] = "\n\nChave PIX: " . ($dadosCobranca['pix_manual'] ?? $company->pix_manual ?? 'Chave PIX não configurada');
        } else {
            $dados['{plano_link}'] = $dadosCobranca['payment_link'] ?? 'http://linkdopagamento.com';
            $dados['{payload_pix}'] = $dadosCobranca['payload_pix'] ?? '';
        }

        return $dados;
    }

    private function getTemplateForFinalidade($finalidade, $userId)
    {
        $cacheKey = "{$finalidade}_{$userId}";
        
        if (!isset($this->templatesCache[$cacheKey])) {
            $this->templatesCache[$cacheKey] = Template::where('finalidade', $finalidade)
                ->where(function($q) use ($userId) {
                    $q->where('user_id', $userId)->orWhereNull('user_id');
                })
                ->orderBy('user_id', 'desc') // Preferência para templates específicos do usuário
                ->first();
        }
        
        return $this->templatesCache[$cacheKey];
    }

    private function substituirPlaceholders($conteudo, $dados)
    {
        return str_replace(array_keys($dados), array_values($dados), $conteudo);
    }

    private function getSaudacao()
    {
        $hora = Carbon::now()->format('H');
        
        if ($hora >= 6 && $hora < 12) return 'Bom dia';
        if ($hora >= 12 && $hora < 18) return 'Boa tarde';
        return 'Boa noite';
    }

    private function getTextExpirate($vencimento)
    {
        $diasRestantes = Carbon::parse($vencimento)->diffInDays(Carbon::now());
        
        if ($diasRestantes <= 0) return 'Seu pagamento está vencido!';
        if ($diasRestantes <= 3) return 'Seu pagamento está próximo do vencimento!';
        return 'Seu pagamento está em dia.';
    }

    private function sendMessage($phone, $message, $user_id, $image = null)
    {
        try {
            $sendMessageController = new SendMessageController();
            
            $requestData = [
                'phone' => $phone,
                'message' => $message,
                'user_id' => $user_id,
            ];

            if (!empty($image)) {
                $requestData['image'] = $image;
                Log::info('Preparando envio de mensagem com imagem', [
                    'image_path' => $image
                ]);
            }
    
            $request = new Request($requestData);
            $response = $sendMessageController->sendMessageWithoutAuth($request);
            $responseData = json_decode($response->getContent(), true);
    
            // Obter a versão da API configurada para o usuário
            $apiVersion = 'v1'; // padrão
            $companyDetails = \DB::table('company_details')->where('user_id', $user_id)->first();
            if ($companyDetails && isset($companyDetails->api_version)) {
                $apiVersion = $companyDetails->api_version;
            }
    
            Log::info('Resposta do envio de mensagem', [
                'api_version' => $apiVersion,
                'response' => $responseData
            ]);
    
            if ($apiVersion === 'v2') {
                // Lógica de verificação para API v2
                return $responseData['success'] ?? false;
            } else {
                // Lógica de verificação para API v1 (original)
                $status = $responseData['status'] ?? null;
                $messageId = $responseData['messageId'] ?? null;
                
                return ($status === 'PENDING' || $status === '200' || $status === 'SENT' || $messageId);
            }
    
        } catch (\Exception $e) {
            Log::error('Erro ao enviar mensagem', [
                'phone' => $phone,
                'user_id' => $user_id,
                'has_image' => !empty($image),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    private function buscarDadosQPanel($username)
    {
        try {
            // Busca as credenciais do QPanel do admin (user_id = 1)
            $companyDetails = CompanyDetail::where('user_id', 1)->first();
            
            if (!$companyDetails || !$companyDetails->qpanel_api_url || !$companyDetails->qpanel_api_key) {
                Log::error('Credenciais do QPanel não configuradas no sistema');
                return null;
            }
    
            $curl = curl_init();
    
            curl_setopt_array($curl, [
                CURLOPT_URL => $companyDetails->qpanel_api_url . "?username=" . urlencode($username),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $companyDetails->qpanel_api_key,
                    'Accept: application/json'
                ],
            ]);
    
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
    
            if ($httpCode !== 200) {
                Log::error('Falha ao buscar dados do QPanel', [
                    'username' => $username,
                    'http_code' => $httpCode,
                    'response' => $response
                ]);
                return null;
            }
    
            $data = json_decode($response, true);
    
            if (!isset($data['data'])) {
                Log::error('Resposta inválida da API QPanel', [
                    'username' => $username,
                    'response' => $response
                ]);
                return null;
            }
    
            return $data['data'];
        } catch (\Exception $e) {
            Log::error('Erro ao buscar dados do QPanel', [
                'username' => $username,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }
    
    protected function sincronizarClientesQPanel()
    {
        // Verificar se já foi executado hoje
        $lastSyncDate = Cache::get(self::QPANEL_SYNC_CACHE_KEY);
        $today = now()->format('Y-m-d');
        
        if ($lastSyncDate === $today) {
            Log::info('Sincronização com QPanel já foi executada hoje. Pulando...');
            return;
        }

        Log::info('Iniciando sincronização em massa com QPanel');
        
        // Processar em lotes de 100 para evitar sobrecarga de memória
        Cliente::where('sync_qpanel', 1)
            ->whereNotNull('iptv_nome')
            ->chunk(100, function ($clientes) {
                $this->sincronizarClientesEmLote($clientes);
            });
            
        // Armazenar no cache que foi sincronizado hoje
        Cache::put(self::QPANEL_SYNC_CACHE_KEY, $today, now()->addDay());
        
        Log::info('Sincronização com QPanel concluída');
    }

    private function getAdminUser()
    {
        if (!$this->adminUser) {
            $this->adminUser = User::where('role_id', 1)->first();
        }
        return $this->adminUser;
    }

    private function getCompanyDetailWithCache($userId)
    {
        if (!isset($this->companyDetailsCache[$userId])) {
            $this->companyDetailsCache[$userId] = CompanyDetail::where('user_id', $userId)->first();
        }
        
        return $this->companyDetailsCache[$userId];
    }
    
    private function getPlanoWithCache($planoId)
    {
        if (!isset($this->planosCache[$planoId])) {
            $this->planosCache[$planoId] = Plano::find($planoId);
        }
        
        return $this->planosCache[$planoId];
    }
    
    private function getOwnerWithCache($userId)
    {
        if (!isset($this->ownersCache[$userId])) {
            $this->ownersCache[$userId] = User::find($userId);
        }
        
        return $this->ownersCache[$userId];
    }

    protected function removerCobrancasPendentes($cliente, $company)
    {
        $cobrancasPendentes = Pagamento::where('cliente_id', $cliente->id)
            ->where('status', 'pending')
            ->get();
    
        if ($cobrancasPendentes->isNotEmpty()) {
            foreach ($cobrancasPendentes as $cobranca) {
                if (!$company->not_gateway && $cobranca->mercado_pago_id) {
                    $this->cancelarCobrancaMercadoPago($company, $cobranca->mercado_pago_id);
                }
                $cobranca->delete();
            }
        }
    }
    
    protected function cancelarCobrancaMercadoPago($company, $mercadoPagoId)
    {
        try {
            MercadoPagoConfig::setAccessToken($company->access_token);
            $paymentClient = new PaymentClient();
            $paymentClient->cancel($mercadoPagoId);
        } catch (\Exception $e) {
            Log::error('Falha ao cancelar cobrança no MercadoPago', [
                'mercado_pago_id' => $mercadoPagoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}