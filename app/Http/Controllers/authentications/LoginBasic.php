<?php

namespace App\Http\Controllers\authentications;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Str;
use App\Http\Controllers\SendMessageController;
use Illuminate\Support\Facades\Cache;

class LoginBasic extends Controller
{

    public function index()
    {
        $pageConfigs = ['myLayout' => 'blank'];
        return view('content.authentications.auth-login-basic', ['pageConfigs' => $pageConfigs]);
    }

    public function login(Request $request)
{
    // Limpar caracteres não numéricos do número de WhatsApp
    $whatsapp = $request->input('whatsapp');

    // Verificar se o usuário excedeu o limite de tentativas
    $loginAttempts = Cache::get("login_attempts_{$whatsapp}", 0);
    if ($loginAttempts >= 5) {
        return response()->json(['errors' => ['whatsapp' => 'Você excedeu o número de tentativas de login. Tente novamente em 1 minuto.']], 429);
    }

    // Validação dos dados de entrada
    $validator = Validator::make($request->all(), [
        'whatsapp' => 'required|string',
        'password' => 'required|string',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // Substituir o valor do campo 'whatsapp' no request pelo valor limpo
    $request->merge(['whatsapp' => $whatsapp]);

    $credentials = $request->only('whatsapp', 'password');

    if (Auth::attempt($credentials)) {
        // Autenticação bem-sucedida
        $user = Auth::user();

        // Verificar se o 2FA está habilitado
        if ($user->two_factor_secret) {
            // Gerar um código de verificação
            $twoFactorCode = rand(100000, 999999);

            // Armazenar o código na sessão
            $request->session()->put('two_factor_code', $twoFactorCode);
            $request->session()->put('two_factor_user_id', $user->id);

            // Enviar o código via WhatsApp
            $this->sendWhatsAppMessage($user->whatsapp, "Seu código de verificação é: $twoFactorCode");

            // Deslogar o usuário temporariamente
            Auth::logout();

            // Retornar resposta JSON indicando que o 2FA é necessário
            return response()->json(['two_factor_required' => true]);
        }

        // Redefinir o contador de tentativas de login
        Cache::forget("login_attempts_{$whatsapp}");

        // Verificar se o status do usuário é "ativo"
        if ($user->status !== 'ativo') {
            Auth::logout();
            return response()->json(['errors' => ['whatsapp' => 'Seu login foi desativado. Contate seu administrador.']], 403);
        }

        // Redirecionar com base no papel do usuário
        return response()->json(['success' => true, 'redirect_url' => $this->redirectUserBasedOnRole($user)]);
    }

    // Incrementar o contador de tentativas de login
    Cache::put("login_attempts_{$whatsapp}", $loginAttempts + 1, now()->addMinute());

    // Autenticação falhou
    return response()->json(['errors' => ['whatsapp' => 'Credenciais inválidas.']], 401);
}

    public function verifyTwoFactor(Request $request)
    {
        // Validação do código de 2FA
        $validator = Validator::make($request->all(), [
            'two_factor_code' => 'required|integer',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        // Verificar o código de 2FA
        if ($request->session()->get('two_factor_code') == $request->two_factor_code) {
            // Código de 2FA válido
            $userId = $request->session()->get('two_factor_user_id');
            $user = User::find($userId);
    
            // Logar o usuário
            Auth::login($user);
    
            // Redefinir o contador de tentativas de login
            $request->session()->forget('login_attempts');
            $request->session()->forget('two_factor_code');
            $request->session()->forget('two_factor_user_id');
    
            // Redirecionar com base no papel do usuário
            return response()->json(['success' => true, 'redirect_url' => $this->redirectUserBasedOnRole($user)]);
        }
    
        // Código de 2FA inválido
        return response()->json(['errors' => ['two_factor_code' => 'Código de verificação inválido.']], 401);
    }

    private function sendWhatsAppMessage($phone, $message)
    {
        // Obter o usuário administrador
        $adminUser = User::where('role_id', 1)->first();
        if ($adminUser) {
            $this->sendMessage($phone, $message, $adminUser->id);
        }
    }

    private function sendMessage($phone, $message, $user_id)
    {
        // Usar o SendMessageController para enviar a mensagem
        $sendMessageController = new SendMessageController();
        $request = new Request([
            'phone' => $phone,
            'message' => $message,
            'user_id' => $user_id,
        ]);
        $sendMessageController->sendMessageWithoutAuth($request);
    }

    private function redirectUserBasedOnRole($user)
    {
        // Redirecionar com base no papel do usuário
        if ($user->role->name == 'admin') {
            return route('app-ecommerce-dashboard');
        } elseif ($user->role->name == 'master') {
            return route('app-ecommerce-dashboard');
        } elseif ($user->role->name == 'cliente') {
            return route('app-ecommerce-dashboard');
        } elseif ($user->role->name == 'revendedor') {
            return route('app-ecommerce-dashboard');
        } else {
            // Redirecionamento para uma página de acesso negado
            return route('access-denied');
        }
    }

    public function logout()
    {
        // Obtenha o usuário autenticado antes de deslogar
        $user = Auth::user();
    
        // Armazene o role_id do usuário antes de deslogar
        $roleId = $user ? $user->role_id : 3;
    
        // Deslogue o usuário
        Auth::logout();
    
        // Invalide a sessão atual
        request()->session()->invalidate();
    
        // Regenerate o token CSRF
        request()->session()->regenerateToken();
    
        // Redirecione adequadamente com base no role_id do usuário
        if ($roleId == 3) { // Supondo que 3 é o role_id para 'cliente'
            return redirect()->route('client.login.form');
        } else {
            return redirect()->route('auth-login-basic');
        }
    }

    public function checkSession()
    {
        // Verificar se o usuário está autenticado
        if (Auth::check()) {
            // Usuário está autenticado
            $user = Auth::user();
            $userId = $user->id;
            $userRole = $user->role->name;

            // Acessar dados da sessão
            $sessionData = Session::all();

            return response()->json([
                'authenticated' => true,
                'user_id' => $userId,
                'user_role' => $userRole,
                'session_data' => $sessionData,
            ]);
        } else {
            // Usuário não está autenticado
            return response()->json([
                'authenticated' => false,
                'message' => 'Usuário não está autenticado.',
            ]);
        }
    }
}