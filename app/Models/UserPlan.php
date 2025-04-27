<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPlan extends Model
{
    protected $table = 'users_planes';

    protected $fillable = [
        'user_id',
        'plan_id',
        'publicaciones_disponibles',
        'impulsos_disponibles',
        'prueba_hecha',
        'fecha_compra',
        'fecha_vencimiento',
    ];

    protected $casts = [
        'prueba_hecha' => 'boolean',
        'fecha_compra' => 'date',
        'fecha_vencimiento' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }
}
