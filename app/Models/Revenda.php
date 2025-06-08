<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Revenda extends Model
{
    use HasFactory;

    protected $fillable = [
       'nome', 'user_id', 'creditos', 'preco', 'total'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getPrecoAttribute($value)
    {
        return number_format($value, 2, ',', '.');
    }
}
