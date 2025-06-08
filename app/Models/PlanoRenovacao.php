<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanoRenovacao extends Model
{
    use HasFactory;

    // Define a tabela associada ao modelo
    protected $table = 'planos_renovacao';

    // Campos que podem ser preenchidos em massa
    protected $fillable = [
        'nome',
        'descricao',
        'preco',
        'detalhes',
        'botao',    
        'limite',   
        'creditos', 
        'duracao',  
    ];

    public function users()
{
    return $this->hasMany(User::class, 'plano_id');
}
}
