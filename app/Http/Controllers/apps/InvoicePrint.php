<?php

namespace App\Http\Controllers\apps;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cliente;
use App\Models\CompanyDetail;
use App\Models\Plano;
use App\Models\Pagamento;
use Illuminate\Support\Facades\Auth;

class InvoicePrint extends Controller
{
  public function index($payment_id)
  {
    $pageConfigs = ['myLayout' => 'blank'];
    $user = Auth::user();

    // Buscar a cobrança específica
    $payment = Pagamento::findOrFail($payment_id);

    // Buscar a empresa associada à cobrança
    $empresa = CompanyDetail::where('user_id', $payment->user_id)->firstOrFail();

    // Buscar o cliente associado à cobrança
    $cliente = Cliente::findOrFail($payment->cliente_id);

    // Buscar o plano associado ao cliente
    $plano = Plano::findOrFail($cliente->plano_id);

    $current_plan_id = $user->plano_id;

    // Obter os totais de pagamentos por status
    $totalPending = Pagamento::where('status', 'pending')->whereNotNull('cliente_id')->count();
    $totalCompleted = Pagamento::where('status', 'approved')->whereNotNull('cliente_id')->count();
    $totalFailed = Pagamento::where('status', 'cancelled')->whereNotNull('cliente_id')->count();

    return view('content.apps.app-invoice-print', [
      'pageConfigs' => $pageConfigs,
      'payment' => $payment,
      'cliente' => $cliente,
      'empresa' => $empresa,
      'plano' => $plano,
      'current_plan_id' => $current_plan_id,
      'totalPending' => $totalPending,
      'totalCompleted' => $totalCompleted,
      'totalFailed' => $totalFailed
    ]);
  }
}