<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RopaEstilo extends Model
{
    protected $table = 'ropa_estilos';

    protected $primaryKey = 'id';

    public $timestamps = false; 

    protected $fillable = [
        'estilo',
    ];
}
