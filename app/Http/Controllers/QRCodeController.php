<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Conexao;

class QRCodeController extends Controller
{
    public function generateNewQRCode($user_id)
    {
        $conexao = Conexao::where('user_id', $user_id)->first();
        if ($conexao) {
            // Lógica para gerar um novo QR Code
            $newQRCode = 'data:image/png;base64,' . base64_encode(QrCode::format('png')->size(300)->generate('Novo QR Code'));

            // Atualizar o QR Code na base de dados
            $conexao->qrcode = $newQRCode;
            $conexao->save();

            return response()->json(['success' => true, 'qrcode' => $newQRCode]);
        }

        return response()->json(['success' => false]);
    }
}
