<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstadoRopa extends Model
{
    use HasFactory;

    protected $table = 'estado_ropa';

    protected $fillable = [
        'estado',
    ];
}
