<?php

namespace App\Http\Controllers\apps;

use App\Http\Controllers\Controller;
use App\Models\Pagamento;
use App\Models\Cliente;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\PlanoRenovacao;

class EcommerceOrderList extends Controller
{
    public function __construct()
    {
        // Aplicar middleware de autenticação
        $this->middleware('auth');
    }

    
  public function index(Request $request)
  {
      if (Auth::check()) {
          // Usuário está autenticado
          $user = Auth::user();
          $userId = $user->id;
          $userRole = $user->role->name;
  
          // Buscar todos os clientes
          $clientes = Cliente::all()->keyBy('id');
  
          // Buscar todos os usuários
          $users = User::all()->keyBy('id');
  
          // Calcular os totais de pedidos
          if ($userRole === 'admin') {
              $totalPending = Pagamento::where('status', 'pending')->count();
              $totalCompleted = Pagamento::where('status', 'approved')->count();
              $totalFailed = Pagamento::where('status', 'cancelled')->count();
          } else {
              $totalPending = Pagamento::where('status', 'pending')->where('user_id', $userId)->count();
              $totalCompleted = Pagamento::where('status', 'approved')->where('user_id', $userId)->count();
              $totalFailed = Pagamento::where('status', 'cancelled')->where('user_id', $userId)->count();
          }
  
          $planos_revenda = PlanoRenovacao::all();
          $current_plan_id = $user->plano_id;
  
          return view('content.apps.ordens', compact('clientes', 'users', 'planos_revenda', 'current_plan_id', 'totalPending', 'totalCompleted', 'totalFailed'));
      } else {
          // Redirecionar para a página de login se o usuário não estiver autenticado
          return redirect()->route('auth-login-basic');
      }
  }
  
    
    
     
   
  
                 public function list(Request $request)
              {
                  Log::info('Acessando a listagem de pedidos com paginação e busca.');
              
                  try {
                      if (Auth::check()) {
                          $user = Auth::user();
                          $userRole = $user->role->name;
              
                          $search = $request->input('search');
                          $sort = $request->input('sort', 'id');
                          $order = $request->input('order', 'DESC');
                          $clienteId = $request->input('order_id');
              
                          // Mapeamento de status em português para inglês
                          $statusMap = [
                              'pendente' => 'pending',
                              'aprovado' => 'approved',
                              'cancelado' => 'cancelled'
                          ];
              
                          // Verifica se o usuário é um administrador
                          if ($userRole === 'admin') {
                              $filter = $request->input('filter', 'all');
              
                              if ($filter == 'mine') {
                                  // Mostrar apenas os pedidos do administrador
                                  $orders = Pagamento::where('user_id', $user->id);
                              } else {
                                  // Mostrar todos os pedidos
                                  $orders = Pagamento::query();
                              }
                          } else {
                              // Retorna apenas os dados do usuário logado se não for administrador
                              $orders = Pagamento::where('user_id', $user->id);
                          }
              
                          if ($clienteId) {
                              $orders = $orders->where('cliente_id', $clienteId);
                          }
              
                          if ($search) {
                              $search = strtolower($search);
                              $search = $statusMap[$search] ?? $search;
              
                              $orders = $orders->where(function ($query) use ($search) {
                                  $query->where('mercado_pago_id', 'like', '%' . $search . '%')
                                        ->orWhere('status', 'like', '%' . $search . '%')
                                        ->orWhereHas('cliente', function ($q) use ($search) {
                                            $q->where('nome', 'like', '%' . $search . '%');
                                        });
                              });
                          }
              
                          $totalOrders = $orders->count();
                          $canEdit = true; // Defina a lógica para verificar se o usuário pode editar
                          $canDelete = true; // Defina a lógica para verificar se o usuário pode deletar
              
                          $orders = $orders->orderBy($sort, $order)
                              ->paginate($request->input('limit', 10))
                              ->through(function ($order) use ($canEdit, $canDelete) {
                                                                 $actions = '<div class="d-grid gap-3">
                                              <div class="row g-3">
                                                  <div class="col-6 mb-2">
                                                      <form action="' . route('app-ecommerce-order-destroy', $order->id) . '" method="POST" style="display:inline;">
                                                          ' . csrf_field() . '
                                                          ' . method_field('DELETE') . '
                                                          <button type="submit" class="btn btn-sm btn-danger w-100" data-bs-toggle="tooltip" data-bs-placement="top" title="Deletar">
                                                              <i class="fas fa-trash-alt"></i>
                                                          </button>
                                                      </form>
                                                  </div>
                                                  <div class="col-6 mb-2">
                                                      <a href="' . route('app-ecommerce-order-details', ['order_id' => $order->id]) . '" class="btn btn-primary w-100" data-bs-toggle="tooltip" data-bs-placement="top" title="Ver Pagamentos">
                                                          <i class="fas fa-eye"></i>
                                                      </a>
                                                  </div>
                                              </div>
                                          </div>';
              
                                  // Tradução dos status
                                  $statusBadge = '';
                                  switch ($order->status) {
                                      case 'pending':
                                          $statusBadge = '<span class="badge bg-warning">Pendente</span>';
                                          break;
                                      case 'approved':
                                          $statusBadge = '<span class="badge bg-success">Aprovado</span>';
                                          break;
                                      case 'cancelled':
                                          $statusBadge = '<span class="badge bg-danger">Cancelado</span>';
                                          break;
                                      default:
                                          $statusBadge = '<span class="badge bg-secondary">' . ucfirst($order->status) . '</span>';
                                          break;
                                  }
              
                                  return [
                                      'id' => $order->id,
                                      'cliente_id' => $order->cliente_id,
                                      'user_id' => $order->user_id,
                                      'plano_id' => $order->plano_id,
                                      'isAnual' => $order->isAnual,
                                      'mercado_pago_id' => $order->mercado_pago_id,
                                      'valor' => $order->valor,
                                      'status' => $statusBadge,
                                      'notified' => $order->notified,
                                      'credito_id' => $order->credito_id,
                                      'created_at' => $order->created_at->format('d/m/Y H:i:s'),
                                      'updated_at' => $order->updated_at->format('d/m/Y H:i:s'),
                                      'payment_date' => $order->payment_date,
                                      'use_saldo_ganhos' => $order->use_saldo_ganhos,
                                      'cliente_nome' => $order->cliente ? $order->cliente->nome : 'N/A',
                                      'user_name' => $order->user ? $order->user->name : 'N/A', // Verifica se o usuário existe
                                      'actions' => $actions 
                                  ];
                              });
              
                          // Fetch user preferences for visible columns
                          $userId = $user->id;
                          $preferences = DB::table('user_client_preferences')
                              ->where('user_id', $userId)
                              ->where('table_name', 'orders')
                              ->value('visible_columns');
              
                          $visibleColumns = json_decode($preferences, true) ?: [
                              'id',
                              'cliente_id',
                              'user_id',
                              'plano_id',
                              'isAnual',
                              'mercado_pago_id',
                              'valor',
                              'status',
                              'notified',
                              'credito_id',
                              'created_at',
                              'updated_at',
                              'payment_date',
                              'use_saldo_ganhos',
                              'cliente_nome',
                              'user_name', // Adiciona o nome do usuário às colunas visíveis
                              'actions'
                          ];
              
                          // Filter the columns based on user preferences
                          $filteredOrders = $orders->map(function ($order) use ($visibleColumns) {
                              return array_filter($order, function ($key) use ($visibleColumns) {
                                  return in_array($key, $visibleColumns);
                              }, ARRAY_FILTER_USE_KEY);
                          });
              
                          // Adicionar dados adicionais que eram retornados no método index
                          $planos_revenda = PlanoRenovacao::all();
                          $current_plan_id = $user->plano_id;
                          $users = User::all();
              
                          return response()->json([
                              'rows' => $filteredOrders,
                              'total' => $totalOrders,
                              'orders' => $orders,
                              'planos_revenda' => $planos_revenda,
                              'current_plan_id' => $current_plan_id,
                              'users' => $users
                          ]);
                      } else {
                          // Usuário não está autenticado
                          return response()->json(['error' => 'Usuário não autenticado'], 401);
                      }
                  } catch (\Exception $e) {
                      Log::error('Erro ao acessar a listagem de pedidos: ' . $e->getMessage());
                      return response()->json(['error' => 'Erro ao acessar a listagem de pedidos'], 500);
                  }
              }
public function update(Request $request, $order_id)
{
    $order = Pagamento::findOrFail($order_id);

    $request->validate([
        'nome' => 'required|string|max:255',
        'preco' => 'required|numeric',
        'duracao' => 'required|integer',
    ]);

    $order->update($request->only(['nome', 'preco', 'duracao']));

    return redirect()->route('app-ecommerce-order-list')->with('success', 'Pedido atualizado com sucesso.');
}

    public function destroy($order_id)
    {
        $order = Pagamento::findOrFail($order_id);
        $order->delete();

        return redirect()->route('app-ecommerce-order-list')->with('success', 'Ordem excluída com sucesso.');
    }

    public function destroyMultiple(Request $request)
    {
        $request->validate([
            'order_ids' => 'required|array',
            'order_ids.*' => 'exists:pagamentos,id',
        ]);

        try {
            Pagamento::whereIn('id', $request->order_ids)->delete();
            return response()->json(['error' => false, 'message' => 'Pedidos excluídos com sucesso.']);
        } catch (\Exception $e) {
            return response()->json(['error' => true, 'message' => 'Erro ao excluir pedidos: ' . $e->getMessage()]);
        }
    }
}