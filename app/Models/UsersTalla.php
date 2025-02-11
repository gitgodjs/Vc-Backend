<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsersTalla extends Model
{
    use HasFactory;

    protected $table = 'users_tallas';

    protected $fillable = [
        'user_id',
        'remeras',
        'pantalones',
        'shorts',
        'trajes',
        'vestidos',
        'abrigos',
        'calzados',
        'created_at',
        'updated_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    // Accesor para obtener solo las prendas
    public function getPrendasAttribute()
    {
        return [
            'remeras' => $this->remeras,
            'pantalones' => $this->pantalones,
            'shorts' => $this->shorts,
            'trajes' => $this->trajes,
            'vestidos' => $this->vestidos,
            'abrigos' => $this->abrigos,
            'calzados' => $this->calzados,
        ];
    }
}
