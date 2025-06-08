<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'whatsapp',
        'password',
        'role_id', // Adicionado o campo role_id
        'trial_ends_at', // Adicionado o campo trial_ends_at
        'profile_photo_url', // Adicionado o campo profile_photo_url
        'plano_id', // Adicionado o campo plano_id
        'status', // Adicionado o campo status
        'limite', // Adicionado o campo limite
        'creditos', // Adicionado o campo creditos
        'user_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'trial_ends_at' => 'datetime', // Adicionado o cast para datetime
    ];

    /**
     * Define a relação com o modelo Role.
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function clientes()
    {
        return $this->hasMany(Cliente::class);
    }

    public function servidores()
    {
        return $this->hasMany(Servidor::class);
    }

    /**
     * Define a relação com o modelo Indicacao.
     */
    public function indicacoes()
    {
        return $this->hasMany(Indicacao::class, 'user_id');
    }

    public function indicados()
    {
        return $this->hasMany(Indicacao::class, 'referred_id');
    }

    public function plano()
    {
        return $this->belongsTo(PlanoRenovacao::class, 'plano_id');
    }

    public function parent()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function userData()
    {
        return $this->hasOne(UserData::class);
    }

    public function isClient()
    {
        return $this->role && $this->role->name === 'cliente'; // Verifica se o papel é 'cliente'
    }

    /**
     * Define a relação com o modelo CompanyDetail.
     */
    public function companyDetail()
    {
        return $this->hasOne(CompanyDetail::class, 'user_id');
    }

    /**
     * Ativa a autenticação de dois fatores para o usuário.
     */
    public function enableTwoFactor()
    {
        $this->two_factor_secret = Str::random(16);
        $this->two_factor_recovery_codes = json_encode(array_map(function () {
            return Str::random(10);
        }, range(1, 8)));
        $this->save();
    }

    /**
     * Desativa a autenticação de dois fatores para o usuário.
     */
    public function disableTwoFactor()
    {
        $this->two_factor_secret = null;
        $this->two_factor_recovery_codes = null;
        $this->two_factor_confirmed_at = null;
        $this->save();
    }

    /**
     * Verifica se a autenticação de dois fatores está ativada.
     */
    public function hasTwoFactorEnabled()
    {
        return !is_null($this->two_factor_secret);
    }



    public function userPreferences()
    {
        return $this->hasMany(UserClientPreference::class);
    }
}