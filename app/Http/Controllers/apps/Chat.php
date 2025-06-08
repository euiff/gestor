<?php

namespace App\Http\Controllers\apps;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PlanoRenovacao;
use App\Models\Revenda;
use App\Models\ChatMessage;
use App\Models\Cliente;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Plano;

class Chat extends Controller
{

    public function index()
    {
        if (Auth::check()) {
            $user = Auth::user();
            Log::info('Authenticated User: ' . $user);
    
            if ($user && $user->role) {
                $userId = $user->id;
                $userRole = $user->role->name;
                Log::info('User ID: ' . $userId);
                Log::info('User Role: ' . $userRole);
    
                if ($userRole === 'admin') {
                    $planos_revenda = PlanoRenovacao::all(); // Obtenha todos os planos de revenda
                    $current_plan_id = $user->plano_id;
                    $rendas_creditos = Revenda::all();
                    $clientes = Cliente::where('user_id', $user->id)->get(); // Obtenha os clientes do usuário logado
                    Log::info('Admin: Retrieved all planos_revenda, rendas_creditos, and clientes');
                } else {
                    $rendas_creditos = Revenda::where('user_id', $userId)->get();
                    $clientes = Cliente::where('user_id', $user->id)->get(); // Obtenha os clientes do usuário logado
                    $planos_revenda = PlanoRenovacao::all(); 
                    $current_plan_id = $user->plano_id;
                }
    
                return view('chat.app-chat', compact('user', 'clientes', 'planos_revenda', 'rendas_creditos', 'current_plan_id'));
            }
        } elseif (Auth::guard('cliente')->check()) {
            $cliente = Auth::guard('cliente')->user(); // Obter o cliente autenticado
            Log::info('Cliente autenticado: ' . $cliente);
    
            // Verificar se o cliente não é nulo antes de tentar acessar suas propriedades
            if ($cliente) {
                $userRole = $cliente->role_id;
                Log::info('Cliente Role: ' . $userRole);
    
                $planos_revenda = PlanoRenovacao::all();
    
                $cliente->plano = Plano::find($cliente->plano_id);
                $clientes = User::whereHas('clientes', function($query) use ($cliente) {
                    $query->where('id', $cliente->id);
                })->get(); // Obtenha os usuários que estão conversando com o cliente
                Log::info('Cliente Plano: ' . $cliente);
    
                return view('chat.app-chat', compact('cliente', 'clientes', 'planos_revenda'));
            } else {
                Log::warning('Cliente autenticado é nulo');
            }
        }
    
        Log::warning('No authenticated user found');
        return redirect()->route('login');
    }
    public function fetchMessages($cliente_id)
    {
        $messages = ChatMessage::where('cliente_id', $cliente_id)
            ->orWhere('user_id', $cliente_id)
            ->get();

        return response()->json($messages);
    }


    public function sendMessage(Request $request)
    {
        // Validação da requisição
        $request->validate([
            'message' => 'required|string',
            'cliente_id' => 'required|integer',
        ]);
    
        try {
            // Inicializa as variáveis
            $user_id = null;
            $sender = null;
    
            // Verifica se o usuário está autenticado
            if (Auth::check()) {
                $sender = 'user';
                $user_id = Auth::id();
            } elseif (Auth::guard('cliente')->check()) {
                $sender = 'cliente';
                $cliente_id = Auth::guard('cliente')->id();
                // Busca o user_id do cliente na tabela clientes
                $cliente = Cliente::find($cliente_id);
                if ($cliente) {
                    $user_id = $cliente->user_id;
                } else {
                    return response()->json(['error' => 'Cliente não encontrado'], 404);
                }
            } else {
                return response()->json(['error' => 'Usuário não autenticado'], 401);
            }
    
            $cliente_id = $request->cliente_id; // Mantenha o cliente_id como fornecido na requisição
    
            $message = ChatMessage::create([
                'user_id' => $user_id,
                'cliente_id' => $cliente_id,
                'message' => $request->message,
                'sender' => $sender,
            ]);
    
            return response()->json($message, 201); // Retornar 201 para criação bem-sucedida
        } catch (\Exception $e) {
            // Log do erro
            Log::error('Erro ao enviar mensagem: ' . $e->getMessage());
    
            return response()->json(['error' => 'Erro ao enviar mensagem'], 500);
        }
    }
  }
