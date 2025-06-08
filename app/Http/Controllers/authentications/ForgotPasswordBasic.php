<?php

namespace App\Http\Controllers\authentications;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Str;
use App\Http\Controllers\SendMessageController;

class ForgotPasswordBasic extends Controller
{
    public function index()
    {
        $pageConfigs = ['myLayout' => 'blank'];
        return view('content.authentications.auth-forgot-password-basic', ['pageConfigs' => $pageConfigs]);
    }

    public function sendResetPassword(Request $request)
    {
        // Validação dos dados de entrada
        $validator = Validator::make($request->all(), [
            'whatsapp' => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Verificar se o usuário existe
        $user = User::where('whatsapp', $request->whatsapp)->first();

        if (!$user) {
            return redirect()->back()->withErrors(['whatsapp' => 'Número de WhatsApp não encontrado.'])->withInput();
        }

        // Gerar uma nova senha
        $newPassword = Str::random(8);

        // Atualizar a senha do usuário no banco de dados
        $user->password = Hash::make($newPassword);
        $user->save();

        // Enviar a nova senha para o usuário via WhatsApp
        $this->sendWhatsAppMessage($user->whatsapp, "Gestor Veetv: Sua nova senha de acesso é: $newPassword");

        return redirect()->route('auth-login-basic')->with('status', 'Nova senha enviada para seu WhatsApp.');
    }

    private function sendWhatsAppMessage($phone, $message)
    {
        // Obter o usuário administrador
        $adminUser = User::where('role_id', 1)->first();
        if ($adminUser) {
            $this->sendMessage($phone, $message, $adminUser->id);
        }
    }

    private function sendMessage($phone, $message, $user_id)
    {
        // Usar o SendMessageController para enviar a mensagem
        $sendMessageController = new SendMessageController();
        $request = new Request([
            'phone' => $phone,
            'message' => $message,
            'user_id' => $user_id,
        ]);
        $sendMessageController->sendMessageWithoutAuth($request);
    }
}