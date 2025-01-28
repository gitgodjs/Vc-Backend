<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserCodigo extends Model
{
    use HasFactory;

    protected $table = 'users_codigos';
    protected $primaryKey = 'id_user';
    public $timestamps = false;
    protected $fillable = [
        'id_user',
        'codigo',
        'create_at',
        'update_at',
    ];
    protected $casts = [
        'create_at' => 'datetime',
        'update_at' => 'datetime',
    ];
}
