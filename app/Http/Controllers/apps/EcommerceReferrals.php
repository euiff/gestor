<?php

namespace App\Http\Controllers\apps;

use App\Http\Controllers\Controller;
use App\Models\indicacoes;
use App\Models\User;
use App\Models\CompanyDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\PlanoRenovacao;
class EcommerceReferrals extends Controller
{
  public function index()
  {
    if (Auth::check()) {
      // Usuário está autenticado
      $user = Auth::user();
      $userId = $user->id;
      $userRole = $user->role->name;

      // Obter o referral_balance do administrador
      $adminCompanyDetail = CompanyDetail::whereHas('user', function($query) {
        $query->where('role_id', 1); // Papel de administrador
      })->first();
      $referralBalance = $adminCompanyDetail->referral_balance ?? 0;

      // Verificar se o usuário é administrador
      if ($userRole === 'admin') {
        // Administrador vê todas as indicações
        $indicacoes = indicacoes::with(['user', 'referred'])->get();
        $totalGanhos = indicacoes::sum('ganhos');
        $ganhosNaoPagos = indicacoes::where('status', 'pending')->sum('ganhos');
      } else {
        // Usuário comum vê apenas suas próprias indicações
        $indicacoes = indicacoes::with(['user', 'referred'])->where('user_id', $userId)->get();
        $totalGanhos = indicacoes::where('user_id', $userId)->sum('ganhos');
        $ganhosNaoPagos = indicacoes::where('user_id', $userId)->where('status', 'pending')->sum('ganhos');
      }

      $planos_revenda = PlanoRenovacao::all();
      $current_plan_id = $user->plano_id;

      return view('content.apps.app-ecommerce-referrals', compact('indicacoes', 'totalGanhos', 'ganhosNaoPagos', 'referralBalance', 'planos_revenda', 'current_plan_id'));
    } else {
      // Redirecionar para a página de login se o usuário não estiver autenticado
      return redirect()->route('auth-login-basic');
    }
  }

  public function create(Request $request)
  {
    $request->validate([
      'user_id' => 'required|exists:users,id',
      'referred_id' => 'required|exists:users,id',
    ]);

    $indicacoes = indicacoes::create([
      'user_id' => $request->user_id,
      'referred_id' => $request->referred_id,
      'status' => 'pending',
      'ganhos' => 5, // Valor fixo de R$ 5 por indicação
    ]);

    return response()->json($indicacoes, 201);
  }
}
