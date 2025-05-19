<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Publicacion extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'publicaciones';

    protected $fillable = [
        'id_user',
        'id_estilo',
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
        'visitas',
        'fecha_impulso',
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
        return $this->belongsTo(RopaCategorias::class, 'categoria');
    }

    public function prenda()
    {
        return $this->belongsTo(Prendas::class, 'prenda');
    }
    
    public function tipo()
    {
        return $this->belongsTo(RopaTipo::class, 'tipo');
    }

    public function imagen()
    {
        return $this->hasOne(ImagePublicacion::class, 'id_publicacion')->oldest();    
    }

    public function imagenes()
    {
        return $this->hasMany(ImagePublicacion::class, 'id_publicacion');
    }

    public function publicacionesGuardadas()
    {
        return $this->hasMany(UsuarioPublicacionGuardada::class, 'id_publicacion');
    }

    public function estilo() {
        return $this->belongsTo(RopaEstilo::class, 'id_estilo');
    }
    
}
