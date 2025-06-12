<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImagePortadaUser extends Model
{
    use HasFactory;

    protected $table = 'images_portada_users';

    protected $fillable = [
        'id_usuario',
        'url',
        'tamaño',
        'extension',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }
}
