<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    /**
     * Define a relação com o modelo Cliente.
     */
    public function clientes()
    {
        return $this->hasMany(Cliente::class);
    }
}
