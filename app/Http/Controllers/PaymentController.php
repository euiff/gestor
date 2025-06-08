<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PlanoRenovacao;
use App\Models\User;
use App\Models\Pagamento;
use App\Models\indicacoes; // Correção da importação
use App\Models\CompanyDetail; // Importação do modelo CompanyDetail
use Illuminate\Support\Facades\Log;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\MercadoPagoConfig;
use App\Models\Revenda;

class PaymentController extends Controller
{

    public function processPayment(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'payment_method' => 'required|string|in:credit_card,pix',
            'plano_id' => 'required|exists:planos_renovacao,id', // Validar o ID do plano
            'isAnual' => 'required|boolean', // Validar se é anual
            'card_token' => 'required_if:payment_method,credit_card|string', // Validar o token do cartão
            'use_saldo_ganhos' => 'required|boolean', // Validar se o usuário quer usar o saldo de ganhos
            'card_cpf' => 'required_if:payment_method,credit_card|string', // Validar o CPF do cartão
            'card_holder_name' => 'required_if:payment_method,credit_card|string', // Validar o nome do titular do cartão
        ]);

        Log::info('Iniciando processo de pagamento...' . json_encode($request->all()));

        $user = User::findOrFail($request->user_id);
        $plano = PlanoRenovacao::findOrFail($request->plano_id);





        // Determinar o valor do plano a ser usado
        $valorPlano = $request->isAnual ? $plano->preco * 12 * 0.9 : $plano->preco;

        // Obter saldo de ganhos diretamente da tabela 'indicacoes'
        $saldoGanhos = $this->getUserSaldoGanhos($user->id);
        Log::info('Saldo de ganhos do usuário: ' . $saldoGanhos);

        // Aplicar saldo de ganhos como desconto se o usuário optar por isso
        if ($request->use_saldo_ganhos) {
            if ($saldoGanhos >= $valorPlano) {
                // Se o saldo de ganhos for suficiente para cobrir o valor do plano
                $this->updateUserSaldoGanhos($user->id, $valorPlano);
                Log::info('Pagamento realizado com sucesso usando saldo de ganhos.');

                // Atualizar a data de término do período de teste e o limite do usuário
                $this->renovarPlano($user, $plano, $request->isAnual);

                return response()->json([
                    'success' => true,
                    'message' => 'Pagamento realizado com sucesso usando saldo de ganhos.',
                    'valor_final' => 0
                ]);
            } else {
                // Aplicar saldo de ganhos como desconto
                $valorFinal = max(0, $valorPlano - $saldoGanhos);
                Log::info('Valor final após aplicar saldo de ganhos: ' . $valorFinal);
                $this->updateUserSaldoGanhos($user->id, $saldoGanhos);
            }
        } else {
            $valorFinal = $valorPlano;
        }

        // Processar o pagamento com Mercado Pago se o valor final for maior que zero
        if ($valorFinal > 0) {
            if ($request->payment_method === 'pix') {
                return $this->processPixPayment($valorFinal, $user, $request);
            } else if ($request->payment_method === 'credit_card') {
                return $this->processCardPayment($valorFinal, $user, $request->card_token, $request);
            }
        } else {
            return response()->json([
                'success' => true,
                'message' => 'Pagamento realizado com sucesso usando saldo de ganhos.',
                'valor_final' => 0
            ]);
        }
    }

    private function renovarPlano($user, $plano, $isAnual)
    {
        // Verifica se o usuário está no período de testes (trial_ends_at muito no futuro)
        $dataAtual = now();
        $dataTesteFuturo = $dataAtual->copy()->addDays(9999); // Data de 9999 dias no futuro
    
        if ($user->trial_ends_at && $user->trial_ends_at->gt($dataTesteFuturo)) {
            // Usuário está no período de testes: zera a data de término
            $user->trial_ends_at = null;
            Log::info('Usuário no período de testes. Data de término zerada para renovação.', ['user_id' => $user->id]);
        }
    
        // Define a duração do plano (anual ou mensal)
        $duracao = $isAnual ? '1 year' : '1 month';
    
        // Atualiza a data de término do plano
        $user->trial_ends_at = $dataAtual->add($duracao);
    
        // Atualiza o limite do usuário com base no plano
        $user->limite = $plano->limite;
    
        // Salva as alterações no usuário
        $user->save();
    
        // Adiciona saldo de indicação ao usuário que fez a indicação
        $this->adicionarSaldoIndicacao($user->id);
    
        Log::info('Plano renovado com sucesso para o usuário.', [
            'user_id' => $user->id,
            'novo_trial_ends_at' => $user->trial_ends_at,
            'limite' => $user->limite,
        ]);
    }

    private function adicionarSaldoIndicacao($referredId)
    {
        // Obter o valor da indicação da tabela company_details usando o modelo CompanyDetail
        $companyDetail = CompanyDetail::first();
        $valorIndicacao = $companyDetail->referral_balance;

        // Obter a indicação correspondente ao usuário que está fazendo a compra
        $indicacao = indicacoes::where('referred_id', $referredId)->first();

        if ($indicacao) {
            // Somar o valor da indicação ao valor existente na coluna ganhos
            $indicacao->ganhos += $valorIndicacao;

            // Atualizar o status para "ativo" se estiver "pendente"
            if ($indicacao->status == 'pendente') {
                $indicacao->status = 'ativo';
            }

            $indicacao->save();

            Log::info('Saldo de indicação adicionado ao usuário: ' . $indicacao->user_id);
        }
    }

    private function getUserSaldoGanhos($userId)
    {
        return indicacoes::where('user_id', $userId)
                        ->where('status', 'ativo')
                        ->sum('ganhos');
    }

 

    private function updateUserSaldoGanhos($userId, $valorDesconto)
{
    $indicacoes = indicacoes::where('user_id', $userId)
                           ->where('status', 'ativo')
                           ->orderBy('created_at', 'asc')
                           ->get();

    foreach ($indicacoes as $indicacao) {
        if ($valorDesconto <= 0) {
            break;
        }

        if ($indicacao->ganhos <= $valorDesconto) {
            $valorDesconto -= $indicacao->ganhos;
            $indicacao->ganhos = 0;
            $indicacao->status = 'usado';
        } else {
            $indicacao->ganhos -= $valorDesconto;
            $valorDesconto = 0;
        }

        // Garantir que ganhos nunca seja NULL
        if (is_null($indicacao->ganhos)) {
            $indicacao->ganhos = 0;
        }

        $indicacao->save();
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

        if ($indicacao->ganhos == 0) {
            $indicacao->ganhos = min($indicacao->ganhos + $valorDesconto, $indicacao->ganhos_original);
            $valorDesconto -= $indicacao->ganhos;
            $indicacao->status = 'ativo';
        } else {
            $indicacao->ganhos += $valorDesconto;
            $valorDesconto = 0;
        }

        // Garantir que ganhos nunca seja NULL
        if (is_null($indicacao->ganhos)) {
            $indicacao->ganhos = 0;
        }

        $indicacao->save();
    }
}


    private function processPixPayment($valorPlano, $user, $request)
    {

   // Buscar o usuário com role_id = 1 (administrador)
      $adminUser = User::where('role_id', 1)->firstOrFail();

   // Obter o user_id do administrador
       $adminUserId = $adminUser->id;

 // Obter o access_token correspondente da tabela company_details usando o user_id do administrador
      $companyDetail = CompanyDetail::where('user_id', $adminUserId)->first();


      if (!$companyDetail) {
        Log::error('Access Token não encontrado para user_id: ' . $adminUserId);
        throw new \Exception('Access Token não encontrado.');
    }
      $accessToken = $companyDetail->access_token;
      $notification_url = $companyDetail->notification_url;

      Log::info('Access Token encontrado: ' . $accessToken);

        MercadoPagoConfig::setAccessToken($accessToken);
        MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::LOCAL);

        $paymentClient = new PaymentClient();

        $payer = [
            'email' => 'admin@admin.com',
            'first_name' => $user->name,
            'identification' => [
                'type' => 'CPF',
                'number' => '12345678909' // Substitua pelo CPF real do usuário
            ]
        ];

        $valorPlanoFormatado = number_format((float)$valorPlano, 2, '.', '');

        $transactionAmount = (float)$valorPlanoFormatado;

        $preference = [
            'transaction_amount' => $transactionAmount, // Usar o valor do plano determinado
            'description' => 'Pagamento de Plano',
            'notification_url' => $notification_url, // Certifique-se de que esta variável está definida no .env
            'payer' => $payer,
            'payment_method_id' => 'pix'
        ];

        Log::info('Dados da preferência: ' . json_encode($preference));

        try {
            $response = $paymentClient->create($preference);
            $paymentLink = $response->point_of_interaction->transaction_data->ticket_url;
            $payloadPix = $response->point_of_interaction->transaction_data->qr_code;
            $qrCodeBase64 = $response->point_of_interaction->transaction_data->qr_code_base64;
            $paymentId = $response->id;
            
            Pagamento::create([
                'cliente_id' => null,
                'user_id' => $user->id,
                'mercado_pago_id' => $response->id,
                'valor' => $valorPlano,
                'status' => 'pendente',
                'plano_id' => $request->plano_id,
                'isAnual' => $request->isAnual,
                'use_saldo_ganhos' => $request->use_saldo_ganhos,

            ]);

            if ($request->has('use_saldo_ganhos') && $request->use_saldo_ganhos) {
                $this->updateUserSaldoGanhos($user->id, $valorPlano);
            }

            return response()->json([
                'success' => true,
                'payment_link' => $paymentLink,
                'payload_pix' => $payloadPix,
                'qr_code_base64' => $qrCodeBase64,
                'valor_final' => $valorPlano,
                'payment_id' => $paymentId
            ]);
        } catch (MPApiException $e) {
            Log::error('Erro ao criar preferência de pagamento: ' . $e->getApiResponse()->getStatusCode());
            Log::error('Conteúdo: ' . json_encode($e->getApiResponse()->getContent()));

            // Restaurar saldo de ganhos se o pagamento falhar
            if ($request->has('use_saldo_ganhos') && $request->use_saldo_ganhos) {
                $this->restoreUserSaldoGanhos($user->id, $valorPlano);
            }

            return response()->json(['success' => false, 'message' => 'Erro ao criar preferência de pagamento.'], 500);
        } catch (\Exception $e) {
            Log::error('Erro: ' . $e->getMessage());

            // Restaurar saldo de ganhos se o pagamento falhar
            if ($request->has('use_saldo_ganhos') && $request->use_saldo_ganhos) {
                $this->restoreUserSaldoGanhos($user->id, $valorPlano);
            }

            return response()->json(['success' => false, 'message' => 'Erro ao criar preferência de pagamento.'], 500);
        }
    }

    private function processCardPayment($valorPlano, $user, $cardToken, $request)
    {
        // Buscar o usuário com role_id = 1 (administrador)
        $adminUser = User::where('role_id', 1)->firstOrFail();

        // Obter o user_id do administrador
        $adminUserId = $adminUser->id;

        // Obter o access_token correspondente da tabela company_details usando o user_id do administrador
        $companyDetail = CompanyDetail::where('user_id', $adminUserId)->first();

        if (!$companyDetail) {
            Log::error('Access Token não encontrado para user_id 2: ' . $adminUserId);
            throw new \Exception('Access Token não encontrado.');
        }
        $accessToken = $companyDetail->access_token;
        $notification_url = $companyDetail->notification_url;
        Log::info('Access Token encontrado: ' . $accessToken);

        MercadoPagoConfig::setAccessToken($accessToken);
        MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::LOCAL);

        $paymentClient = new PaymentClient();

        $payer = [
            'email' => 'admin@admin.com',
            'first_name' => $request->card_holder_name,
            'identification' => [
                'type' => 'CPF',
                'number' => $request->card_cpf, // Substitua pelo CPF real do usuário
            ]
        ];

        $valorPlanoFormatado = number_format((float)$valorPlano, 2, '.', '');

        // Certifique-se de que o valor seja um número, não uma string
        $transactionAmount = (float)$valorPlanoFormatado;

        $preference = [
            'transaction_amount' =>  $transactionAmount, // Usar o valor do plano determinado
            'description' => 'Pagamento de Plano',
            'notification_url' => $notification_url, // Certifique-se de que esta variável está definida no .env
            'payer' => $payer,
            'token' => $cardToken,
            'installments' => 1 // Número de parcelas
        ];

        Log::info('Dados da preferência de pagamento com cartão: ' . json_encode($preference));

        try {
            $response = $paymentClient->create($preference);

            // Verificar se a resposta contém o ID da transação
            if (isset($response->id)) {
              $paymentId = $response->id;

                Log::info('Pagamento com cartão criado com sucesso. ID da transação: ' .$paymentId);

                // Salvar os dados do pagamento na tabela 'pagamentos'
                Pagamento::create([
                    'cliente_id' => null, // Ajuste conforme necessário
                    'user_id' => $user->id,
                    'mercado_pago_id' => $paymentId,
                    'valor' => $valorPlano,
                    'status' => 'pendente', // Ajuste conforme necessário
                    'plano_id' => $request->plano_id,
                    'isAnual' => $request->isAnual,
                    'use_saldo_ganhos' => $request->use_saldo_ganhos,
                ]);

                // Atualizar o saldo de ganhos do usuário se ele optou por usar
                if ($request->has('use_saldo_ganhos') && $request->use_saldo_ganhos) {
                    $this->updateUserSaldoGanhos($user->id, $valorPlano);
                    Log::info('Saldo de ganhos atualizado após pagamento com cartão.');
                }

                // Se você só precisa do ID da transação e não de um link de pagamento, ajuste conforme necessário
                return response()->json([
                    'success' => true,
                    'payment_id' => $paymentId,
                    'valor_final' => $valorPlano
                ]);
            } else {
                Log::error('Resposta da API não contém dados esperados.');
                return response()->json(['success' => false, 'message' => 'Resposta da API não contém dados esperados.'], 500);
            }
        } catch (MPApiException $e) {
            Log::error('Erro ao criar preferência de pagamento: ' . $e->getApiResponse()->getStatusCode());
            Log::error('Conteúdo: ' . json_encode($e->getApiResponse()->getContent()));

            // Restaurar saldo de ganhos se o pagamento falhar
            if ($request->has('use_saldo_ganhos') && $request->use_saldo_ganhos) {
                $this->restoreUserSaldoGanhos($user->id, $valorPlano);
                Log::info('Saldo de ganhos restaurado após falha no pagamento com cartão.');
            }

            return response()->json(['success' => false, 'message' => 'Erro ao criar preferência de pagamento.'], 500);
        } catch (\Exception $e) {
            Log::error('Erro: ' . $e->getMessage());

            // Restaurar saldo de ganhos se o pagamento falhar
            if ($request->has('use_saldo_ganhos') && $request->use_saldo_ganhos) {
                $this->restoreUserSaldoGanhos($user->id, $valorPlano);
                Log::info('Saldo de ganhos restaurado após falha no pagamento com cartão.');
            }

            return response()->json(['success' => false, 'message' => 'Erro ao criar preferência de pagamento.'], 500);
        }
    }

    public function getSaldoGanhos($userId)
    {
        try {
            $saldoGanhos = indicacoes::where('user_id', $userId)
                                    ->where('status', 'ativo')
                                    ->sum('ganhos');

            return response()->json([
                'success' => true,
                'saldo_ganhos' => $saldoGanhos
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar saldo de ganhos: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar saldo de ganhos.'
            ], 500);
        }
    }


            public function processPaymentCreditos(Request $request)
            {
                // Validação dos dados recebidos
                $request->validate([
                    'credito_id' => 'required|exists:revendas,id',
                    'user_id' => 'required|exists:users,id',
                ]);

                // Obter o plano de créditos selecionado
                $revenda = Revenda::find($request->credito_id);

                // Obter o usuário
                $user = User::find($request->user_id);

                // Chamar o método para processar o pagamento via PIX
                return $this->processPixPaymentCreditos($revenda->total, $user, $revenda->id, $revenda->creditos);
            }





            public function processPixPaymentCreditos($valorPlano, $user, $revendaId, $creditos)
            {
                // Buscar o usuário com role_id = 1 (administrador)
                $adminUser = User::where('role_id', 1)->firstOrFail();

                // Obter o user_id do administrador
                $adminUserId = $adminUser->id;

                // Obter o access_token correspondente da tabela company_details usando o user_id do administrador
                $companyDetail = CompanyDetail::where('user_id', $adminUserId)->first();

                if (!$companyDetail) {
                    Log::error('Access Token não encontrado para user_id: ' . $adminUserId);
                    throw new \Exception('Access Token não encontrado.');
                }

                $adminCompanyDetail = CompanyDetail::where('user_id', $adminUser->id)->first();
                
                if (!$adminCompanyDetail) {
                    Log::error('Detalhes da empresa não encontrados para o administrador com user_id: ' . $adminUser->id);
                    throw new \Exception('Detalhes da empresa não encontrados para o administrador.');
                }
        
                $url_notification = $adminCompanyDetail->notification_url;
        
                $accessToken = $companyDetail->access_token;
                Log::info('Access Token encontrado: ' . $accessToken);

                MercadoPagoConfig::setAccessToken($accessToken);
                MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::LOCAL);

                $paymentClient = new PaymentClient();

                $payer = [
                    'email' => 'admin@admin.com',
                    'first_name' => $user->name,
                    'identification' => [
                        'type' => 'CPF',
                        'number' => '12345678909' // Substitua pelo CPF real do usuário
                    ]
                ];

                $preference = [
                    'transaction_amount' => (float)$valorPlano, // Usar o valor do plano determinado
                    'description' => 'Compra de Créditos',
                    'notification_url' => $url_notification, // Certifique-se de que esta variável está definida no .env
                    'payer' => $payer,
                    'payment_method_id' => 'pix'
                ];

                Log::info('Dados da preferência: ' . json_encode($preference));

                try {
                    $response = $paymentClient->create($preference);


                    Log::info('Pagamento criado com sucesso.para pix creditos' . json_encode($response));
                    // Verificar se a resposta contém os dados esperados
                    if (!isset($response->point_of_interaction->transaction_data)) {
                        throw new \Exception('Dados de transação não encontrados na resposta.');
                    }

                    $transactionData = $response->point_of_interaction->transaction_data;
                    $paymentLink = $transactionData->ticket_url ?? null;
                    $payloadPix = $transactionData->qr_code ?? null;
                    $qrCodeBase64 = $transactionData->qr_code_base64 ?? null;
                    $paymentId = $response->id ?? null;

                    if (!$paymentLink || !$payloadPix || !$qrCodeBase64 || !$paymentId) {
                        throw new \Exception('Dados de pagamento incompletos na resposta.');
                    }

                    // Formatar o valor corretamente para o banco de dados
                    $valorPlanoFormatted = number_format((float)$valorPlano, 2, '.', '');

                    // Salvar os dados do pagamento na tabela 'pagamentos'
                    Pagamento::create([
                        'cliente_id' => null, // Ajuste conforme necessário
                        'user_id' => $user->id,
                        'mercado_pago_id' => $paymentId,
                        'valor' => $valorPlanoFormatted,
                        'status' => 'pending', // Ajuste conforme necessário
                        'plano_id' => $revendaId,
                        'credito_id' => $creditos,
                        'isAnual' => false
                    ]);

                    return response()->json([
                        'success' => true,
                        'payment_link' => $paymentLink,
                        'payload_pixx' => $payloadPix,
                        'qr_code_base644' => $qrCodeBase64,
                        'valor_final' => $valorPlanoFormatted,
                        'payment_id' => $paymentId
                    ]);
                } catch (MPApiException $e) {
                    Log::error('Erro ao criar preferência de pagamento: ' . $e->getApiResponse()->getStatusCode());
                    Log::error('Conteúdo: ' . json_encode($e->getApiResponse()->getContent()));

                    return response()->json(['success' => false, 'message' => 'Erro ao criar preferência de pagamento.'], 500);
                } catch (\Exception $e) {
                    Log::error('Erro: ' . $e->getMessage());

                    return response()->json(['success' => false, 'message' => 'Erro ao criar preferência de pagamento.'], 500);
                }
            }
        }
