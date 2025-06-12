<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PublicacionOferta extends Model
{
    use SoftDeletes;

    protected $table = 'publicaciones_ofertas';

    protected $fillable = [
        'mensaje_id',
        'publicacion_id',
        'precio',
        'estado_oferta_id', // Nueva columna
        'oferta_respondida_at', // Nueva columna
    ];

    protected $dates = [
        'deleted_at', 
        'oferta_respondida_at', // Agregar esta fecha a las fechas gestionadas por Laravel
    ];

    public function mensaje()
    {
        return $this->belongsTo(ChatMensaje::class, 'mensaje_id');
    }

    public function publicacion()
    {
        return $this->belongsTo(Publicacion::class, 'publicacion_id');
    }

    public function estado()
    {
        return $this->belongsTo(EstadoOferta::class, 'estado_oferta_id'); // Relación con el estado de la oferta
    }
}
