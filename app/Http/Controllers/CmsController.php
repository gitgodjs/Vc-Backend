<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\UserPlan;
use App\Models\ReportePublicacion;
use App\Models\ReporteUsuario;
use App\Models\Publicacion;
use App\Models\RopaCategorias;
use Illuminate\Http\Request;
use App\Models\OpinionUser;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;


class CmsController extends Controller
{
    public function getNuevosUsuarios()
    {
        $user = auth()->user();
    
        if (!$user) {
            return response()->json([
                "Mensaje" => "Este usuario no existe"
            ], 404);
        }
    
        $inicioMes = Carbon::now()->startOfMonth();
        $finMes = Carbon::now()->endOfMonth();
    
        // Agrupar usuarios por día (formato "dd")
        $usuariosPorDia = User::selectRaw('DATE(created_at) as fecha, COUNT(*) as cantidad')
            ->whereBetween('created_at', [$inicioMes, $finMes])
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get()
            ->pluck('cantidad', 'fecha'); // Mapa [fecha => cantidad]
    
        // Generar array con todos los días del mes
        $diasDelMes = [];
        $dia = $inicioMes->copy();
    
        while ($dia <= $finMes) {
            $fechaStr = $dia->toDateString(); // 'YYYY-MM-DD'
            $diasDelMes[] = [
                'dia' => $dia->format('d'), // Solo día numérico "01", "02", etc.
                'Usuarios' => $usuariosPorDia->get($fechaStr, 0),
            ];
            $dia->addDay();
        }
    
        return response()->json([
            "Mensaje" => "Grafico obtenido con éxito",
            "dataGrafico" => $diasDelMes,
        ]);
    }    

    public function getGeneralData() {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                "Mensaje" => "Este usuario no existe"
            ], 404);
        };

        $usuariosActuales = User::count();
        $publicacionesActivas = Publicacion::where("estado_publicacion", "1")->count();
        $publicacionesActuales = Publicacion::where("estado_publicacion", "!=", "3")->count();
        $prendasVendidas = Publicacion::where("estado_publicacion", "3")->count();
        $planesActuales = UserPlan::where("plan_id", "!=", "1")->count();
        $reportesPublicaciones = ReportePublicacion::where("estado", "!=", "pendiente")->count();
        $reportesUsers = ReporteUsuario::where("estado", "!=", "pendiente")->count();
        $reportesActuales = $reportesPublicaciones + $reportesUsers;

        return response()->json([
            "Mensaje" => "Informacion obtenida con éxito",
            "usuariosActuales" => $usuariosActuales,
            "publicacionesActuales" => $publicacionesActuales,
            "publicacionesActivas" => $publicacionesActivas,
            "prendasVendidas" => $prendasVendidas,
            "planesActuales" => $planesActuales,
            "reportesActuales" => $reportesActuales
        ], 200);
    }

    public function getGanancias()
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                "Mensaje" => "Este usuario no existe"
            ], 404);
        }

        /*
            Hacer acá las tres secciones la lado de el grafico
        */

        $inicioMes = Carbon::now()->startOfMonth();
        $finMes = Carbon::now()->endOfMonth();

        // Obtener suma de montos agrupada por día
        $ganancias = DB::table('mercadopago_comprobantes')
            ->selectRaw('DATE(created_at) as fecha, SUM(monto) as total')
            ->whereBetween('created_at', [$inicioMes, $finMes])
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get()
            ->pluck('total', 'fecha'); // Mapa [fecha => total]

        // Crear arreglo para cada día del mes
        $diasDelMes = [];
        $dia = $inicioMes->copy();

        while ($dia <= $finMes) {
            $fechaStr = $dia->toDateString();
            $diasDelMes[] = [
                'dia' => $dia->format('d'), // o 'dia' => $dia->translatedFormat('D') si quieres Lun/Mar/etc.
                'Ganancias' => (float) $ganancias->get($fechaStr, 0),
            ];
            $dia->addDay();
        }

        return response()->json([
            "Mensaje" => "Grafico obtenido con éxito",
            "dataGrafico" => $diasDelMes,
        ]);
    }   
}