<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    use HasFactory;

   protected $fillable = [
    'user_id',
    'nome',
    'finalidade',
    'conteudo',
    'tipo_mensagem', // Adicionado
    'imagem', // Adicionado
];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
