<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $table = 'planes';

    protected $fillable = [
        'titulo',
        'descripcion',
        'beneficios',
        'precio',
        'meses_plan',
        'publicaciones_mes',
        'impulsos_mes',
    ];

    public function usersPlanes()
    {
        return $this->hasMany(UserPlan::class, 'plan_id');
    }
}
