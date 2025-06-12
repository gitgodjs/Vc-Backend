<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RopaTipo extends Model
{
    use HasFactory;

    protected $table = 'ropa_tipo';

    protected $fillable = [
        'tipo',
    ];
}
