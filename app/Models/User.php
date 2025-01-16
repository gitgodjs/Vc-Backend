<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Tymon\JWTAuth\Contracts\JWTSubject; 

class User extends Authenticatable implements JWTSubject 
{
    use HasFactory;

    protected $table = 'users';

    protected $fillable = [
        'correo',
        'password',
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

    protected $hidden = [
        'password',
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

    public function getJWTIdentifier()
    {
        return $this->getKey(); 
    }

    public function getJWTCustomClaims()
    {
        return []; 
    }
}

