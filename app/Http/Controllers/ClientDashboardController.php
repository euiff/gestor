<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PlanoRenovacao;
use App\Models\Cliente;
use App\Models\Plano;
use App\Models\Pagamento;

use App\Models\CompanyDetail; // Importação do modelo CompanyDetail
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\MercadoPagoConfig;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use App\Models\Revenda;
use App\Http\Controllers\ConexaoController;
use App\Models\Conexao;
use App\Models\Template;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Models\Servidor;
use Illuminate\Support\Facades\Auth;
use MercadoPago\Client\Common\RequestOptions;
use Illuminate\Support\Facades\Hash;

class ClientDashboardController extends Controller
{
    public function index()
    {
        // Obter o cliente logado
        $cliente = auth()->user();


        // buscar o plano do  cliente com o id do plano e obtemos da tabela planos
        $cliente->plano = Plano::find($cliente->plano_id);



        // Obter planos de revenda
        $planos_revenda = PlanoRenovacao::all();
        $current_plan_id = $cliente->plano_id;

        // Obter dados de compras
        $totalCompras = Pagamento::where('cliente_id', $cliente->id)->count();
        $comprasPendentes = Pagamento::where('cliente_id', $cliente->id)->where('status', 'pending')->count();
        $comprasCanceladas = Pagamento::where('cliente_id', $cliente->id)->where('status', 'cancelled')->count();
        $comprasAtrasadas = Pagamento::where('cliente_id', $cliente->id)->where('status', 'approved')->count();

        // Calcular percentuais em relação à semana passada
        $totalComprasSemanaPassada = Pagamento::where('cliente_id', $cliente->id)->whereBetween('created_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()])->count();
        $comprasPendentesSemanaPassada = Pagamento::where('cliente_id', $cliente->id)->where('status', 'pending')->whereBetween('created_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()])->count();
        $comprasCanceladasSemanaPassada = Pagamento::where('cliente_id', $cliente->id)->where('status', 'cancelled')->whereBetween('created_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()])->count();
        $comprasAtrasadasSemanaPassada = Pagamento::where('cliente_id', $cliente->id)->where('status', 'approved')->whereBetween('created_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()])->count();

        // Calcular percentuais
        $percentualComprasSemana = $totalComprasSemanaPassada > 0 ? (($totalCompras - $totalComprasSemanaPassada) / $totalComprasSemanaPassada) * 100 : 0;
        $percentualPendentesSemana = $comprasPendentesSemanaPassada > 0 ? (($comprasPendentes - $comprasPendentesSemanaPassada) / $comprasPendentesSemanaPassada) * 100 : 0;
        $percentualCanceladasSemana = $comprasCanceladasSemanaPassada > 0 ? (($comprasCanceladas - $comprasCanceladasSemanaPassada) / $comprasCanceladasSemanaPassada) * 100 : 0;
        $percentualAtrasadasSemana = $comprasAtrasadasSemanaPassada > 0 ? (($comprasAtrasadas - $comprasAtrasadasSemanaPassada) / $comprasAtrasadasSemanaPassada) * 100 : 0;

        return view('client.dashboard', compact(
            'cliente',
            'planos_revenda',
            'current_plan_id',
            'totalCompras',
            'comprasPendentes',
            'comprasCanceladas',
            'comprasAtrasadas',
            'percentualComprasSemana',
            'percentualPendentesSemana',
            'percentualCanceladasSemana',
            'percentualAtrasadasSemana'
        ));
    }



    public function showCompras(Request $request)
    {
        // Obter o cliente logado
        $cliente = auth()->user();

        // Obter as compras do cliente com filtros
        $query = Pagamento::where('cliente_id', $cliente->id);

        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from') && $request->date_from != '') {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to != '') {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $compras = $query->get();
        $total = $compras->sum('valor');

        // Obter planos de revenda
        $planos_revenda = PlanoRenovacao::all();
        $current_plan_id = $cliente->plano_id;

        // Array de mapeamento de status
        $statusMap = [
            'approved' => 'Aprovado',
            'pending' => 'Pendente',
            'cancelled' => 'Cancelado'
        ];

        if ($request->ajax()) {
            return response()->json([
                'compras' => $compras,
                'total' => $total
            ]);
        }

        return view('client.comprovantes', compact('cliente', 'compras', 'planos_revenda', 'current_plan_id', 'total', 'statusMap'));
    }
    public function showPlanos()
    {
        // Obter o cliente logado
        $cliente = auth()->user();

        // Obter os planos disponíveis para o cliente
        $planos = Plano::where('user_id', $cliente->user_id)->get();
         // Obter planos de revenda
         $planos_revenda = PlanoRenovacao::all();
         $current_plan_id = $cliente->plano_id;

        return view('client.planos', compact('cliente', 'planos', 'planos_revenda', 'current_plan_id'));
    }



    public function processPaymentPlanos($clienteId)
    {
        Log::info('cobrancaManual chamada com clienteId: ' . $clienteId);
    
        $cliente = Cliente::findOrFail($clienteId);
        Log::info('Cliente encontrado: ' . json_encode($cliente));
    
        // Verifica se o cliente pode receber notificações
        if (!$cliente->notificacoes) {
            Log::info('Cliente não pode receber notificações.');
            return response()->json([
                'success' => false,
                'message' => 'Este cliente não pode receber notificações.'
            ]);
        }
    
        // Verifica se o cliente está conectado ao WhatsApp
        $conexao = Conexao::where('user_id', $cliente->user_id)->first();
        if (!$conexao || $conexao->conn != 1) {
            Log::info('Você não está conectado ao WhatsApp.');
            return response()->json([
                'success' => false,
                'message' => 'Você precisa conectar seu WhatsApp.'
            ]);
        }
    
        // Supondo que você tenha um template específico para cobrança manual
        $template = Template::where('finalidade', 'cobranca_manual')->firstOrFail();
        Log::info('Template encontrado: ' . json_encode($template));
    
        // Formata a data de vencimento para dd/mm/yyyy para exibição
        $vencimentoFormatado = Carbon::parse($cliente->vencimento)->format('d/m/Y');
    
        // Obter o access_token correspondente da tabela company_details usando o user_id do cliente
        $companyDetail = CompanyDetail::where('user_id', $cliente->user_id)->first();
    
        
            if (!$companyDetail) {
                Log::error('Access Token não encontrado para user_id: ' . $cliente->user_id);
                return response()->json([
                    'success' => false,
                    'message' => 'Access Token não encontrado.'
                ]);
            }
        $adminUser = User::where('role_id', 1)->first();
      
        if (!$adminUser) {
            Log::error('Administrador não encontrado.');
            throw new \Exception('Administrador não encontrado.');
        }
    
        $adminCompanyDetail = CompanyDetail::where('user_id', $adminUser->id)->first();
    
        if (!$adminCompanyDetail) {
            Log::error('Detalhes da empresa não encontrados para o administrador com user_id: ' . $adminUser->id);
            throw new \Exception('Detalhes da empresa não encontrados para o administrador.');
        }
    
        $url_notification = $adminCompanyDetail->notification_url;
    
    
        $accessToken = $companyDetail->access_token;
    
        if (!$accessToken) {
            Log::error('Access Token não encontrado. Verifique se a variável MERCADO_PAGO_ACCESS_TOKEN está definida no arquivo .env.');
            return response()->json([
                'success' => false,
                'message' => 'Access Token não encontrado.'
            ]);
        }
        Log::info('Access Token: ' . $accessToken);
        MercadoPagoConfig::setAccessToken($accessToken);
        MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::LOCAL);
    
        // Inicializa o cliente da API
        $paymentClient = new PaymentClient();
    
        // Buscar o plano na tabela planos
        $plano = Plano::findOrFail($cliente->plano_id);
        Log::info('Plano encontrado: ' . json_encode($plano));
    
        // Verifica se o valor do plano é positivo
        $valorPlano = (float) $plano->preco;
        Log::info('Valor do plano recuperado: ' . $valorPlano);
        if ($valorPlano <= 0) {
            Log::error('Valor do plano deve ser positivo. Valor encontrado: ' . $valorPlano);
            return response()->json([
                'success' => false,
                'message' => 'Valor do plano deve ser positivo.'
            ]);
        }
    
       
        // Cria a requisição de pagamento usando PIX
        $preference = [
            'transaction_amount' => $valorPlano,
            'description' => $plano->nome,
            'payment_method_id' => 'pix',
            'notification_url' => $url_notification,
            'payer' => [
                'email' => 'cliente@cliente.com',
                'first_name' => $cliente->nome,
                'identification' => [
                    'type' => 'CPF',
                    'number' => '12345678909' // Substitua pelo CPF real do cliente
                ]
            ]
        ];
    
        Log::info('Preferência de pagamento: ' . json_encode($preference));
    
        // Cria as opções de requisição
        $requestOptions = new RequestOptions();
        $requestOptions->setCustomHeaders(["X-Idempotency-Key: " . uniqid()]);
    
        Log::info('Opções de requisição: ' . json_encode($requestOptions));
    
        try {
            // Faz a requisição de pagamento
            $response = $paymentClient->create($preference);
            $paymentLink = $response->point_of_interaction->transaction_data->ticket_url;
            $payloadPix = $response->point_of_interaction->transaction_data->qr_code;
            $qrCodeBase64 = $response->point_of_interaction->transaction_data->qr_code_base64;
            $paymentId = $response->id;
    
            $pagamento = Pagamento::create([
                'cliente_id' => $cliente->id,
                'user_id' => $cliente->user_id,
                'mercado_pago_id' => $response->id,
                'valor' => $valorPlano,
                'status' => 'pending',
                'plano_id' => $cliente->plano_id,
                'isAnual' => false,
            ]);
    
        } catch (MPApiException $e) {
            Log::error('Erro ao criar preferência de pagamento: ' . $e->getApiResponse()->getStatusCode());
            Log::error('Conteúdo: ' . json_encode($e->getApiResponse()->getContent()));
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar preferência de pagamento.'
            ]);
        } catch (\Exception $e) {
            Log::error('Erro: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar preferência de pagamento.'
            ]);
        }
    
        // Buscar os dados da empresa
        $company = CompanyDetail::where('user_id', $cliente->user_id)->first();
        $nomeEmpresa = $company ? $company->company_name : '{nome_empresa}';
        $whatsappEmpresa = $company ? $company->company_whatsapp : '{whatsapp_empresa}';
    
        $dadosCliente = [
            'nome' => $cliente->nome,
            'telefone' => $cliente->whatsapp,
            'notas' => $cliente->notas,
            'vencimento' => $vencimentoFormatado,
            'plano_nome' => $plano->nome,
            'plano_valor' => $plano->preco,
            'plano_link' => $paymentLink, // Link de pagamento do Mercado Pago
            'text_expirate' => $this->getTextExpirate(Carbon::parse($cliente->vencimento)->format('Y-m-d')),
            'data_pagamento' => $pagamento->updated_at->format('d/m/Y') ?? 'Data do Pagamento',
            'status_pagamento' => $pagamento->status ?? 'Status do Pagamento',
            'nome_empresa' => $nomeEmpresa,
            'whatsapp_empresa' => $whatsappEmpresa,
        ];
    
        Log::info('Dados do cliente: ' . json_encode($dadosCliente));
    
        $conteudo = $this->substituirPlaceholders($template->conteudo, $dadosCliente);
        Log::info('Conteúdo da mensagem gerado: ' . $conteudo);
    
        $conexaoController = new ConexaoController();
    
        // Enviar mensagem para o cliente
        $responseCliente = $conexaoController->sendMessage(new Request([
            'phone' => $cliente->whatsapp,
            'message' => $conteudo,
        ]));
        Log::info('Mensagem enviada ao cliente, resposta: ' . json_encode($responseCliente));
    
        // Enviar mensagem para a empresa
        if ($company) {
            $dadosEmpresa = array_merge($dadosCliente, [
                'nome_empresa' => $company->company_name,
                'whatsapp_empresa' => $company->company_whatsapp
            ]);
            $conteudoEmpresa = $this->substituirPlaceholders($template->conteudo, $dadosEmpresa);
            $responseEmpresa = $conexaoController->sendMessage(new Request([
                'phone' => $company->company_whatsapp,
                'message' => $conteudoEmpresa,
            ]));
            Log::info('Mensagem enviada à empresa, resposta: ' . json_encode($responseEmpresa));
        }
    
        // Retorna os dados do pagamento em formato JSON
        return response()->json([
            'success' => true,
            'payment_id' => $paymentId,
            'payment_link' => $paymentLink,
            'payload_pix' => $payloadPix,
            'qr_code_base64' => $qrCodeBase64
        ]);
    }
    
    
    private function substituirPlaceholders($conteudo, $dadosCliente)
    {
        $placeholders = [
            '{nome_cliente}' => $dadosCliente['nome'] ?? 'Nome do Cliente',
            '{telefone_cliente}' => $dadosCliente['telefone'] ?? '(11) 99999-9999',
            '{notas}' => $dadosCliente['notas'] ?? 'Notas do cliente',
            '{vencimento_cliente}' => $dadosCliente['vencimento'] ?? '01/01/2023',
            '{plano_nome}' => $dadosCliente['plano_nome'] ?? 'Plano Básico',
            '{plano_valor}' => $dadosCliente['plano_valor'] ?? 'R$ 99,90',
            '{data_atual}' => date('d/m/Y'),
            '{plano_link}' => $dadosCliente['plano_link'] ?? 'http://linkdopagamento.com',
            '{text_expirate}' => $dadosCliente['text_expirate'] ?? '',
            '{saudacao}' => $this->getSaudacao(),
            '{data_pagamento}' => $dadosCliente['data_pagamento'] ?? 'Data do Pagamento',
            '{status_pagamento}' => $dadosCliente['status_pagamento'] ?? 'Status do Pagamento',
            '{nome_empresa}' => $dadosCliente['nome_empresa'] ?? 'Nome da Empresa',
            '{whatsapp_empresa}' => $dadosCliente['whatsapp_empresa'] ?? '(11) 99999-9999',
            '{nome_dono}' => $dadosCliente['nome_dono'] ?? 'Nome do Dono',
            '{whatsapp_dono}' => $dadosCliente['whatsapp_dono'] ?? '(11) 99999-9999',
        ];
    
        foreach ($placeholders as $placeholder => $valor) {
            $conteudo = str_replace($placeholder, $valor, $conteudo);
        }
    
        return $conteudo;
    }
    
    
    
    private function getTextExpirate($vencimento)
    {
        // Converte a data de yyyy-mm-dd para um objeto Carbon
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
}
