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
        'estado_ropa',
        'estado_publicacion',
        'precio',
        'categoria',
        'prenda',
        'talle',
        'tipo',
        'ubicacion',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function estadoRopa()
    {
        return $this->belongsTo(EstadoRopa::class, 'estado_ropa');
    }

    public function estadoPublicacion()
    {
        return $this->belongsTo(EstadoPublicacion::class, 'estado_publicacion');
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria');
    }

    public function prenda()
    {
        return $this->belongsTo(Prenda::class, 'prenda');
    }

    public function talle()
    {
        return $this->belongsTo(Talle::class, 'talle');
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
