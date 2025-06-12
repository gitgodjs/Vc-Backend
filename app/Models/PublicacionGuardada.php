<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublicacionGuardada extends Model
{
    use HasFactory;

    protected $table = 'publicaciones_guardadas';

    protected $fillable = [
        'id_publicacion',
        'user_id',
    ];

    public function publicacion()
    {
        return $this->belongsTo(Publicacion::class, 'id_publicacion');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
