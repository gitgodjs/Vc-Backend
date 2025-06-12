<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PublicacionVenta extends Model
{
    use SoftDeletes;
    // Indicar que no usamos la convención de nombres pluralizados
    protected $table = 'publicaciones_ventas';

    // Atributos que se pueden llenar de forma masiva
    protected $fillable = [
        'id_publicacion',
        'id_vendedor',
        'id_comprador',
        'oferta_id',
        'precio',
    ];

    // Definimos las relaciones de Eloquent
    public function publicacion()
    {
        // Una venta pertenece a una publicación
        return $this->belongsTo(Publicacion::class, 'id_publicacion');
    }

    public function vendedor()
    {
        // Una venta tiene un vendedor (usuario)
        return $this->belongsTo(User::class, 'id_vendedor');
    }

    public function comprador()
    {
        // Una venta tiene un comprador (usuario)
        return $this->belongsTo(User::class, 'id_comprador');
    }

    public function oferta()
    {
        // Una venta está asociada a una oferta
        return $this->belongsTo(PublicacionesOferta::class, 'oferta_id');
    }
}
