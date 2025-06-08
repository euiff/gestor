<?php
namespace App\Http\Controllers;

require_once '../vendor/autoload.php';

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Pagamento;
use App\Models\Cliente;
use App\Models\User;
use App\Models\Template;
use App\Http\Controllers\SendMessageController;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Payment\PaymentClient;
use App\Models\CompanyDetail;
use App\Models\PlanoRenovacao;
use App\Models\indicacoes;
use App\Models\Plano;
use Carbon\Carbon;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        $adminUser = User::where('role_id', 1)->firstOrFail();
        $adminUserId = $adminUser->id;

        $body = json_decode(file_get_contents('php://input'), true);

        if (!isset($body['type'])) {
            return response()->json(['status' => 'error', 'message' => 'Tipo de evento não encontrado'], 400);
        }

        $event = $body['type'];
        $paymentId = $body['data']['id'];

        $pagamento = Pagamento::where('mercado_pago_id', $paymentId)->first();

        if ($pagamento) {
            $accessTokenUserId = $this->determineAccessTokenUserId($pagamento, $adminUserId);

            if ($accessTokenUserId) {
                $companyDetail = CompanyDetail::where('user_id', $accessTokenUserId)->first();
                if ($companyDetail) {
                    $this->processEvent($event, $paymentId, $companyDetail->access_token, $pagamento);
                } else {
                    Log::error('Access Token não encontrado para user_id: ' . $accessTokenUserId);
                }
            } else {
                return response()->json(['status' => 'error', 'message' => 'Condições não atendidas'], 400);
            }
        } else {
            Log::warning('Pagamento não encontrado com mercado_pago_id: ' . $paymentId);
        }

        return response()->json(['status' => 'success']);
    }

    private function determineAccessTokenUserId($pagamento, $adminUserId)
    {
        if (is_null($pagamento->cliente_id) && !is_null($pagamento->user_id)) {
            return $adminUserId;
        } elseif (!is_null($pagamento->cliente_id) && !is_null($pagamento->user_id)) {
            return $pagamento->user_id;
        }
        return null;
    }

    private function processEvent($event, $paymentId, $accessToken, $pagamento)
    {
        MercadoPagoConfig::setAccessToken($accessToken);
        MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::LOCAL);

        $client = new PaymentClient();
        if ($event === "payment") {
            $payment = $client->get($paymentId);
            $this->updatePaymentStatus($payment, $pagamento);
        } else {
            Log::warning('Evento não suportado: ' . $event);
        }
    }

    private function updatePaymentStatus($payment, $pagamento)
    {
        $statusAnterior = $pagamento->status;
        $pagamento->status = $payment->status;
        $pagamento->save();

        if ($payment->status === 'approved' && $statusAnterior !== 'approved') {
            $this->processPayment($payment);
        } elseif ($payment->status === 'cancelled' && $pagamento->use_saldo_ganhos > 0) {
            $this->restoreUserSaldoGanhos($pagamento->user_id, $pagamento->use_saldo_ganhos);
            Log::info('Saldo de ganhos restaurado para o usuário: ' . $pagamento->user_id);
        }
    }

    private function restoreUserSaldoGanhos($userId, $valorDesconto)
    {
        $indicacoes = indicacoes::where('user_id', $userId)
                                ->where('status', 'usado')
                                ->orderBy('created_at', 'desc')
                                ->get();

        foreach ($indicacoes as $indicacao) {
            if ($valorDesconto <= 0) {
                break;
            }

            $valorRestaurado = min($indicacao->ganhos_original - $indicacao->ganhos, $valorDesconto);
            $indicacao->ganhos += $valorRestaurado;
            $valorDesconto -= $valorRestaurado;
            $indicacao->status = 'ativo';
            $indicacao->save();
        }
    }

    private function processPayment($payment)
    {
        $paymentId = $payment->id;
        $paymentRecord = Pagamento::where('mercado_pago_id', $paymentId)->first();
    
        if ($paymentRecord) {
            if (is_null($paymentRecord->cliente_id)) {
                $this->renovarPlanoUser($paymentRecord->user_id, $paymentRecord->plano_id, $paymentRecord->isAnual);
                $this->adicionarSaldoIndicacao($paymentRecord->user_id);
    
                if ($paymentRecord->credito_id !== null) {
                    $adminUser = User::where('role_id', 1)->firstOrFail();
                    $this->adicionarCreditosUsuario($paymentId, $adminUser->id);
                }
            } else {
                $novaDataVencimento = $this->renovarPlanoCliente($paymentRecord->cliente_id, $paymentRecord->plano_id);
                // Adiciona processamento do QPanel quando necessário
                $this->processQPanelRenovation($paymentRecord);
                $this->notifyClientAndOwner($paymentRecord, $novaDataVencimento);
            }
        } else {
            $this->notifyClientAndOwner($paymentRecord);
            Log::warning('Pagamento não encontrado: ' . $paymentId);
        }
    }

    private function processQPanelRenovation($paymentRecord)
    {
        $cliente = Cliente::find($paymentRecord->cliente_id);
        if (!$cliente || $cliente->sync_qpanel != 1) {
            return;
        }

        $resultadoRenovacao = $this->renovarNoQPanel($cliente);
        $creditos = $this->obterCreditosPlanoQPanel($cliente->plano_qpanel)['credits'] ?? 1;
        $paymentRecord->qpanel_success = $resultadoRenovacao['success'];
        $paymentRecord->qpanel_message = $resultadoRenovacao['message'];
        $paymentRecord->qpanel_credits = $creditos;
    }

    private function renovarNoQPanel($cliente)
    {
        if ($cliente->sync_qpanel != 1 || empty($cliente->iptv_nome) || empty($cliente->plano_qpanel)) {
            return ['success' => false, 'message' => 'Cliente não configurado para sincronização com QPanel'];
        }
    
        $dono = User::find($cliente->user_id);
        if (!$dono || empty($dono->id_qpanel)) {
            return ['success' => false, 'message' => 'Dono do cliente não configurado no QPanel'];
        }
    
        try {
            // Busca as credenciais do QPanel do admin (user_id = 1)
            $companyDetails = CompanyDetail::where('user_id', 1)->first();
            
            if (!$companyDetails || !$companyDetails->qpanel_api_url || !$companyDetails->qpanel_api_key) {
                Log::error('Credenciais do QPanel não configuradas no sistema');
                return ['success' => false, 'message' => 'Configurações do QPanel não encontradas'];
            }
    
            $curl = curl_init();
    
            $postData = [
                'userId' => $dono->id_qpanel,
                'username' => $cliente->iptv_nome,
                'packageId' => $cliente->plano_qpanel
            ];
    
            // Monta a URL completa com a base do banco de dados
            $urlCompleta = rtrim($companyDetails->qpanel_api_url, '/') . '/api/webhook/customer/renew';
    
            curl_setopt_array($curl, [
                CURLOPT_URL => $urlCompleta,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($postData),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $companyDetails->qpanel_api_key
                ],
            ]);
    
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
    
            $responseData = json_decode($response, true);
    
            if ($httpCode !== 200) {
                Log::error('Falha ao renovar no QPanel', [
                    'cliente_id' => $cliente->id,
                    'response' => $response,
                    'http_code' => $httpCode,
                    'api_url' => $urlCompleta
                ]);
                return ['success' => false, 'message' => $responseData['message'] ?? 'Erro ao renovar no QPanel'];
            }
    
            return ['success' => true, 'message' => 'Renovação no QPanel realizada com sucesso'];
    
        } catch (\Exception $e) {
            Log::error('Exceção ao renovar no QPanel', [
                'cliente_id' => $cliente->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    private function obterCreditosPlanoQPanel($planoId)
    {
        try {
            // Busca as credenciais do QPanel do admin (user_id = 1)
            $companyDetails = CompanyDetail::where('user_id', 1)->first();
            
            if (!$companyDetails || !$companyDetails->qpanel_api_url || !$companyDetails->qpanel_api_key) {
                Log::error('Credenciais do QPanel não configuradas no sistema');
                return ['success' => false, 'credits' => 1];
            }
    
            $curl = curl_init();
    
            // Monta a URL completa com a base do banco de dados
            $urlCompleta = rtrim($companyDetails->qpanel_api_url, '/') . '/api/webhook/package';
    
            curl_setopt_array($curl, [
                CURLOPT_URL => $urlCompleta,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $companyDetails->qpanel_api_key
                ],
            ]);
    
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
    
            if ($httpCode !== 200) {
                Log::error('Falha ao obter créditos do plano QPanel', [
                    'plano_id' => $planoId,
                    'http_code' => $httpCode,
                    'api_url' => $urlCompleta
                ]);
                return ['success' => false, 'credits' => 1];
            }
    
            $responseData = json_decode($response, true);
            $planos = $responseData['data'] ?? [];
    
            foreach ($planos as $plano) {
                if ($plano['id'] === $planoId) {
                    return ['success' => true, 'credits' => $plano['credits'] ?? 1];
                }
            }
    
            return ['success' => false, 'credits' => 1];
    
        } catch (\Exception $e) {
            Log::error('Erro ao obter créditos do plano QPanel', [
                'plano_id' => $planoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['success' => false, 'credits' => 1];
        }
    }

    private function adicionarSaldoIndicacao($referredId)
    {
        $companyDetail = CompanyDetail::first();
        $valorIndicacao = $companyDetail->referral_balance;

        $indicacao = indicacoes::where('referred_id', $referredId)->first();

        if ($indicacao) {
            $indicacao->ganhos += $valorIndicacao;

            if ($indicacao->status == 'pendente') {
                $indicacao->status = 'ativo';
            }

            $indicacao->save();
            Log::info('Saldo de indicação adicionado ao usuário: ' . $indicacao->user_id);
        }
    }

    private function adicionarCreditosUsuario($paymentId)
    {
        $paymentRecord = Pagamento::where('mercado_pago_id', $paymentId)->first();
    
        if ($paymentRecord && $paymentRecord->status === 'approved') {
            $user = User::find($paymentRecord->user_id);
    
            if ($user) {
                $user->creditos += $paymentRecord->credito_id;
                $user->save();
    
                Log::info('Créditos adicionados ao usuário: ' . json_encode($user));
                $this->notificarAprovacaoCreditos($user, $paymentRecord);
            } else {
                Log::warning('Usuário não encontrado para user_id: ' . $paymentRecord->user_id);
            }
        } else {
            Log::warning('Pagamento não encontrado ou não aprovado: ' . $paymentId);
        }
    }

    private function notificarAprovacaoCreditos($user, $paymentRecord)
    {
        $adminUser = User::where('role_id', 1)->firstOrFail();

        // pesquisa o user
        $user = User::find($paymentRecord->user_id);
    
        if ($adminUser) {
            $template = Template::where('finalidade', 'creditos_aprovados')->firstOrFail();
            $saudacao = $this->getSaudacao();
    
            $dadosDono = [
                '{nome_cliente}' => $user->name,
                '{creditos}' => $paymentRecord->credito_id,
                '{data_atual}' => Carbon::now()->format('d/m/Y'),
                '{saudacao}' => $saudacao,
                '{nome_dono}' => $adminUser->name,
            ];
    
            $conteudoDono = $this->substituirPlaceholders($template->conteudo, $dadosDono);
            Log::info('Conteúdo da mensagem para o dono: ' . $conteudoDono);
            $this->sendMessage($user->whatsapp, $conteudoDono, $adminUser->id);
        } else {
            Log::warning('Dono do cliente não encontrado.');
        }
    }

    private function renovarPlanoUser($userId, $planoId, $isAnual)
    {
        $user = User::find($userId);
        $plano = PlanoRenovacao::find($planoId);

        if ($user && $plano) {
            $duracao = $isAnual ? '1 year' : '1 month';
            $user->trial_ends_at = now()->add($duracao);
            $user->limite = $plano->limite;
            $user->save();

            Log::info('Plano renovado com sucesso para o usuário: ' . $user->id);
        } else {
            Log::warning('Usuário ou plano não encontrado para renovação.');
        }
    }

    private function renovarPlanoCliente($clienteId, $planoId)
    {
        $cliente = Cliente::find($clienteId);
        $plano = Plano::find($planoId);

        if ($cliente && $plano) {
            $novaData = Carbon::parse($cliente->vencimento)->addDays($plano->duracao);
            $cliente->vencimento = $novaData;
            $cliente->save();

            Log::info('Plano renovado com sucesso para o cliente: ' . $cliente->id);
            return $novaData;
        } else {
            Log::warning('Cliente ou plano não encontrado para renovação.');
            return null;
        }
    }

    private function notifyClientAndOwner($paymentRecord, $novaDataVencimento = null)
    {
        $cliente = Cliente::find($paymentRecord->cliente_id);
        if ($cliente) {
            $template = Template::where('finalidade', 'pagamentos')
                                ->where('user_id', $cliente->user_id)
                                ->first() ?? Template::where('finalidade', 'pagamentos')
                                                     ->whereNull('user_id')
                                                     ->firstOrFail();
    
            $statusPagamentoMap = [
                'paid' => 'Pago',
                'pending' => 'Pendente',
                'failed' => 'Falhou',
                'in_process' => 'Em Processo',
                'approved' => 'Aprovado',
            ];
    
            $statusPagamento = $statusPagamentoMap[$paymentRecord->status] ?? $paymentRecord->status ?? 'Status do Pagamento';
            $company = CompanyDetail::where('user_id', $cliente->user_id)->first();
            $nomeEmpresa = $company ? $company->company_name : '{nome_empresa}';
            $whatsappEmpresa = $company ? $company->company_whatsapp : '{whatsapp_empresa}';
            $owner = User::find($cliente->user_id);
            $nomeDono = $owner ? $owner->name : '{nome_dono}';
            $whatsappDono = $owner ? $owner->whatsapp : '{whatsapp_dono}';
            $plano = Plano::find($paymentRecord->plano_id);
            $nomePlano = $plano ? $plano->nome : 'Nome do Plano';
            $valorPlano = $plano ? $plano->preco : 'Valor do Plano';
            $saudacao = $this->getSaudacao();
            $textExpirate = $novaDataVencimento ? Carbon::parse($novaDataVencimento)->format('d/m/Y') : $this->getTextExpirate($cliente->vencimento);
    
            $dadosCliente = [
                '{nome_cliente}' => $cliente->nome ?? 'Nome do Cliente',
                '{telefone_cliente}' => $cliente->whatsapp ?? '(11) 99999-9999',
                '{notas}' => $cliente->notas ?? 'Notas',
                '{vencimento_cliente}' => Carbon::parse($cliente->vencimento)->format('d/m/Y') ?? 'Vencimento do Cliente',
                '{plano_nome}' => $nomePlano,
                '{plano_valor}' => $valorPlano,
                '{data_atual}' => Carbon::now()->format('d/m/Y'),
                '{plano_link}' => $paymentRecord->link_pagamento ?? 'Link de Pagamento',
                '{text_expirate}' => $textExpirate,
                '{saudacao}' => $saudacao,
                '{payload_pix}' => $paymentRecord->payload_pix ?? 'Pix Copia e Cola',
                '{whatsap_empresa}' => $whatsappEmpresa,
                '{status_pagamento}' => $statusPagamento,
                '{nome_empresa}' => $nomeEmpresa,
                '{nome_dono}' => $nomeDono,
            ];
    
            $conteudoCliente = $this->substituirPlaceholders($template->conteudo, $dadosCliente);
            Log::info('Conteúdo da mensagem para o cliente: ' . $conteudoCliente);
            $this->sendMessage($cliente->whatsapp, $conteudoCliente, $cliente->user_id);
    
            if ($owner) {
                // Mensagem personalizada para o dono incluindo status do QPanel
                $mensagemDono = "Olá, tudo bem?\n";
                $mensagemDono .= "O cliente {$cliente->nome} fez o pagamento do plano *{$nomePlano}*.\n";
                $mensagemDono .= "No valor de: R$ {$valorPlano}.\n";
                $mensagemDono .= "Data do Pagamento: " . Carbon::now()->format('d/m/Y') . ".\n\n";
    
                // Adiciona informações do QPanel se aplicável
                if ($cliente->sync_qpanel == 1 && isset($paymentRecord->qpanel_success)) {
                    if ($paymentRecord->qpanel_success) {
                        $mensagemDono .= "Seu cliente foi renovado no Qpanel e foi deduzido {$paymentRecord->qpanel_credits} crédito" . ($paymentRecord->qpanel_credits > 1 ? 's' : '') . " do seu painel.";
                    } else {
                        if (str_contains($paymentRecord->qpanel_message, 'You don\'t have enough credits')) {
                            $mensagemDono .= "Entretanto, o seu painel não possui créditos suficientes para fazer a renovação automática.";
                        } else {
                            $mensagemDono .= "Houve um erro ao renovar no QPanel: {$paymentRecord->qpanel_message}";
                        }
                    }
                }
    
                $this->sendMessage($owner->whatsapp, $mensagemDono, $owner->id);
            } else {
                Log::warning('Dono do cliente não encontrado.');
            }
        } else {
            Log::warning('Cliente não encontrado para o pagamento: ' . json_encode($paymentRecord));
        }
    }

    private function getTextExpirate($vencimento)
    {
        $dataVencimento = Carbon::parse($vencimento);
        $dataAtual = Carbon::now();
        $intervalo = $dataAtual->diff($dataVencimento);

        if ($intervalo->invert) {
            return 'expirou há ' . $intervalo->days . ' dias';
        } elseif ($intervalo->days == 0) {
            return 'expira hoje';
        } else {
            return 'expira em ' . $intervalo->days . ' dias';
        }
    }

    private function getSaudacao()
    {
        $hora = date('H');
        if ($hora < 12) {
            return 'Bom dia!';
        } elseif ($hora < 18) {
            return 'Boa tarde!';
        } else {
            return 'Boa noite!';
        }
    }

    private function substituirPlaceholders($conteudo, $dados)
    {
        foreach ($dados as $placeholder => $valor) {
            $conteudo = str_replace($placeholder, $valor, $conteudo);
        }
        return $conteudo;
    }

    private function sendMessage($phone, $message, $user_id)
    {
        Log::info('Enviando mensagem para ' . $phone . ': ' . $message);
        $sendMessageController = new SendMessageController();
        $request = new Request([
            'phone' => $phone,
            'message' => $message,
            'user_id' => $user_id,
        ]);
        $sendMessageController->sendMessageWithoutAuth($request);
    }
}