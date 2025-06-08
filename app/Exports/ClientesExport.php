<?php

namespace App\Exports;

use App\Models\Cliente;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ClientesExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        // Ordenar os resultados pelo campo user_id em ordem ascendente

        // verifica se o usuário é um administrador e exibe todos os clientes
        if (auth()->user()->role->name === 'admin') {
            return Cliente::orderBy('user_id', 'asc')->get([
                'id',
                'nome',
                'user_id',
                'whatsapp',
                'password',
                'vencimento',
                'servidor_id',
                'mac',
                'notificacoes',
                'plano_id',
                'numero_de_telas',
                'notas',
                'created_at',
                'updated_at'
            ]);
        }

        // verifica se o usuário é um administrador e exibe apenas os clientes do usuário autenticado
        return Cliente::where('user_id', auth()->id())->orderBy('user_id', 'asc')->get([
            'id',
            'nome',
            'user_id',
            'whatsapp',
            'password',
            'vencimento',
            'servidor_id',
            'mac',
            'notificacoes',
            'plano_id',
            'numero_de_telas',
            'notas',
            'created_at',
            'updated_at'
        ]);
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nome',
            'User ID',
            'WhatsApp',
            'Senha',
            'Vencimento',
            'Servidor ID',
            'MAC',
            'Notificações',
            'Plano ID',
            'Número de Telas',
            'Notas',
            'Criado em',
            'Atualizado em'
        ];
    }
}
