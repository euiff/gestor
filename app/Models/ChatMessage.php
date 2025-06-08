<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'message',
        'user_id',
        'cliente_id',
        'sender',
    ];

    // Relacionamento com o usuário
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relacionamento com o cliente
    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function isClient()
    {
        return $this->role && $this->role->name === 'cliente'; // Verifica se o papel é 'cliente'
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}