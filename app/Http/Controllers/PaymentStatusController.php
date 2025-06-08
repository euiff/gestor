<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Pagamento;

class PaymentStatusController extends Controller
{
    public function checkStatus($paymentId)
    {
        // Obter o pagamento específico com base no mercado_pago_id
        $pagamento = Pagamento::where('mercado_pago_id', $paymentId)->first();

        if ($pagamento) {
            return response()->json([
                'success' => true,
                'status' => $pagamento->status
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Pagamento não encontrado.'
            ], 404);
        }
    }
}
