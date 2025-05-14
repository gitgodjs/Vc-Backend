<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificacionTipo extends Model
{
    // Nombre de la tabla
    protected $table = 'notificaciones_tipos';

    // Clave primaria
    protected $primaryKey = 'id';

    // Campos que se pueden asignar de forma masiva
    protected $fillable = [
        'clave',
        'titulo',
        'mensaje',
        'ruta_destino',
    ];

    // Timestamps personalizados
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    // Por si quieres casting de tipos (opcional)
    protected $casts = [
        'id' => 'integer',
    ];
}
