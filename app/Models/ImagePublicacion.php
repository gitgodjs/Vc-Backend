<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImagePublicacion extends Model
{
    use HasFactory;

    protected $table = 'images_publicaciones';

    protected $fillable = [
        'id_usuario',
        'id_publicacion',
        'url',
        'tamaño',
        'nombre',
        'extension',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    public function publicacion()
    {
        return $this->belongsTo(Publicacion::class, 'id_publicacion');
    }
}
