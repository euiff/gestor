<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use App\Models\Cliente;

class ClienteAuthController extends Controller
{
    public function showClientLoginForm()
    {
        $pageConfigs = ['myLayout' => 'blank'];
        return view('client.login', ['pageConfigs' => $pageConfigs]);
    }

    public function clientLogin(Request $request)
    {
        Log::debug('Iniciando processo de login do cliente', ['whatsapp' => $request->whatsapp]);
        
        $request->validate([
            'whatsapp' => 'required',
            'password' => 'required',
        ]);
    
        $cliente = Cliente::where('whatsapp', $request->whatsapp)->first();
        
        if (!$cliente) {
            Log::warning('Cliente não encontrado', ['whatsapp' => $request->whatsapp]);
            return redirect()->back()->withErrors(['whatsapp' => 'Credenciais inválidas']);
        }
        
        // Corrigido: usando Hash::check para verificar a senha
        if (Hash::check($request->password, $cliente->password)) {
            Log::debug('Senha válida - tentando autenticar', ['cliente_id' => $cliente->id]);
            
            Auth::guard('cliente')->login($cliente);
            
            if (Auth::guard('cliente')->check()) {
                Log::debug('Cliente autenticado com sucesso', ['cliente_id' => $cliente->id]);
                return redirect('/client/dashboard');
            } else {
                Log::error('Falha na autenticação - guard cliente não persistiu a sessão', ['cliente_id' => $cliente->id]);
                return redirect()->back()->withErrors(['whatsapp' => 'Falha no processo de autenticação']);
            }
        }
        
        Log::warning('Senha inválida', ['cliente_id' => $cliente->id]);
        return redirect()->back()->withErrors(['whatsapp' => 'Credenciais inválidas']);
    }
}