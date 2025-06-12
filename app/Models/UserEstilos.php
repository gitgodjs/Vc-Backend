<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserEstilos extends Model
{
    use HasFactory;

    // Nombre de la tabla en la base de datos
    protected $table = 'users_estilos';
    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'id_user', 
        'id_estilo_1', 
        'id_estilo_2', 
        'id_estilo_3'
    ];

    // Relación con el modelo User
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    // Relación con el modelo RopaEstilo para el primer estilo
    public function estilo1()
    {
        return $this->belongsTo(RopaEstilo::class, 'id_estilo_1');
    }

    // Relación con el modelo RopaEstilo para el segundo estilo
    public function estilo2()
    {
        return $this->belongsTo(RopaEstilo::class, 'id_estilo_2');
    }

    // Relación con el modelo RopaEstilo para el tercer estilo
    public function estilo3()
    {
        return $this->belongsTo(RopaEstilo::class, 'id_estilo_3');
    }
}
