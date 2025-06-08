<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Conexao;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\PlanoRenovacao;
use App\Models\CompanyDetail;
use App\Models\User;

class ConexaoController extends Controller
{
    private $apikey;
    private $urlapi;

    public function __construct()
    {
        $adminUser = User::where('role_id', 1)->first();
        if ($adminUser) {
            $companyDetail = CompanyDetail::where('user_id', $adminUser->id)->first();
            if ($companyDetail) {
                $this->apikey = $companyDetail->evolution_api_key;
                $this->urlapi = $companyDetail->evolution_api_url;
            }
        }
    }
    
        public function index()
    {
        $user = auth()->user();
        Log::info("Usuário autenticado:", ['user_id' => $user->id]);
    
        $conexoes = Conexao::where('user_id', $user->id)->get();
        $planos_revenda = PlanoRenovacao::all();
        $current_plan_id = $user->plano_id;
    
        foreach ($conexoes as $conexao) {
            try {
                $url = $this->urlapi . '/instance/connectionState/Veetv_API' . $conexao->tokenid;
                Log::info("Verificando status da conexão para o token:", ['tokenid' => $conexao->tokenid, 'url' => $url]);
    
                $statusResponse = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'apikey' => $this->apikey,
                ])->withOptions([
                    'verify' => false,
                ])->get($url);
    
                if ($statusResponse->failed()) {
                    Log::error("Falha na requisição para verificar status da conexão.", [
                        'tokenid' => $conexao->tokenid,
                        'status_code' => $statusResponse->status(),
                        'response_body' => $statusResponse->body()
                    ]);
                    continue;
                }
    
                $statusRes = $statusResponse->json();
                Log::info("Resposta da API ao verificar o status da conexão:", ['response' => $statusRes]);
    
                if (!isset($statusRes['instance'])) {
                    Log::error("Erro: 'instance' não encontrado no JSON.", ['tokenid' => $conexao->tokenid, 'json' => $statusRes]);
                    continue;
                }
    
                if (!isset($statusRes['instance']['state'])) {
                    Log::error("Erro: 'state' não encontrado dentro de 'instance'.", ['tokenid' => $conexao->tokenid, 'json' => $statusRes]);
                    continue;
                }
    
                $state = $statusRes['instance']['state'];
                Log::info("Estado da conexão retornado:", ['tokenid' => $conexao->tokenid, 'state' => $state]);
    
                $conexao->conn = ($state === 'open') ? 1 : 0;
    
                $conexao->save();
                Log::info("Conexão atualizada com sucesso.", [
                    'tokenid' => $conexao->tokenid,
                    'conn' => $conexao->conn
                ]);
            } catch (\Exception $e) {
                Log::error("Erro ao atualizar a conexão.", [
                    'tokenid' => $conexao->tokenid,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
        // Verifica se há conexões com conn = 0 (desconectadas)
        $hasDisconnectedConnections = $conexoes->where('conn', 0)->isNotEmpty();
        
        return view('content.apps.app-whatsapp', compact('conexoes', 'planos_revenda', 'current_plan_id', 'hasDisconnectedConnections'));
    }

    public function createConnection(Request $request)
    {
        \Log::info('Iniciando createConnection', ['request_data' => $request->all()]);
    
        if ($request->has('phone')) {
            $phoneOriginal = $request->input('phone');
            $phone = preg_replace('/[^0-9]/', '', $phoneOriginal);
            $user = auth()->user();
    
            \Log::debug('Dados recebidos e sanitizados', [
                'phone_original' => $phoneOriginal,
                'phone_sanitizado' => $phone,
                'user_id' => $user ? $user->id : null
            ]);
    
            $conexaoExistente = Conexao::where('whatsapp', $phoneOriginal)->first();
            if ($conexaoExistente) {
                \Log::warning('Conexão já existe para este número', ['phone' => $phoneOriginal]);
                return redirect()->route('app-whatsapp')->with('error', 'Já existe uma conexão para este número de WhatsApp.');
            }
    
            $tokenid = bin2hex(random_bytes(16));
            $celular = "55" . $phone;
    
            // Obter a versão da API configurada para o usuário admin (ID=1)
            $apiVersion = 'v1'; // padrão
            $companyDetails = \DB::table('company_details')->where('user_id', 1)->first();
            if ($companyDetails && isset($companyDetails->api_version)) {
                $apiVersion = $companyDetails->api_version;
            }
    
            \Log::debug('Preparando dados para API externa', [
                'instanceName' => 'Veetv_API' . $tokenid,
                'token' => $tokenid,
                'number' => $celular,
                'api_version' => $apiVersion
            ]);
    
            try {
                $payload = [];
                
                if ($apiVersion === 'v2') {
                    $payload = [
                        'instanceName' => 'Veetv_API' . $tokenid,
                        'qrcode' => true,
                        'integration' => 'WHATSAPP-BAILEYS',
                        'number' => $celular // Adicionando o número no payload da v2
                    ];
                } else { // v1 (default)
                    $payload = [
                        'instanceName' => 'Veetv_API' . $tokenid,
                        'token' => $tokenid,
                        'qrcode' => true,
                        'number' => $celular,
                    ];
                }
    
                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'apikey' => $this->apikey,
                ])->withOptions([
                    'verify' => false,
                ])->post($this->urlapi . '/instance/create', $payload);
    
                $res = $response->json();
                \Log::debug('Resposta completa da API externa', ['response' => $res, 'api_version' => $apiVersion]);
    
                // Verifica se a instância foi criada com sucesso
                $instanceStatus = $res['instance']['status'] ?? null;
                $isSuccess = ($apiVersion === 'v2') 
                    ? ($instanceStatus === 'created' || $instanceStatus === 'connecting')
                    : ($instanceStatus === 'created');
    
                if ($isSuccess) {
                    $qrcodelink = $res['qrcode']['base64'] ?? null;
                    $pairingCode = $res['qrcode']['pairingCode'] ?? null;
                    $count = $res['qrcode']['count'] ?? 0;
                    
                    if (is_null($qrcodelink)) {
                        \Log::warning('API não retornou QR code, apenas contador', ['count' => $count]);
                        
                        $conexaoExistente = Conexao::where('tokenid', $tokenid)->first();
                        
                        if (!$conexaoExistente) {
                            Conexao::create([
                                'user_id' => $user->id,
                                'qrcode' => null,
                                'conn' => 0,
                                'whatsapp' => $phoneOriginal,
                                'tokenid' => $tokenid,
                                'notifica' => 0,
                                'saudacao' => null,
                                'arquivo' => null,
                                'midia' => null,
                                'tipo' => null,
                                'pairing_code' => null,
                                'formatted_pairing_code' => null,
                            ]);
                            \Log::info('Conexão criada sem QR code', ['tokenid' => $tokenid]);
                        }
                        
                        return redirect()->route('app-whatsapp')->with('warning', 'Instância criada, mas QR code não disponível ainda. Tente novamente em alguns instantes.');
                    }
    
                    $formattedPairingCode = $pairingCode ? substr($pairingCode, 0, 4) . ' - ' . substr($pairingCode, 4) : null;
    
                    Conexao::create([
                        'user_id' => $user->id,
                        'qrcode' => $qrcodelink,
                        'conn' => 0,
                        'whatsapp' => $phoneOriginal,
                        'tokenid' => $tokenid,
                        'notifica' => 0,
                        'saudacao' => null,
                        'arquivo' => null,
                        'midia' => null,
                        'tipo' => null,
                        'pairing_code' => $pairingCode,
                        'formatted_pairing_code' => $formattedPairingCode,
                    ]);
    
                    \Log::info('Conexão criada com sucesso', [
                        'phone_original' => $phoneOriginal,
                        'phone_api' => $celular,
                        'has_qrcode' => !is_null($qrcodelink),
                        'has_pairing_code' => !is_null($pairingCode),
                        'api_version' => $apiVersion,
                        'instance_status' => $instanceStatus
                    ]);
                    return redirect()->route('app-whatsapp');
                } else {
                    \Log::error('Instância não foi criada corretamente', [
                        'response' => $res,
                        'expected_status' => ($apiVersion === 'v2') ? 'created or connecting' : 'created',
                        'actual_status' => $instanceStatus
                    ]);
                    return redirect()->back()->withErrors(['error' => 'Erro ao criar a instância na API.']);
                }
            } catch (\Exception $e) {
                \Log::error('Erro ao chamar API externa', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'api_version' => $apiVersion
                ]);
                return redirect()->back()->withErrors(['error' => 'Erro ao conectar com o serviço de WhatsApp.']);
            }
        } else {
            \Log::warning('Parâmetro phone não encontrado na requisição');
            return redirect()->back()->withErrors(['error' => 'Número de WhatsApp não informado.']);
        }
    }
    
    public function instanceConnect($instanceName, $number)
    {
        \Log::info('Iniciando instanceConnect', [
            'instanceName' => $instanceName,
            'number' => $number
        ]);
    
        try {
            $response = Http::withHeaders([
                'apikey' => $this->apikey,
            ])->withOptions([
                'verify' => false,
            ])->get($this->urlapi . '/instance/connect/' . $instanceName, [
                'number' => $number
            ]);
    
            $res = $response->json();
            \Log::debug('Resposta da API para conexão', ['response' => $res]);
    
            if (isset($res['pairingCode'])) {
                $formattedPairingCode = substr($res['pairingCode'], 0, 4) . ' - ' . substr($res['pairingCode'], 4);
                
                $data = [
                    'pairing_code' => $res['pairingCode'],
                    'formatted_pairing_code' => $formattedPairingCode,
                    'count' => $res['count'] ?? 0
                ];
                
                if (isset($res['base64'])) {
                    $data['qrcode'] = $res['base64'];
                }
                
                return $data;
            } elseif (isset($res['base64'])) {
                return [
                    'qrcode' => $res['base64'],
                    'count' => $res['count'] ?? 0
                ];
            } else {
                \Log::error('Resposta inesperada ao conectar instância', ['response' => $res]);
                return false;
            }
            
        } catch (\Exception $e) {
            \Log::error('Erro ao conectar instância', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
    
    public function connect($id)
    {
        $conexao = Conexao::findOrFail($id);
        
        $result = $this->instanceConnect(
            'Veetv_API' . $conexao->tokenid,
            '55' . preg_replace('/[^0-9]/', '', $conexao->whatsapp)
        );
        
        if ($result) {
            // Atualiza os dados no banco
            $conexao->update($result);
            
            return response()->json([
                'qrcode' => $result['qrcode'] ?? null,
                'formatted_pairing_code' => $result['formatted_pairing_code'] ?? null
            ]);
        }
        
        return response()->json(['error' => 'Falha ao conectar'], 500);
    }

    public function updateConnection(Request $request)
    {
        if ($request->has('phone')) {
            $phone = $request->input('phone');
            $user = auth()->user();
            $conexao = Conexao::where('user_id', $user->id)->first();
            
            try {
                if ($conexao) {
                    $tokenid = $conexao->tokenid;
                    $celular = "55" . $phone;
                    $response = Http::withHeaders([
                        'apikey' => $this->apikey,
                    ])->withOptions([
                                'verify' => false,
                            ])->get($this->urlapi . '/instance/connectionState/Veetv_API' . $tokenid);
                    $res = $response->json();
                    $conexaoo = $res['instance']['state'] ?? 'false';

                    if ($conexaoo == 'open') {
                        $conexao->update(['conn' => 1]);
                        $user->update(['start' => 1]);
                        return response()->json(['success' => 'Conexão estabelecida com sucesso.']);
                    }
                    Http::withHeaders([
                        'apikey' => $this->apikey,
                    ])->withOptions([
                                'verify' => false,
                            ])->delete($this->urlapi . '/instance/delete/Veetv_API' . $tokenid);
                    sleep(3);
                    $tokenid = bin2hex(random_bytes(16));
                    $conexao->update(['tokenid' => $tokenid, 'conn' => 0]);
                    $response = Http::withHeaders([
                        'Content-Type' => 'application/json',
                        'apikey' => $this->apikey,
                    ])->withOptions([
                                'verify' => false,
                            ])->post($this->urlapi . '/instance/create', [
                                'instanceName' => 'Veetv_API' . $tokenid,
                                'token' => $tokenid,
                                'qrcode' => true,
                                'number' => $celular,
                            ]);
                    $res = $response->json();

                    if (isset($res['instance']['status']) && $res['instance']['status'] == 'created' && isset($res['qrcode']['base64'])) {
                        $qrcodelink = $res['qrcode']['base64'];
                        $conexao->update(['qrcode' => $qrcodelink]);

                        return response()->json(['qrcode' => $conexao->qrcode]);
                    } else {
                        Log::error('Resposta inesperada ao criar nova conexão:', $res);
                        return response()->json(['error' => 'Erro ao criar nova conexão.'], 500);
                    }
                } else {
                    $tokenid = bin2hex(random_bytes(16));
                    $celular = "55" . $phone;

                    $response = Http::withHeaders([
                        'Content-Type' => 'application/json',
                        'apikey' => $this->apikey,
                    ])->withOptions([
                                'verify' => false,
                            ])->post($this->urlapi . '/instance/create', [
                                'instanceName' => 'Veetv_API' . $tokenid,
                                'token' => $tokenid,
                                'qrcode' => true,
                                'number' => $celular,
                            ]);
                    $res = $response->json();

                    if (isset($res['instance']['status']) && $res['instance']['status'] == 'created' && isset($res['qrcode']['base64'])) {
                        $qrcodelink = $res['qrcode']['base64'];
                        Conexao::create([
                            'user_id' => $user->id,
                            'qrcode' => $qrcodelink,
                            'conn' => 0,
                            'whatsapp' => $phone,
                            'tokenid' => $tokenid,
                            'notifica' => 0,
                        ]);

                        return response()->json(['qrcode' => $qrcodelink]);
                    } else {
                        Log::error('Resposta inesperada ao criar nova conexão:', $res);
                        return response()->json(['error' => 'Erro ao criar nova conexão.'], 500);
                    }
                }
            } catch (\Exception $e) {
                Log::error('Erro ao atualizar conexão:', ['exception' => $e->getMessage()]);
                return response()->json(['error' => 'Erro interno do servidor.'], 500);
            }
        }
        return response()->json(['error' => 'Parâmetro telefone não fornecido.'], 400);
    }
    
    public function checkStatus($id)
    {
        $conexao = Conexao::findOrFail($id);
        
        try {
            // Verificar status na API
            $response = Http::withHeaders([
                'apikey' => $this->apikey,
            ])->withOptions([
                'verify' => false,
            ])->get($this->urlapi . '/instance/connectionState/' . 'Veetv_API' . $conexao->tokenid);
            
            $apiResponse = $response->json();
            \Log::info("Resposta da API ao verificar o status da conexão: " . json_encode($apiResponse));
            
            // Verificar se a instância está aberta
            $isOpen = ($apiResponse['instance']['state'] ?? null) === 'open';
            
            // Atualizar status no banco de dados se necessário
            if ($isOpen && $conexao->conn == 0) {
                $conexao->update(['conn' => 1]);
            }
            
            return response()->json([
                'conn' => $isOpen ? 1 : 0,
                'status' => $apiResponse['instance']['state'] ?? 'unknown'
            ]);
            
        } catch (\Exception $e) {
            \Log::error("Erro ao verificar status da conexão: " . $e->getMessage());
            return response()->json([
                'conn' => $conexao->conn,
                'status' => 'error'
            ]);
        }
    }

   public function deleteConnection($id)
    {
        Log::info('Iniciando processo de deleção da conexão com ID: ' . $id);
    
        $conexao = Conexao::find($id);
    
        if (!$conexao) {
            Log::error('Conexão não encontrada no banco de dados.', ['id' => $id]);
            return redirect()->route('app-whatsapp')->with('error', 'Item não encontrado.');
        }
    
        try {
            // 1. Tentar fazer logout da instância na API
            $logoutResponse = Http::withHeaders([
                'Content-Type' => 'application/json',
                'apikey' => $this->apikey,
            ])->withOptions([
                'verify' => false,
            ])->delete($this->urlapi . '/instance/logout/Veetv_API' . $conexao->tokenid);
    
            $logoutRes = $logoutResponse->json();
    
            // Se a instância não existir (404), podemos prosseguir
            if ($logoutResponse->status() === 404) {
                Log::warning('Instância não encontrada na API (404), prosseguindo com exclusão local', ['tokenid' => $conexao->tokenid]);
            } elseif (!(isset($logoutRes['status']) && strtoupper($logoutRes['status']) === 'SUCCESS' && isset($logoutRes['error']) && $logoutRes['error'] === false)) {
                Log::error('Erro ao fazer logout da instância.', ['response' => $logoutRes]);
                return redirect()->route('app-whatsapp')->with('error', 'Erro ao fazer logout da instância.');
            } else {
                Log::info('Logout da instância realizado com sucesso.', ['tokenid' => $conexao->tokenid]);
            }
    
            // 2. Tentar deletar a instância na API
            $deleteResponse = Http::withHeaders([
                'Content-Type' => 'application/json',
                'apikey' => $this->apikey,
            ])->withOptions([
                'verify' => false,
            ])->delete($this->urlapi . '/instance/delete/Veetv_API' . $conexao->tokenid);
    
            $deleteRes = $deleteResponse->json();
    
            // Se a instância não existir (404) ou for deletada com sucesso, prosseguir
            if ($deleteResponse->status() === 404) {
                Log::warning('Instância não encontrada na API (404), prosseguindo com exclusão local', ['tokenid' => $conexao->tokenid]);
            } elseif (!(isset($deleteRes['status']) && strtoupper($deleteRes['status']) === 'SUCCESS' && isset($deleteRes['error']) && $deleteRes['error'] === false)) {
                Log::error('Erro ao deletar a instância na API.', ['response' => $deleteRes]);
                return redirect()->route('app-whatsapp')->with('error', 'Erro ao deletar a instância na API.');
            } else {
                Log::info('Instância deletada com sucesso na API.', ['tokenid' => $conexao->tokenid]);
            }
    
            // Deletar a conexão no banco de dados
            $conexao->delete();
            Log::info('Conexão deletada com sucesso no banco de dados.', ['id' => $id]);
    
            return redirect()->route('app-whatsapp')->with('success', 'Item deletado com sucesso.');
    
        } catch (\Exception $e) {
            Log::error('Erro durante o processo de exclusão: ' . $e->getMessage(), [
                'exception' => $e,
                'conexao_id' => $id
            ]);
            return redirect()->route('app-whatsapp')->with('error', 'Ocorreu um erro durante o processo de exclusão.');
        }
    }

public function sendMessage(Request $request)
{
    Log::info('Preparando para enviar mensagem.');

    // Validação dos dados de entrada
    $request->validate([
        'phone' => 'required|string',
        'message' => 'required|string',
        'image' => 'nullable|string', // Caminho da imagem (opcional)
    ]);

    $phone = $request->input('phone');
    $message = $request->input('message');
    $image = $request->input('image'); // Caminho da imagem (opcional)
    $user = auth()->user();

    Log::info('Usuário autenticado: ' . $user->id);

    // Busca a conexão do usuário
    $conexao = Conexao::where('user_id', $user->id)->first();
    if (!$conexao) {
        Log::error('Conexão não encontrada para o usuário: ' . $user->id);
        return response()->json(['error' => 'Conexão não encontrada.'], 404);
    }

    $tokenid = $conexao->tokenid;

    // Obter a versão da API e configurações do banco de dados
    $apiVersion = 'v1'; // padrão
    $companyDetails = \DB::table('company_details')->where('user_id', 1)->first();
    if ($companyDetails) {
        if (isset($companyDetails->api_version)) {
            $apiVersion = $companyDetails->api_version;
        }
        // Obter URL e API key do banco de dados
        $this->urlapi = $companyDetails->api_url ?? $this->urlapi;
        $this->apikey = $companyDetails->api_key ?? $this->apikey;
    }

    // Limpa o número de telefone, removendo todos os caracteres não numéricos
    $celular = preg_replace('/\D/', '', $phone);

    // Verifica se o número já começa com o código do país (55 para Brasil)
    if (!str_starts_with($celular, '55')) {
        // Adiciona o código do país 55 para números brasileiros
        $celular = '55' . $celular;
    }

    // Verifica se o número tem o comprimento correto após a limpeza
    if (strlen($celular) < 12 || strlen($celular) > 13) {
        Log::error('Número de telefone inválido após limpeza: ' . $celular);
        return response()->json(['error' => 'Número de telefone inválido.'], 400);
    }

    Log::info('Número formatado: ' . $celular);

    try {
        // Define o corpo da requisição com base na versão da API
        if ($apiVersion === 'v2') {
            if ($image) {
                // Se houver imagem, usa o endpoint /message/sendMedia para v2
                $endpoint = '/message/sendMedia/Veetv_API' . $tokenid;
                $body = [
                    'number' => $celular,
                    'mediatype' => 'image',
                    'mimetype' => $this->getMimeType($image),
                    'caption' => $message,
                    'media' => $image,
                    'fileName' => basename($image)
                ];
            } else {
                // Se não houver imagem, usa o endpoint /message/sendText para v2
                $endpoint = '/message/sendText/Veetv_API' . $tokenid;
                $body = [
                    'number' => $celular,
                    'text' => $message
                ];
            }
        } else {
            // Versão v1 (padrão)
            $body = [
                'number' => $celular,
                'options' => [
                    'delay' => 1200,
                    'presence' => 'composing',
                ],
            ];

            if ($image) {
                // Se houver imagem, usa o endpoint /message/sendMedia para v1
                $endpoint = '/message/sendMedia/Veetv_API' . $tokenid;
                $body['mediaMessage'] = [
                    'mediatype' => 'image',
                    'caption' => $message,
                    'media' => $image,
                ];
            } else {
                // Se não houver imagem, usa o endpoint /message/sendText para v1
                $endpoint = '/message/sendText/Veetv_API' . $tokenid;
                $body['textMessage'] = [
                    'text' => $message,
                ];
            }
        }

        Log::info('Enviando requisição para a API ' . $apiVersion . ': ' . json_encode($body));

        // Envia a mensagem via API
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'apikey' => $this->apikey,
        ])->withOptions([
            'verify' => false,
        ])->post($this->urlapi . $endpoint, $body);

        $result = $response->json();
        Log::info('Resposta da API: ' . json_encode($result));

        // Tratamento da resposta baseado na versão da API
        if ($apiVersion === 'v2') {
            // Para v2, verificamos se a requisição foi bem sucedida
            if ($response->successful()) {
                Log::info('Mensagem enviada com sucesso (API v2)');
                return response()->json(['success' => 'Mensagem enviada com sucesso.']);
            } else {
                Log::error('Erro ao enviar mensagem (API v2): ' . json_encode($result));
                return response()->json([
                    'error' => 'Erro ao enviar mensagem.',
                    'api_error' => $result['message'] ?? 'Erro desconhecido'
                ], 500);
            }
        } else {
            // Para v1, verificamos o status como antes
            if (isset($result['status'])) {
                $status = $result['status'];
            
                if ($status === 'PENDING') {
                    Log::info('Mensagem enviada com sucesso, status: PENDING. ID: ' . $result['key']['id']);
                    return response()->json(['success' => 'Mensagem enviada com sucesso, aguardando entrega.']);
                } else {
                    Log::error('Erro ao enviar mensagem. Status inesperado: ' . $status);
                    return response()->json(['error' => 'Erro ao enviar mensagem.'], 500);
                }
            } else {
                Log::error('Erro ao enviar mensagem. Status não encontrado na resposta da API.');
                return response()->json(['error' => 'Erro ao enviar mensagem.'], 500);
            }
        }

    } catch (\Exception $e) {
        Log::error('Exceção ao enviar mensagem: ' . $e->getMessage());
        return response()->json(['error' => 'Erro ao enviar mensagem: ' . $e->getMessage()], 500);
    }
}

// Função auxiliar para detectar o tipo MIME
private function getMimeType($url)
{
    $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));
    
    $mimeTypes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
    ];

    return $mimeTypes[$extension] ?? 'image/jpeg';
}
}
