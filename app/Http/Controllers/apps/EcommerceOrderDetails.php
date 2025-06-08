<?php

namespace App\Http\Controllers\apps;

use App\Http\Controllers\Controller;
use App\Models\Pagamento;
use App\Models\Cliente;
use Illuminate\Http\Request;
use App\Models\PlanoRenovacao;
use Illuminate\Support\Facades\Auth;
use App\Models\CompanyDetail;
use App\Http\Controllers\SendMessageController;
use App\Models\Plano;
use Illuminate\Support\Facades\Log;
use App\Models\Template;
use App\Models\User;
use Carbon\Carbon;

class EcommerceOrderDetails extends Controller
{
  public function __construct()
  {
    // Aplicar middleware de autenticação
    $this->middleware('auth');
  }

  public function index(Request $request)
  {
      $user = Auth::user();
  
      // Buscar a cobrança específica
      $paymentId = $request->query('order_id');
  
      $payment = Pagamento::find($paymentId);
  
      // Verificar se o pagamento foi encontrado
      if (!$payment) {
          return redirect()->back()->with('error', 'Pagamento não encontrado.');
      }
  
    
      $cliente = Cliente::find($payment->cliente_id);
      if (!$cliente) {
          return redirect()->back()->with('error', 'Nenhum Pagamento encontrado para este Cliente.');
      }
  
      $empresa = CompanyDetail::where('user_id', $payment->user_id)->first();
      if (!$empresa) {
          return redirect()->back()->with('error', 'Empresa não encontrada.');
      }
  
      $plano = Plano::find($cliente->plano_id);
      if (!$plano) {
          return redirect()->back()->with('error', 'Plano não encontrado.');
      }
    
      $planos_revenda = PlanoRenovacao::all();
   
  
      $current_plan_id = $user->plano_id;
      return view('content.apps.detalhes', compact('payment', 'cliente', 'planos_revenda', 'current_plan_id', 'empresa', 'plano'));
    }

    public function addPayment(Request $request)
    {
        try {
            \Log::info('Iniciando addPayment', ['request_data' => $request->all()]);
    
            $request->validate([
                'payment_id' => 'required|exists:pagamentos,id',
                'invoiceAmount' => 'required|numeric',
                'payment_date' => 'required|date',
                'payment_status' => 'required|string|in:pending,approved',
            ]);
    
            \Log::info('Validação passou com sucesso');
    
            $payment = Pagamento::findOrFail($request->payment_id);
            \Log::info('Pagamento encontrado', ['payment_id' => $payment->id, 'current_status' => $payment->status]);
    
            // Guardamos o status original para caso precise reverter
            $originalStatus = $payment->status;
    
            // Atualizamos os dados do pagamento, mas ainda não salvamos
            $payment->valor = $request->invoiceAmount;
            $payment->status = $request->payment_status;
            $payment->payment_date = $request->payment_date;
            $payment->updated_at = now();
    
            $cliente = null;
            $plano = null;
            
            if ($payment->status === 'approved') {
                \Log::info('Pagamento aprovado - processando atualizações do cliente');
                
                $cliente = Cliente::find($payment->cliente_id);
                if ($cliente) {
                    \Log::info('Cliente encontrado', ['cliente_id' => $cliente->id]);
                    
                    $plano = Plano::find($cliente->plano_id);
                    if ($plano) {
                        \Log::info('Plano encontrado', ['plano_id' => $plano->id, 'duracao' => $plano->duracao]);
                        
                        $paymentDate = Carbon::parse($request->payment_date);
                        $currentDueDate = Carbon::parse($cliente->vencimento);
                        
                        \Log::info('Datas comparadas', [
                            'payment_date' => $paymentDate,
                            'current_due_date' => $currentDueDate
                        ]);
                        
                        if ($currentDueDate->lt($paymentDate)) {
                            \Log::info('Data de vencimento atual é anterior à data de pagamento - atualizando vencimento');
                            $cliente->vencimento = $paymentDate;
                        }
                        
                        $newDueDate = Carbon::parse($cliente->vencimento)->addDays($plano->duracao);
                        $cliente->vencimento = $newDueDate;
                        \Log::info('Nova data de vencimento calculada', ['new_due_date' => $cliente->vencimento]);
                        
                        // Salva as alterações no cliente primeiro
                        $cliente->save();
                    }
                }
                
                // Salva as alterações no pagamento
                $payment->save();
                
                try {
                    \Log::info('Tentando enviar notificações para cliente e proprietário');
                    $this->notifyClientAndOwner($payment, true);
                    \Log::info('Notificações enviadas com sucesso');
                } 
                
                catch (\Exception $e) {
                    \Log::error('Falha ao enviar notificações', [
                        'error' => $e->getMessage(),
                        'payment_id' => $payment->id,
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    // Não revertemos mais o status, apenas registramos o erro
                    if (strpos($e->getMessage(), 'No query results for model [App\\Models\\Template]') !== false) {
                        return redirect()->back()
                               ->with('success', 'Pagamento atualizado com sucesso, mas houve um problema ao enviar as notificações: O template com a finalidade Pagamentos não foi configurado.');
                    }
                    
                    return redirect()->back()
                           ->with('success', 'Pagamento atualizado com sucesso, mas houve um problema ao enviar as notificações: ' . $e->getMessage());
                }
            } else {
                // Se não for approved, apenas salva as alterações
                $payment->save();
            }
    
            \Log::info('Processo concluído com sucesso, redirecionando');
            return redirect()->back()->with('success', 'Pagamento atualizado com sucesso.');
    
        } catch (\Exception $e) {
            \Log::error('Erro fatal em addPayment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return redirect()->back()
                   ->with('error', 'Ocorreu um erro ao processar o pagamento: ' . $e->getMessage());
        }
    }
    
    private function notifyClientAndOwner($paymentRecord, $shouldProcessRenewal = false)
    {
        $cliente = Cliente::find($paymentRecord->cliente_id);
        if (!$cliente) return;
    
        // Buscar o template para notificações de pagamento
        $template = Template::where('finalidade', 'pagamentos')
            ->where('user_id', $cliente->user_id)
            ->first();
    
        if (!$template) {
            $template = Template::where('finalidade', 'pagamentos')
                ->whereNull('user_id')
                ->firstOrFail();
        }
    
        $statusPagamentoMap = [
            'paid' => 'Pago',
            'pending' => 'Pendente',
            'failed' => 'Falhou',
            'in_process' => 'Em Processo',
            'approved' => 'Aprovado',
        ];
    
        $statusPagamento = $statusPagamentoMap[$paymentRecord->status] ?? $paymentRecord->status ?? 'Status do Pagamento';
    
        // Buscar os dados da empresa
        $company = CompanyDetail::where('user_id', $cliente->user_id)->first();
        $nomeEmpresa = $company ? $company->company_name : '{nome_empresa}';
        $whatsappEmpresa = $company ? $company->company_whatsapp : '{whatsapp_empresa}';
    
        // Buscar os dados do dono do cliente
        $owner = User::find($cliente->user_id);
        $nomeDono = $owner ? $owner->name : '{nome_dono}';
        $whatsappDono = $owner ? $owner->whatsapp : '{whatsapp_dono}';
    
        // Buscar o plano na tabela planos
        $plano = Plano::find($paymentRecord->plano_id);
        $nomePlano = $plano ? $plano->nome : 'Nome do Plano';
        $valorPlano = $plano ? $plano->preco : 'Valor do Plano';
    
        // Obter a saudação e o texto de expiração
        $saudacao = $this->getSaudacao();
        $textExpirate = $this->getTextExpirate($cliente->vencimento);
    
        // Dados para substituir os placeholders no template
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
    
        // Notificar o cliente - mantemos a mensagem padrão do template
        $conteudoCliente = $this->substituirPlaceholders($template->conteudo, $dadosCliente);
        $this->sendMessage($cliente->whatsapp, $conteudoCliente, $cliente->user_id);
    
        // Notificar o dono do cliente - sempre com a mensagem detalhada
        if ($owner) {
            $creditos = 1;
            $renovacaoSucesso = true;
            $mensagemErro = '';
    
            // Processar renovação apenas quando necessário
            if ($shouldProcessRenewal && $cliente->sync_qpanel == 1 && $paymentRecord->status === 'approved') {
                $infoPlano = $this->obterCreditosPlanoQPanel($cliente->plano_qpanel);
                $creditos = $infoPlano['credits'];
                
                $resultadoRenovacao = $this->renovarNoQPanel($cliente);
                $renovacaoSucesso = $resultadoRenovacao['success'];
                $mensagemErro = $resultadoRenovacao['message'] ?? '';
            }
    
            // Montar mensagem padrão para o dono (mesmo formato para todos os tipos de pagamento)
            $mensagemDono = "Olá, tudo bem?\n";
            $mensagemDono .= "O cliente {$cliente->nome} fez o pagamento do plano *{$nomePlano}*.\n";
            $mensagemDono .= "No valor de: R$ {$valorPlano}.\n";
            $mensagemDono .= "Data do Pagamento: " . Carbon::now()->format('d/m/Y') . ".\n";
            $mensagemDono .= "Nova data de vencimento: " . Carbon::parse($cliente->vencimento)->format('d/m/Y') . ".\n\n";
    
            // Adicionar informações específicas para clientes com sync_qpanel
            if ($cliente->sync_qpanel == 1) {
                if ($renovacaoSucesso) {
                    $mensagemDono .= "Seu cliente foi renovado no Qpanel e foi deduzido {$creditos} crédito" . ($creditos > 1 ? 's' : '') . " do seu painel.";
                } else {
                    if (str_contains($mensagemErro, 'You don\'t have enough credits')) {
                        $mensagemDono .= "Entretanto, o seu painel não possui créditos suficientes para fazer a renovação automática.";
                    } else {
                        $mensagemDono .= "Houve um erro ao renovar no QPanel: {$mensagemErro}";
                    }
                }
            }
    
            $this->sendMessage($owner->whatsapp, $mensagemDono, $owner->id);
        }
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
    
            // Monta a URL completa
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
    
            // Monta a URL completa
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

    private function substituirPlaceholders($conteudo, $dados)
    {
      foreach ($dados as $placeholder => $valor) {
          $conteudo = str_replace($placeholder, $valor, $conteudo);
      }
      return $conteudo;
    }

    private function sendMessage($phone, $message, $user_id)
    {
     
      $sendMessageController = new SendMessageController();
      $request = new Request([
          'phone' => $phone,
          'message' => $message,
          'user_id' => $user_id,
      ]);
      $sendMessageController->sendMessageWithoutAuth($request);
    }
}