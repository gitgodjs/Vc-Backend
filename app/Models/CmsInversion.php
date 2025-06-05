<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CmsInversion extends Model
{
    use SoftDeletes;

    // Nombre de la tabla
    protected $table = 'cms_inversiones';

    protected $fillable = [
        'titulo',
        'descripcion',
        'monto',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];
}
