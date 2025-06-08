<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pagamento;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;


class TransactionController extends Controller
{
  public function filter(Request $request)
  {
      $period = $request->query('period');
      $userId = $request->query('user_id');
      $query = Pagamento::query();

      // Filtrar por user_id e status 'approved'
      $query->where('user_id', $userId)
            ->where('status', 'approved');

      // Filtrar por período
      switch ($period) {
          case '28_days':
              $query->where('created_at', '>=', Carbon::now()->subDays(28));
              break;
          case 'last_month':
              $query->whereMonth('created_at', '=', Carbon::now()->subMonth()->month);
              break;
          case 'last_year':
              $query->whereYear('created_at', '=', Carbon::now()->subYear()->year);
              break;
          case '7_days':
              $query->where('created_at', '>=', Carbon::now()->subDays(7));
              break;
          default:
              return response()->json([]);
      }

      $pagamentos = $query->orderBy('created_at', 'desc')->take(5)->get(['user_id', 'valor', 'status', 'created_at', 'updated_at', 'mercado_pago_id']);
      // Converter valor para float e arredondar para duas casas decimais
      $pagamentos->transform(function($item) {
          $item->valor = round((float)$item->valor, 2);
          return $item;
      });

      return response()->json([
          'payments' => $pagamentos
      ]);
  }


  public function earningReports(Request $request)
  {
      if (Auth::check()) {
          // Usuário está autenticado
          $user = Auth::user();
          $userId = $user->id;
          $userRole = $user->role->name;

          // Verificar se o usuário é administrador
          if ($userRole === 'admin') {
              // Buscar vendas aprovadas
              $approvedSalesQuery = Pagamento::query()
                  ->where('status', 'approved')
                  ->where('created_at', '>=', Carbon::now()->subDays(7));
          } else {
              // Buscar vendas aprovadas para o usuário autenticado
              $approvedSalesQuery = Pagamento::query()
                  ->where('user_id', $userId)
                  ->where('status', 'approved')
                  ->where('created_at', '>=', Carbon::now()->subDays(7));
          }

          $approvedSales = $approvedSalesQuery->get(['user_id', 'valor', 'status', 'created_at', 'updated_at']);
          $approvedSales->transform(function($item) {
              $item->valor = round((float)$item->valor, 2);
              return $item;
          });
          $approvedSalesTotal = round($approvedSales->sum('valor'), 2);

          // Buscar todos os pagamentos (approved e pending)
          if ($userRole === 'admin') {
              $totalPaymentsQuery = Pagamento::query()
                  ->where('created_at', '>=', Carbon::now()->subDays(7));
          } else {
              $totalPaymentsQuery = Pagamento::query()
                  ->where('user_id', $userId)
                  ->where('created_at', '>=', Carbon::now()->subDays(7));
          }

          $totalPayments = $totalPaymentsQuery->get(['user_id', 'valor', 'status', 'created_at', 'updated_at']);
          $totalPayments->transform(function($item) {
              $item->valor = round((float)$item->valor, 2);
              return $item;
          });
          $totalPaymentsSum = round($totalPayments->sum('valor'), 2);

          // Adicionar logs detalhados para depuração
          \Log::info('Total Payments Data: ' . json_encode($totalPayments));

          // Agrupar ganhos por dia da semana
          $earnings = $approvedSales->groupBy(function($item) {
              $dayName = Carbon::parse($item->created_at)->locale('en')->dayOfWeek; // Usar formato de dia da semana em inglês
              \Log::info('Data: ' . $item->created_at . ' - Dia da Semana: ' . $dayName);
              return $dayName; // Agrupar por dia da semana em inglês
          })->map(function($day) {
              $sum = round($day->sum('valor'), 2);
              \Log::info('Soma do Dia: ' . $sum);
              return $sum;
          });

          // Garantir que todos os dias da semana estejam presentes
          $daysOfWeek = ['0' => 'Sun', '1' => 'Mon', '2' => 'Tue', '3' => 'Wed', '4' => 'Thu', '5' => 'Fri', '6' => 'Sat'];
          $earnings = collect($daysOfWeek)->mapWithKeys(function($day, $index) use ($earnings) {
              return [$day => $earnings->get($index, 0)];
          });

          // Adicionar logs para depuração
          \Log::info('Approved Sales Total: ' . $approvedSalesTotal);
          \Log::info('Total Payments Sum: ' . $totalPaymentsSum);
          \Log::info('Earnings: ' . json_encode($earnings));

          // Calcular o número total de pedidos
          $totalOrders = $totalPaymentsQuery->count();

          return response()->json([
              'approved_sales_total' => $approvedSalesTotal,
              'total_earnings' => $totalPaymentsSum,
              'total_orders' => $totalOrders,
              'earnings' => $earnings,
              'data' => [
                  [
                      'chart_data' => $totalOrders,
                      'active_option' => 0
                  ],
                  [
                      'chart_data' => $totalPaymentsSum,
                      'active_option' => 1
                  ],
                  [
                      'chart_data' => $approvedSalesTotal,
                      'active_option' => 2
                  ],
                  [
                      'chart_data' => $earnings->values()->all(),
                      'active_option' => 3
                  ]
              ]
          ]);
      } else {
          return response()->json(['error' => 'Usuário não autenticado'], 401);
      }
  }
}
