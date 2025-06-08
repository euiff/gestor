<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campanha extends Model
{
    use HasFactory;

protected $fillable = [
    'user_id',
    'nome',
    'horario',
    'contatos',
    'origem_contatos',
    'ignorar_contatos',
    'mensagem',
    'arquivo',
    'data',
    'enviar_diariamente',
    'ultima_execucao' // Adicione esta linha
];

protected $casts = [
    'contatos' => 'array',
    'ignorar_contatos' => 'boolean',
    'enviar_diariamente' => 'boolean',
    'data' => 'date', // Remova o formato para evitar problemas
    'ultima_execucao' => 'datetime' // Adicione este cast
];
    
    // Adicione este método à sua classe Campanha
// No model Campanha.php
public function getStatusAttribute()
{
    if ($this->enviar_diariamente) {
        return 'Recorrente';
    }

    if (!$this->data || !$this->horario) {
        return 'Pendente';
    }

    try {
        $now = now();
        $campanhaDate = \Carbon\Carbon::createFromFormat(
            'Y-m-d H:i',
            $this->data->format('Y-m-d') . ' ' . $this->horario
        );

        // Verifica se já foi executada hoje
        if ($this->ultima_execucao && $this->ultima_execucao->isToday()) {
            return 'Enviada';
        }

        return $campanhaDate->isPast() ? 'Enviada' : 'Agendada';
    } catch (\Exception $e) {
        return 'Pendente';
    }
}

    // Relacionamento com usuário
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function contatos()
{
    return $this->hasMany(Contato::class);
}

    // Mutator para garantir array vazio se null
    public function setContatosAttribute($value)
    {
        $this->attributes['contatos'] = $value ? json_encode($value) : json_encode([]);
    }
}