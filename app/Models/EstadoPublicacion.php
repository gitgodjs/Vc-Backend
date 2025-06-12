<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstadoPublicacion extends Model
{
    use HasFactory;

    protected $table = 'estado_publicacion';

    protected $fillable = [
        'estado',
    ];
}
