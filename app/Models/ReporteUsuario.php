<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReporteUsuario extends Model
{
    use HasFactory;

    protected $table = 'reportes_usuarios';

    protected $fillable = [
        'id_creador',
        'id_usuario_reportado',
        'titulo',
        'descripcion',
        'estado',
    ];

    // Relaciones
    public function creador()
    {
        return $this->belongsTo(User::class, 'id_creador');
    }

    public function usuarioReportado()
    {
        return $this->belongsTo(User::class, 'id_usuario_reportado');
    }
}
