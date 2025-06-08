<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Campanha extends Model
{
protected $casts = [
    'data' => 'date',
    'ultima_execucao' => 'datetime',
    'contatos' => 'array',
    'enviar_diariamente' => 'boolean',
    'ignorar_contatos' => 'boolean',
];

    // Método para obter os clientes (se necessário)
    public function clientes()
    {
        return $this->belongsToMany(Cliente::class, 'campanha_cliente', 'campanha_id', 'cliente_id');
    }
    
        public function getContatosCountAttribute()
    {
        if (is_array($this->contatos)) {
            return count($this->contatos);
        }
        return 0;
    }
        
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}