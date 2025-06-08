<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conexao extends Model
{
    use HasFactory;

    protected $table = 'conexoes'; // Nome correto da tabela

    protected $fillable = [
        'user_id',
        'qrcode',
        'pairing_code',
        'formatted_pairing_code',
        'conn',
        'whatsapp',
        'data_cadastro',
        'data_alteracao',
        'tokenid',
        'notifica',
        'saudacao',
        'arquivo',
        'midia',
        'tipo',
    ];

    /**
     * Get the user that owns the connection.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
