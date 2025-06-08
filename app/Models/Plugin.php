<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plugin extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'price', 'image_url', 'users_count', 'user_id'];

    // Definir a relação com o modelo User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
