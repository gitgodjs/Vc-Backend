<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tymon\JWTAuth\Contracts\JWTSubject; 

class User extends Authenticatable implements JWTSubject 
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'users';

    protected $fillable = [
        'correo',
        'password',
        'username',
        'nombre',
        'descripcion',
        'ubicacion',         
        'email_verified_at',
        'telefono',
        'red_social',
        'genero',
        'fecha_nacimiento',
    ];

    protected $hidden = [
        'password',
    ];

    public function imagenProfile()
    {
        return $this->hasOne(ImageUser::class, 'id_usuario');
    }

    public function imagenPortada()
    {
        return $this->hasOne(ImagePortadaUser::class, 'id_usuario');
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

    public function tallas()
    {
        return $this->hasOne(UsersTalla::class, 'user_id');
    }

    public function getJWTIdentifier()
    {
        return $this->getKey(); 
    }

    public function getJWTCustomClaims()
    {
        return []; 
    }
}
