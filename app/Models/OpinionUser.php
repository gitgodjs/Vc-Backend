<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpinionUser extends Model
{
    use HasFactory;

    protected $table = 'opiniones_users';

    protected $fillable = [
        'id_comentador',
        'id_comentado',
        'comentario',
        'rate_general',
        'rate_calidad_precio',
        'rate_atencion',
        'rate_flexibilidad',
    ];

    public function comentador()
    {
        return $this->belongsTo(User::class, 'id_comentador');
    }

    public function comentado()
    {
        return $this->belongsTo(User::class, 'id_comentado');
    }
}
