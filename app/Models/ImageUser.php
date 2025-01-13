<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImageUser extends Model
{
    use HasFactory;

    protected $table = 'images_users';

    protected $fillable = [
        'id_usuario',
        'url',
        'tamaño',
        'extension',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }
}
