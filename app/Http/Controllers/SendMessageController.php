<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\Conexao;
use App\Models\CompanyDetail;
use App\Models\User;

class SendMessageController extends Controller
{
    private $apikey;
    private $urlapi;

    public function __construct()
    {
        // Obter o usuário administrador
        $adminUser = User::where('role_id', 1)->first();
        if ($adminUser) {
            // Obter os detalhes da empresa para o usuário administrador
            $companyDetail = CompanyDetail::where('user_id', $adminUser->id)->first();
            if ($companyDetail) {
                $this->apikey = $companyDetail->evolution_api_key;
                $this->urlapi = $companyDetail->evolution_api_url;
            }
        }
    }

    public function sendMessageWithoutAuth(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string',
            'user_id' => 'required|integer',
            'image' => 'nullable|string',
        ]);
    
        $phone = $request->input('phone');
        $message = $request->input('message');
        $user_id = $request->input('user_id');
        $image = $request->input('image');
    
        // Buscar a conexão do usuário
        $conexao = Conexao::where('user_id', $user_id)->first();
        if (!$conexao) {
            Log::error('Conexão não encontrada para user_id: ' . $user_id);
            return response()->json(['error' => 'Conexão não encontrada.'], 404);
        }
    
        // Obter a versão da API configurada para o usuário
        $apiVersion = 'v1'; // padrão
        $companyDetails = \DB::table('company_details')->where('user_id', $user_id)->first();
        if ($companyDetails && isset($companyDetails->api_version)) {
            $apiVersion = $companyDetails->api_version;
        }
    
        $tokenid = $conexao->tokenid;
    
        // Formatar número de telefone
        $celular = preg_replace('/[^0-9]/', '', $phone);
        if (!str_starts_with($celular, '55')) {
            $celular = '55' . $celular;
        }
        if (strlen($celular) < 12 || strlen($celular) > 13) {
            return response()->json(['error' => 'Número de telefone inválido.'], 400);
        }
    
        $maxRetries = 3;
        $attempt = 0;
        $lastError = null;
    
        do {
            $attempt++;
            try {
                Log::info("Tentativa $attempt de enviar mensagem para $celular (API $apiVersion)");
    
                // Estrutura base da requisição
                if ($apiVersion === 'v2') {
                    $requestData = ['number' => $celular];
                    
                    if (!empty($image)) {
                            // Remove a barra inicial se existir
                            $caminhoImagem = ltrim($image, '/');
                            
                            // Monta a URL completa dinamicamente
                            $imageUrl = str_starts_with($image, 'http') 
                                ? $image 
                                : rtrim(env('APP_URL'), '/') . '/' . $caminhoImagem;
                            
                            $endpoint = '/message/sendMedia/Veetv_API' . $tokenid;
                            
                            $requestData = array_merge($requestData, [
                                'mediatype' => 'image',
                                'mimetype' => $this->getMimeType($imageUrl), // Atualizado para usar a URL completa
                                'caption' => $message,
                                'media' => $imageUrl,
                                'fileName' => basename($caminhoImagem) // Usa o caminho original sem barras
                            ]);
                            
                            Log::info('Preparando envio de mídia com APP_URL dinâmico', [
                                'image_url' => $imageUrl,
                                'mimetype' => $requestData['mimetype'],
                                'filename' => $requestData['fileName']
                            ]);
                        } else {
                        $endpoint = '/message/sendText/Veetv_API' . $tokenid;
                        $requestData['text'] = $message;
                    }
                } else {
                    // Versão v1 (padrão)
                    $requestData = [
                        'number' => $celular,
                        'options' => [
                            'delay' => 1200,
                            'presence' => 'composing',
                        ],
                    ];
    
                    if (!empty($image)) {
                            // Remove a barra inicial se existir
                            $caminhoImagem = ltrim($image, '/');
                            
                            // Monta a URL completa dinamicamente
                            $imageUrl = str_starts_with($image, 'http') 
                                ? $image 
                                : rtrim(env('APP_URL'), '/') . '/' . $caminhoImagem;
                            
                            $endpoint = '/message/sendMedia/Veetv_API' . $tokenid;
                            $requestData['mediaMessage'] = [
                                'mediatype' => 'image',
                                'media' => $imageUrl,
                                'caption' => $message,
                            ];
                            
                            Log::info('Preparando envio de mídia', [
                                'endpoint' => $endpoint,
                                'image_url' => $imageUrl
                            ]);
                        } else {
                        $endpoint = '/message/sendText/Veetv_API' . $tokenid;
                        $requestData['textMessage'] = ['text' => $message];
                    }
                }
    
                // Enviar requisição com timeout configurado
                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'apikey' => $this->apikey,
                ])->withOptions([
                    'verify' => false,
                    'timeout' => 60, // 60 segundos de timeout
                    'connect_timeout' => 30, // 30 segundos para conexão
                ])->post($this->urlapi . $endpoint, $requestData);
    
                $result = $response->json();
                Log::info('Resposta completa da API:', $result);
    
                // Verificação baseada na versão da API
                if ($apiVersion === 'v2') {
                    if ($response->successful() && !isset($result['error'])) {
                        Log::info("Mensagem enviada com sucesso para $celular (API v2)");
                        return response()->json([
                            'success' => true,
                            'message' => 'Mensagem enviada com sucesso.',
                            'status' => 'sent'
                        ]);
                    }
                } else {
                    // Verificação para API v1
                    if ($response->successful()) {
                        $status = $result['status'] ?? null;
                        $messageId = $result['messageId'] ?? null;
    
                        if (in_array($status, ['200', 'PENDING', 'QUEUED', 'SENT']) || $messageId) {
                            Log::info("Mensagem enviada com sucesso para $celular (API v1)", [
                                'message_id' => $messageId,
                                'status' => $status
                            ]);
                            return response()->json([
                                'success' => true,
                                'message' => 'Mensagem enviada com sucesso.',
                                'message_id' => $messageId,
                                'status' => $status
                            ]);
                        }
                    }
                }
    
                $lastError = $result['error'] ?? $response->body() ?? 'Erro desconhecido';
                Log::warning("Tentativa $attempt falhou", ['error' => $lastError]);
    
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                $lastError = 'Timeout: ' . $e->getMessage();
                Log::warning("Timeout na tentativa $attempt para $celular");
            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                Log::error("Erro na tentativa $attempt: " . $e->getMessage());
            }
    
            if ($attempt < $maxRetries) {
                sleep(5 * $attempt); // Backoff exponencial
            }
        } while ($attempt < $maxRetries);
    
        Log::error("Falha ao enviar mensagem após $maxRetries tentativas", [
            'phone' => $celular,
            'last_error' => $lastError,
            'api_version' => $apiVersion
        ]);
    
        return response()->json([
            'error' => true,
            'message' => 'Falha ao enviar mensagem após várias tentativas.',
            'last_error' => $lastError
        ], 500);
    }
    
    // Função auxiliar para determinar o tipo MIME do arquivo
    private function getMimeType($filePath)
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        switch (strtolower($extension)) {
            case 'jpg':
            case 'jpeg':
                return 'image/jpeg';
            case 'png':
                return 'image/png';
            case 'gif':
                return 'image/gif';
            case 'pdf':
                return 'application/pdf';
            default:
                return 'application/octet-stream';
        }
    }
}