<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Plan;
use App\Models\UserPlan;
use App\Models\Publicacion;
use Illuminate\Http\Request;
use App\Mail\EmailCodeConfirmation;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;


class PlanesController extends Controller
{
    public function obtenerPlanes() {
        $user = auth()->user();
        if(!$user) {
            return response()->json([
                "Mensaje" => "Error! El usuario no existe!",
                "code" => 404
            ], 404);
        }

        $userPlan = UserPlan::where('user_id', $user->id)->first();
        $planActual = null;
        $vencimiento = null;

        if(!$userPlan) {
            $planActual = Plan::find(1);
        } else {
            $planActual = Plan::find($userPlan->plan_id);

            $hoy = Carbon::now();
            $fechaVencimiento = Carbon::parse($userPlan->fecha_vencimiento);

            $diasRestantes = $hoy->diffInDays($fechaVencimiento, false); // false para que devuelva negativos si ya venció

            if ($diasRestantes > 0) {
                $vencimiento = "$diasRestantes días";
            } elseif ($diasRestantes === 0) {
                $vencimiento = "Vence hoy";
            } else {
                $vencimiento = "Vencido hace " . abs($diasRestantes) . " días";
            }

            $planActual->vencimiento = $vencimiento;
        }

        $planes = Plan::get();

        return response()->json([
            "Mensaje" => "Planes",
            "code" => 200,
            "planes" => $planes,
            "planActual" => $planActual,
        ], 200);
    }


    public function obtenerPlanActual() 
    {
        $user = auth()->user();
        
        if(!$user) {
            return response()->json([
                "success" => false,
                "message" => "Error! El usuario no existe!",
                "code" => 404
            ], 404);
        }
    
        // Verificar o crear plan básico si no existe
        $planActual = UserPlan::where('user_id', $user->id)->first();
        
        if(!$planActual) {
            $planActual = UserPlan::create([
                'user_id' => $user->id,
                'plan_id' => 1, // Plan básico
                'publicaciones_disponibles' => 5,
                'impulsos_disponibles' => 0,
                'fecha_compra' => now(),
                'fecha_vencimiento' => now()->addMonths(12),
            ]);
        }
    
        // Contar publicaciones activas
        $publicacionesActivas = Publicacion::where('id_user', $user->id)
            ->where('estado_publicacion', 1)
            ->count();
        
        $plan = Plan::find($planActual->id)->first();
    
        return response()->json([
            "success" => true,
            "plan" => $plan,
            "userPlan" => $planActual,
            "publicaciones_activas" => $publicacionesActivas,
            "publicaciones_restantes" => $planActual->publicaciones_disponibles - $publicacionesActivas,
            "puede_publicar" => ($publicacionesActivas < $planActual->publicaciones_disponibles)
        ]);
    }

    public function cancelarPlan(Request $request) {
        $user = auth()->user();
        if(!$user) {
            return response()->json([
                "message" => "Error! El usuario no existe!",
                "code" => 404
            ], 404);
        }
    
        $planActual = UserPlan::where('user_id', $user->id)->first();
        
        if($planActual->plan_id === 1) {
            return response()->json([
                "plan" => $planActual->plan_id,
                "message" => "Este plan no se pude cancelar porque es el de base",
                "code" => 402
            ], 402);
        };

        $vencimientoPlan = now()->addYear();
        $planActual->update([
            'plan_id' => 1,
            'publicaciones_disponibles' => 5,
            'impulsos_disponibles' => 0,
            'fecha_compra' => now(),
            'fecha_vencimiento' => $vencimientoPlan,
            'updated_at' => now(),
        ]);

        return response()->json([
            "message" => "Plan cancelado con exito",
            "code" => 200
        ], 200);
    }
}