<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\indicacoes;
use Illuminate\Support\Facades\Log;

class SaldoController extends Controller
{
    public function getSaldoGanhos($userId)
    {
        try {
            $saldoGanhos = indicacoes::where('user_id', $userId)
                                    ->where('status', 'ativo')
                                    ->sum('ganhos');

            return response()->json([
                'success' => true,
                'saldo_ganhos' => $saldoGanhos
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar saldo de ganhos: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar saldo de ganhos.'
            ], 500);
        }
    }
}
