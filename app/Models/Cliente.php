<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Auth\Authenticatable;

class Cliente extends Model implements AuthenticatableContract
{
    use HasFactory, Authenticatable;

    protected $fillable = [
        'nome',
        'iptv_nome',
        'iptv_senha',
        'user_id',
        'whatsapp',
        'password',
        'vencimento',
        'servidor_id',
        'mac',
        'notificacoes',
        'sync_qpanel',
        'plano_id',
        'plano_qpanel',
        'numero_de_telas',
        'notas',
        'role_id',
    ];

    protected $attributes = [
        'role_id' => 3, // Definindo valor padrão para role_id
    ];

    /**
     * Define a relação com o modelo User.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function pagamentos()
    {
        return $this->hasMany(Pagamento::class);
    }

    /**
     * Define a relação com o modelo Servidor.
     */
    public function servidor()
    {
        return $this->belongsTo(Servidor::class, 'servidor_id');
    }

    /**
     * Define a relação com o modelo Plano.
     */
    public function plano()
    {
        return $this->belongsTo(Plano::class);
    }

    /**
     * Define a relação com o modelo Role.
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }


    public function userPreferences()
    {
        return $this->hasMany(UserClientPreference::class);
    }
}
