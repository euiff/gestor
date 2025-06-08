<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Servidor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nome',
    ];

    protected $table = 'servidores'; // Certifique-se de que o nome da tabela está correto

    /**
     * Define a relação com o modelo User.
     */

     public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Define a relação com o modelo Cliente.
     */
    public function clientes()
    {
        return $this->hasMany(Cliente::class);
    }

    //atruibuir ao user_preferences
    public function userPreferences()
    {
        return $this->hasMany(UserClientPreference::class);
    }
}
