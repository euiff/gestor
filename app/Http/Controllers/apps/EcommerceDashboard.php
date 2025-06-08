<?php
namespace App\Http\Controllers\apps;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\Pagamento; // Importar o modelo Pagamento
use App\Models\Cliente; // Importar o modelo Cliente
use Carbon\Carbon; // Importar Carbon para manipulação de datas
use Illuminate\Support\Facades\Log;

use App\Models\PlanoRenovacao;

class EcommerceDashboard extends Controller
{
  public function __construct()
  {
    // Aplicar middleware de autenticação
    $this->middleware('auth');
  }


  public function index()
  {
      // Verificar se o usuário está autenticado
      if (Auth::check()) {
          // Usuário está autenticado
          $user = Auth::user();
          $userId = $user->id;
          $userRole = $user->role->name;

          // Verificar se o usuário é administrador
          if ($userRole === 'admin') {
              // Buscar as 5 últimas transações pagas
              $pagamentos = Pagamento::where('status', 'approved')
                  ->orderBy('created_at', 'desc')
                  ->take(5)
                  ->get();

              // Buscar todos os clientes
              $clientes = Cliente::all();
          } else {
              // Buscar transações pagas do usuário autenticado
              $pagamentos = Pagamento::where('user_id', $userId)
                  ->where('status', 'approved')
                  ->orderBy('created_at', 'desc')
                  ->take(5)
                  ->get();

              // Buscar dados dos clientes do usuário autenticado
              $clientes = Cliente::where('user_id', $userId)->get();
          }

          // Calcular estatísticas dos clientes
          $totalClientes = $clientes->count();
          $inadimplentes = $clientes->filter(function ($cliente) {
              return Carbon::parse($cliente->vencimento)->isBefore(Carbon::today());
          })->count(); // Clientes com vencimento anterior à data atual
          $ativos = $clientes->filter(function ($cliente) {
              $vencimento = Carbon::parse($cliente->vencimento);
              return $vencimento->isAfter(Carbon::today()) || $vencimento->isSameDay(Carbon::today());
          })->count(); // Clientes com vencimento igual ou posterior à data atual
          $expiramHoje = $clientes->where('vencimento', Carbon::today()->format('Y-m-d'))->count();

          // Calcular o cliente com mais pagamentos pagos
          $clienteMaisCompras = null;
          $totalComprasClienteMaisCompras = 0;
          $clienteMaisComprasData = Pagamento::select('cliente_id')
              ->whereNotNull('cliente_id')
              ->where('status', 'approved')
              ->when($userRole !== 'admin', function ($query) use ($userId) {
                  return $query->where('user_id', $userId);
              })
              ->selectRaw('COUNT(*) as total, SUM(valor) as total_valor')
              ->groupBy('cliente_id')
              ->orderByDesc('total')
              ->first();

          if ($clienteMaisComprasData) {
              $clienteMaisCompras = Cliente::find($clienteMaisComprasData->cliente_id);
              $totalComprasClienteMaisCompras = $clienteMaisComprasData->total_valor;
          }

          $planos_revenda = PlanoRenovacao::all();

          // Acessar dados da sessão
          $sessionData = Session::all();

          return view('content.apps.app-ecommerce-dashboard', [
              'user_id' => $userId,
              'user_role' => $userRole,
              'session_data' => $sessionData,
              'pagamentos' => $pagamentos, // Passar as transações para a view
              'totalClientes' => $totalClientes,
              'inadimplentes' => $inadimplentes,
              'ativos' => $ativos,
              'expiramHoje' => $expiramHoje,
              'clienteMaisCompras' => $clienteMaisCompras, // Passar o cliente com mais compras para a view
              'totalComprasClienteMaisCompras' => $totalComprasClienteMaisCompras, // Passar o valor total das compras do cliente
              'planos_revenda' => $planos_revenda,
              'current_plan_id' => $user->plano_id,
          ]);
      } else {
          return redirect()->route('auth-login-basic');
      }
  }
}
