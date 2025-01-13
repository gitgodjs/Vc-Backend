<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasFactory;

    protected $table = 'users';

    protected $fillable = [
        'correo',
        'nombre',
        'apellido',
        'descripcion',
        'ubicacion_id',
        'image_id',
        'email_verified_at',
        'telefono',
        'red_social',
        'genero',
        'fecha_nacimiento',
    ];

    public function imagen()
    {
        return $this->hasOne(ImageUser::class, 'id_usuario');
    }

    public function publicaciones()
    {
        return $this->hasMany(Publicacion::class, 'id_user');
    }

    public function opiniones()
    {
        return $this->hasMany(OpinionUser::class, 'id_comentado');
    }

    public function publicacionesGuardadas()
    {
        return $this->hasMany(UsuarioPublicacionGuardada::class, 'user_id');
    }
}
