<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Publicacion extends Model
{
    use HasFactory;

    protected $table = 'publicaciones';

    protected $fillable = [
        'id_user',
        'nombre',
        'descripcion',
        'estado_producto',
        'estado',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function estadoProducto()
    {
        return $this->belongsTo(Estado::class, 'estado_producto');
    }

    public function imagenes()
    {
        return $this->hasMany(ImagePublicacion::class, 'id_publicacion');
    }

    public function publicacionesGuardadas()
    {
        return $this->hasMany(UsuarioPublicacionGuardada::class, 'id_publicacion');
    }
}
