<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserNotificacion extends Model
{
    use HasFactory;

    // Nombre de la tabla personalizado
    protected $table = 'users_notificaciones';

    // Laravel manejará created_at y updated_at
    public $timestamps = true;

    // Campos que pueden ser asignados masivamente
    protected $fillable = [
        'user_id',
        'notificacion_tipo_id',
        'mensaje',              // Nuevo campo agregado
        'leido',
        'fecha_creacion',
        'fecha_visto',
        'ruta_destino'
    ];

    protected $casts = [
        'leido' => 'boolean',
        'fecha_creacion' => 'datetime',
        'fecha_visto' => 'datetime',
    ];

    // Relación con el modelo 'User'
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relación con el modelo 'NotificacionTipo'
    public function notificacionTipo()
    {
        return $this->belongsTo(NotificacionTipo::class, 'notificacion_tipo_id');
    }
}
