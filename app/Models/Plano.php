<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plano extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nome',
        'preco',
        'duracao',
        'plano_id',
        'id_qpanel',
        'user_id',
    ];

    /**
     * Get the user that owns the plano.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the clients for the plano.
     */
    public function clientes()
    {
        return $this->hasMany(Cliente::class);
    }
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

}
