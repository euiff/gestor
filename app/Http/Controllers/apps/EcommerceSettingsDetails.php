<?php

namespace App\Http\Controllers\apps;

use App\Models\CompanyDetail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\PlanoRenovacao;
use Illuminate\Support\Facades\DB;

class EcommerceSettingsDetails extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();
        $companyDetails = CompanyDetail::where('user_id', $user->id)->first();
        $planos_revenda = PlanoRenovacao::all();
        $current_plan_id = $user->plano_id;
        
        return view('content.apps.configuracoes', compact('companyDetails', 'planos_revenda', 'current_plan_id'));
    }

    public function store(Request $request)
    {
        $validatedData = $this->validateRequest($request);

        try {
            $data = $this->prepareCompanyData($validatedData);
            $companyDetails = CompanyDetail::create($data);
            
            $this->handleFileUploads($request, $companyDetails);
            $this->updateQpanelData($validatedData, $companyDetails);
            
            // Se for admin e alterou campos da API, atualiza todos os usuários
            if (Auth::user()->role_id === 1 && $this->apiFieldsChanged($validatedData)) {
                $this->updateAllUsersApiSettings($validatedData);
            }
            
            Log::info('Configurações criadas com sucesso', [
                'user_id' => Auth::id(),
                'company_id' => $companyDetails->id
            ]);
            
            return redirect()->back()->with('success', 'Configurações salvas com sucesso!');
        } catch (\Exception $e) {
            Log::error('Erro ao salvar configurações', [
                'error' => $e->getMessage(),
                'data' => $validatedData
            ]);
            return redirect()->back()->with('error', 'Erro ao salvar configurações: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $validatedData = $this->validateRequest($request);
    
        try {
            $companyDetails = CompanyDetail::findOrFail($id);
            $originalData = $companyDetails->toArray();
            
            $data = $this->prepareCompanyData($validatedData);
            $companyDetails->update($data);
            
            $this->handleFileUploads($request, $companyDetails);
            
            if (isset($validatedData['qpanel_username'])) {
                $this->updateQpanelData($validatedData, $companyDetails);
            }
            
            // Forçar atualização em massa para admin (removida a verificação de campos alterados)
            if (Auth::user()->role_id === 1) {
                Log::debug('Admin atualizou configurações - Forçando atualização em massa');
                $this->updateAllUsersApiSettings($validatedData);
            }
            
            return redirect()->back()->with('success', 'Configurações atualizadas com sucesso!');
            
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar configurações', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Erro ao atualizar configurações: ' . $e->getMessage());
        }
    }
    
    // Método auxiliar para registrar quais campos da API foram alterados
    private function getChangedApiFields(array $newData, array $originalData): array
    {
        $changes = [];
        $fields = ['evolution_api_url', 'evolution_api_key', 'api_version'];
        
        foreach ($fields as $field) {
            if (($newData[$field] ?? null) != ($originalData[$field] ?? null)) {
                $changes[$field] = [
                    'from' => $originalData[$field] ?? null,
                    'to' => $newData[$field] ?? null
                ];
            }
        }
        
        return $changes;
    }
    
    private function updateQpanelData(array $validatedData, CompanyDetail $companyDetails): void
    {
        try {
            $username = $validatedData['qpanel_username'];
            
            // 1. Primeiro salva o username na company_details
            $companyDetails->qpanel_username = $username;
            $companyDetails->save();
            
            // 2. Busca o ID no QPanel e atualiza na users
            $userData = $this->buscarUsuarioQPanel($username);
            
            if ($userData && isset($userData['id'])) {
                $user = User::find($companyDetails->user_id);
                
                if ($user) {
                    $user->id_qpanel = $userData['id'];
                    $user->save();
                    
                    Log::info('Dados do QPanel atualizados com sucesso', [
                        'user_id' => $user->id,
                        'qpanel_username' => $username,
                        'qpanel_id' => $userData['id'],
                        'company_details_id' => $companyDetails->id
                    ]);
                } else {
                    Log::error('Usuário principal não encontrado', [
                        'company_user_id' => $companyDetails->user_id
                    ]);
                }
            } else {
                Log::warning('Usuário QPanel não encontrado', [
                    'username' => $username,
                    'response' => $userData
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Falha ao atualizar dados do QPanel', [
                'company_id' => $companyDetails->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    private function apiFieldsChanged(array $newData, array $originalData = null): bool
    {
        $apiFields = ['evolution_api_url', 'evolution_api_key', 'api_version'];
        
        foreach ($apiFields as $field) {
            // Verifica se o campo existe nos novos dados E
            // (não há dados originais OU o valor é diferente)
            if (array_key_exists($field, $newData) && 
                (!isset($originalData)) || 
                ($newData[$field] != ($originalData[$field] ?? null))) {
                return true;
            }
        }
        
        return false;
    }
    
    private function updateAllUsersApiSettings(array $settings): void
    {
        try {
            $fields = [
                'evolution_api_url' => $settings['evolution_api_url'],
                'evolution_api_key' => $settings['evolution_api_key'],
                'api_version' => $settings['api_version']
            ];
    
            $affected = CompanyDetail::where('user_id', '!=', Auth::id())
                ->update($fields);
            
            Log::info('ATUALIZAÇÃO EM MASSA EXECUTADA', [
                'admin_id' => Auth::id(),
                'fields' => $fields,
                'affected_rows' => $affected
            ]);
            
        } catch (\Exception $e) {
            Log::error('Falha na atualização em massa', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function validateRequest(Request $request): array
    {
        return $request->validate([
            'company_name' => 'required|string|max:255',
            'company_whatsapp' => 'required|string|max:20',
            'access_token' => 'nullable|string|max:255',
            'company_logo_light' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'company_logo_dark' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'favicon' => 'nullable|image|mimes:ico,png|max:1024',
            'pix_manual' => 'nullable|string|max:255',
            'referral_balance' => 'nullable|numeric',
            'api_session' => 'nullable|string|max:255',
            'public_key' => 'nullable|string|max:255',
            'qpanel_username' => 'nullable|string|max:255',
            'site_id' => 'nullable|string|max:255',
            'evolution_api_url' => 'nullable|string|max:255',
            'evolution_api_key' => 'nullable|string|max:255',
            'api_version' => 'nullable|string|in:v1,v2', // Adicionado validação para api_version
            'qpanel_api_url' => 'nullable|string|max:255',
            'qpanel_api_key' => 'nullable|string|max:255',
            'not_gateway' => 'nullable|boolean',
            'notification_url' => 'nullable|string|max:255',
        ]);
    }

    private function prepareCompanyData(array $validatedData): array
        {
            $data = [
                'user_id' => Auth::id(),
                'company_name' => $validatedData['company_name'],
                'company_whatsapp' => $validatedData['company_whatsapp'],
                'access_token' => $validatedData['access_token'] ?? null,
                'pix_manual' => $validatedData['pix_manual'] ?? null,
                'not_gateway' => $validatedData['not_gateway'] ?? false,
                'api_session' => $validatedData['api_session'] ?? null,
                'qpanel_username' => $validatedData['qpanel_username'] ?? null,
            ];
    
            if (Auth::user()->role_id === 1) {
                $adminFields = [
                    'referral_balance' => $validatedData['referral_balance'] ?? null,
                    'public_key' => $validatedData['public_key'] ?? null,
                    'site_id' => $validatedData['site_id'] ?? null,
                    'evolution_api_url' => $validatedData['evolution_api_url'] ?? null,
                    'evolution_api_key' => $validatedData['evolution_api_key'] ?? null,
                    'api_version' => $validatedData['api_version'] ?? 'v1', // Valor padrão 'v1'
                    'qpanel_api_url' => $validatedData['qpanel_api_url'] ?? null,
                    'qpanel_api_key' => $validatedData['qpanel_api_key'] ?? null,
                    'notification_url' => $validatedData['notification_url'] ?? null,
                ];
                $data = array_merge($data, $adminFields);
            }
    
            return $data;
        }

    private function handleFileUploads(Request $request, CompanyDetail $companyDetails): void
    {
        try {
            $this->handleLogoUpload($request, 'company_logo_light', $companyDetails);
            $this->handleLogoUpload($request, 'company_logo_dark', $companyDetails);
            $this->handleFaviconUpload($request, $companyDetails);
        } catch (\Exception $e) {
            Log::error('Erro ao processar uploads de arquivos', [
                'company_id' => $companyDetails->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function handleLogoUpload(Request $request, string $field, CompanyDetail $companyDetails): void
    {
        if ($request->hasFile($field)) {
            $directory = public_path('assets/img/logos');
            $this->createDirectoryIfNotExists($directory);

            $this->deleteOldFile($companyDetails->$field);

            $fileName = time().'_'.$request->file($field)->getClientOriginalName();
            $path = $request->file($field)->move($directory, $fileName);
            
            if ($path) {
                $companyDetails->$field = '/assets/img/logos/'.$fileName;
                $companyDetails->save();
                Log::info('Logo atualizado', [
                    'company_id' => $companyDetails->id,
                    'field' => $field,
                    'file' => $fileName
                ]);
            }
        }
    }

    private function handleFaviconUpload(Request $request, CompanyDetail $companyDetails): void
    {
        if ($request->hasFile('favicon')) {
            $directory = public_path('assets/img/favicons');
            $this->createDirectoryIfNotExists($directory);

            $this->deleteOldFile($companyDetails->favicon);

            $fileName = time().'_'.$request->file('favicon')->getClientOriginalName();
            $path = $request->file('favicon')->move($directory, $fileName);
            
            if ($path) {
                $companyDetails->favicon = '/assets/img/favicons/'.$fileName;
                $companyDetails->save();
                Log::info('Favicon atualizado', [
                    'company_id' => $companyDetails->id,
                    'file' => $fileName
                ]);
            }
        }
    }

    private function createDirectoryIfNotExists(string $directory): void
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
            chmod($directory, 0777);
        }
    }

    private function deleteOldFile(?string $filePath): void
    {
        if ($filePath && file_exists(public_path($filePath))) {
            unlink(public_path($filePath));
        }
    }

    private function buscarUsuarioQPanel(string $username): ?array
    {
        try {
            // Busca as credenciais do QPanel do admin (user_id = 1)
            $adminCredentials = CompanyDetail::where('user_id', 1)->first();
            
            if (!$adminCredentials || !$adminCredentials->qpanel_api_url || !$adminCredentials->qpanel_api_key) {
                Log::error('Credenciais do QPanel não configuradas no sistema', ['username' => $username]);
                return null;
            }
    
            $curl = curl_init();
    
            // Monta a URL completa com o endpoint correto para usuários
            $urlCompleta = rtrim($adminCredentials->qpanel_api_url, '/') . '/api/webhook/user?username=' . urlencode($username);
    
            curl_setopt_array($curl, [
                CURLOPT_URL => $urlCompleta,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $adminCredentials->qpanel_api_key,
                    'Accept: application/json'
                ],
            ]);
    
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlError = curl_error($curl);
            curl_close($curl);
    
            if ($curlError) {
                throw new \Exception('Erro cURL: ' . $curlError);
            }
    
            if ($httpCode !== 200) {
                throw new \Exception('HTTP Status: ' . $httpCode);
            }
    
            $data = json_decode($response, true);
            
            Log::debug('Resposta completa da API QPanel', [
                'username' => $username,
                'response' => $data
            ]);
    
            // Retorna o primeiro usuário encontrado no array 'data'
            return $data['data'][0] ?? null;
            
        } catch (\Exception $e) {
            Log::error('Erro na requisição ao QPanel', [
                'username' => $username,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    public function destroy($id)
    {
        try {
            $companyDetail = CompanyDetail::findOrFail($id);
            $this->deleteCompanyFiles($companyDetail);
            $companyDetail->delete();
            
            Log::info('Configurações removidas', ['company_id' => $id]);
            return redirect()->back()->with('success', 'Configurações removidas com sucesso!');
        } catch (\Exception $e) {
            Log::error('Erro ao remover configurações', [
                'company_id' => $id,
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', 'Erro ao remover configurações: ' . $e->getMessage());
        }
    }

    private function deleteCompanyFiles(CompanyDetail $companyDetails): void
    {
        $this->deleteOldFile($companyDetails->company_logo_light);
        $this->deleteOldFile($companyDetails->company_logo_dark);
        $this->deleteOldFile($companyDetails->favicon);
    }
}