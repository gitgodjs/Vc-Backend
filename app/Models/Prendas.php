<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prendas extends Model
{
    use HasFactory;

    protected $table = 'ropa_prendas';

    protected $fillable = [
        'prenda',
    ];
}
