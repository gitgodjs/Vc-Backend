<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReportePublicacion extends Model
{
    use HasFactory;

    protected $table = 'reportes_publicaciones';
    
    protected $fillable = [
        'id_publicacion',
        'id_creador',
        'id_dueño_publicacion',
        'titulo',
        'descripcion',
        'estado',
    ];

    // Relaciones (si existen los modelos User y Publicacion)
    public function creador()
    {
        return $this->belongsTo(User::class, 'id_creador');
    }

    public function dueñoPublicacion()
    {
        return $this->belongsTo(User::class, 'id_dueño_publicacion');
    }

    public function publicacion()
    {
        return $this->belongsTo(Publicacion::class, 'id_publicacion');
    }
}
