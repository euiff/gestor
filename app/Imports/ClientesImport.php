<?php

namespace App\Imports;

use App\Models\Cliente;
use App\Models\User;
use Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ClientesImport implements ToModel, WithHeadingRow
{
    protected $userId;

    public function __construct($userId = null)
    {
        $this->userId = $userId;
    }

    public function model(array $row)
    {
        // Buscar o usuário com base no ID do usuário fornecido no arquivo de importação
        $user = User::find($row['user_id']);

        // Verificar se o usuário foi encontrado
        if (!$user) {
            // Lançar exceção se o usuário não for encontrado
            throw new \Exception("Usuário não encontrado com ID: " . $row['user_id']);
        }

        // Verificar se a senha já está criptografada
        $password = $row['senha'];
        if (!Hash::needsRehash($password)) {
            // A senha já está criptografada
            $hashedPassword = $password;
        } else {
            // A senha não está criptografada, então criptografar
            $hashedPassword = Hash::make($password);
        }

        return new Cliente([
            'nome' => $row['nome'],
            'user_id' => $user->id,
            'whatsapp' => $row['whatsapp'],
            'password' => $hashedPassword,
            'vencimento' => $row['vencimento'],
            'servidor_id' => $row['servidor_id'],
            'mac' => $row['mac'],
            'notificacoes' => $row['notificacoes'] ?? 0, // Definindo um valor padrão se for nulo
            'plano_id' => $row['plano_id'] ?? 0, // Definindo um valor padrão se for nulo
            'numero_de_telas' => $row['numero_de_telas'],
            'notas' => $row['notas'] ?? '', // Definindo um valor padrão se for nulo
        ]);
    }
}
