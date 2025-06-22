<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\UserPlan;
use App\Models\ReportePublicacion;
use App\Models\ReporteUsuario;
use App\Models\Publicacion;
use App\Models\RopaCategorias;
use App\Models\CmsInversion;
use App\Models\UserSolicitud;
use App\Models\MercadoPagoComprobante;
use Illuminate\Http\Request;
use App\Models\OpinionUser;
use App\Models\PublicacionGuardada;
use App\Models\PublicacionOferta;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\NotificacionTipo;
use App\Models\UserNotificacion;

use App\Mail\EmailVerificacionRechazada;
use App\Mail\EmailVerificacionAceptada;
use App\Mail\VerificacionRechazadaMail;
use Illuminate\Support\Facades\Mail;

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

        $inicioMes = Carbon::now()->startOfMonth();
        $finMes = Carbon::now()->endOfMonth();
        $inicioAno = Carbon::now()->startOfYear();
        $finAno = Carbon::now()->endOfYear();

        // 1. GANANCIAS por día del mes actual
        $gananciasPorDia = DB::table('mercadopago_comprobantes')
            ->selectRaw('DATE(created_at) as fecha, SUM(monto) as total')
            ->whereBetween('created_at', [$inicioMes, $finMes])
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get()
            ->pluck('total', 'fecha'); // [fecha => total]

        // 2. Total ganancias mes y año
        $gananciaMes = DB::table('mercadopago_comprobantes')
            ->whereBetween('created_at', [$inicioMes, $finMes])
            ->sum('monto');

        $gananciaAno = DB::table('mercadopago_comprobantes')
            ->whereBetween('created_at', [$inicioAno, $finAno])
            ->sum('monto');

        // 3. Inversión mes y año
        $inversionMes = DB::table('cms_inversiones')
            ->whereBetween('created_at', [$inicioMes, $finMes])
            ->whereNull('deleted_at')
            ->sum('monto');

        $inversionAno = DB::table('cms_inversiones')
            ->whereBetween('created_at', [$inicioAno, $finAno])
            ->whereNull('deleted_at')
            ->sum('monto');

        // 4. Planes comprados en el mes (excluyendo plan_id = 1)
        $planesCompradosMes = DB::table('mercadopago_comprobantes')
            ->whereBetween('created_at', [$inicioMes, $finMes])
            ->where('plan_id', '!=', 1)
            ->count();

        // 5. Construir gráfico por día del mes
        $diasDelMes = [];
        $dia = $inicioMes->copy();
        while ($dia <= $finMes) {
            $fechaStr = $dia->toDateString();
            $diasDelMes[] = [
                'dia' => $dia->format('d'),
                'Ganancias' => (float) $gananciasPorDia->get($fechaStr, 0),
            ];
            $dia->addDay();
        }

        // 6. Resultado final con ganancias netas
        return response()->json([
            "Mensaje" => "Gráfico obtenido con éxito",
            "dataGrafico" => $diasDelMes,
            "resumen" => [
                "ganancia_mensual_neta" => $gananciaMes - $inversionMes,
                "ganancia_anual_neta" => $gananciaAno - $inversionAno,
                "inversion_mensual" => $inversionMes,
                "inversion_anual" => $inversionAno,
                "planes_comprados_mes" => $planesCompradosMes
            ]
        ]);
    }

    public function getPublicacionesData() {
        $user = auth()->user();
    
        if (!$user) {
            return response()->json([
                "Mensaje" => "Este usuario no existe"
            ], 404);
        }
    
        $inicioMes = Carbon::now()->startOfMonth();
        $finMes = Carbon::now()->endOfMonth();
        $inicioAno = Carbon::now()->startOfYear();
        $finAno = Carbon::now()->endOfYear();
        $baseUrl = config('app.url');
    
        // 1. Publicaciones creadas por día del mes
        $publicacionesPorDia = DB::table('publicaciones')
            ->selectRaw('DATE(created_at) as fecha, COUNT(*) as total')
            ->whereBetween('created_at', [$inicioMes, $finMes])
            ->whereNull('deleted_at')
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get()
            ->pluck('total', 'fecha');
    
        $diasDelMes = [];
        $dia = $inicioMes->copy();
        while ($dia <= $finMes) {
            $fechaStr = $dia->toDateString();
            $diasDelMes[] = [
                'dia' => $dia->format('d'), // NÚMERO del día (Ej: 01, 02, ..., 31)
                'Publicaciones' => (int) $publicacionesPorDia->get($fechaStr, 0),
            ];
            $dia->addDay();
        }
    
        // 2. Totales
        $publicacionesMes = DB::table('publicaciones')
            ->whereBetween('created_at', [$inicioMes, $finMes])
            ->whereNull('deleted_at')
            ->count();
    
        $publicacionesTotales = DB::table('publicaciones')
            ->whereNull('deleted_at')
            ->count();
    
        $ofertasMes = DB::table('publicaciones_ofertas')
            ->whereBetween('created_at', [$inicioMes, $finMes])
            ->count();
    
        $ofertasTotales = DB::table('publicaciones_ofertas')->count();
    
        $guardadosMes = DB::table('publicaciones_guardadas')
            ->whereBetween('created_at', [$inicioMes, $finMes])
            ->count();
    
        $guardadosTotales = DB::table('publicaciones_guardadas')->count();
    
        // 3. Publicaciones destacadas
        $publicacionesMesQuery = Publicacion::with('imagen', 'user')
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$inicioMes, $finMes])
            ->get();
    
        $masVista = $publicacionesMesQuery->where('visitas', '>', 0)->sortByDesc('visitas')->first();
    
        $masGuardada = $publicacionesMesQuery->sortByDesc(function ($pub) {
            return PublicacionGuardada::where('id_publicacion', $pub->id)->count();
        })->filter(function ($pub) {
            return PublicacionGuardada::where('id_publicacion', $pub->id)->count() > 0;
        })->first();
    
        $masOfertada = $publicacionesMesQuery->sortByDesc(function ($pub) {
            return PublicacionOferta::where('publicacion_id', $pub->id)->count();
        })->filter(function ($pub) {
            return PublicacionOferta::where('publicacion_id', $pub->id)->count() > 0;
        })->first();
    
        $publicacionesDestacadas = [
            'mas_vista' => $masVista ? [
                'id' => $masVista->id,
                'nombre' => $masVista->nombre,
                'precio' => $masVista->precio,
                'correo' => $masVista->user->email ?? null,
                'imagenUrl' => $masVista->imagen ? $baseUrl . "/storage/" . $masVista->imagen->url : null,
                'valor' => $masVista->visitas,
                'tipo' => 'visitada'
            ] : null,
    
            'mas_guardada' => $masGuardada ? [
                'id' => $masGuardada->id,
                'nombre' => $masGuardada->nombre,
                'precio' => $masGuardada->precio,
                'correo' => $masGuardada->user->email ?? null,
                'imagenUrl' => $masGuardada->imagen ? $baseUrl . "/storage/" . $masGuardada->imagen->url : null,
                'valor' => PublicacionGuardada::where('id_publicacion', $masGuardada->id)->count(),
                'tipo' => 'guardada'
            ] : null,
    
            'mas_ofertada' => $masOfertada ? [
                'id' => $masOfertada->id,
                'nombre' => $masOfertada->nombre,
                'precio' => $masOfertada->precio,
                'correo' => $masOfertada->user->email ?? null,
                'imagenUrl' => $masOfertada->imagen ? $baseUrl . "/storage/" . $masOfertada->imagen->url : null,
                'valor' => PublicacionOferta::where('publicacion_id', $masOfertada->id)->count(),
                'tipo' => 'ofertada'
            ] : null,
        ];
    
        return response()->json([
            'mensaje' => 'Datos cargados correctamente',
            'dataGrafico' => $diasDelMes,
            'resumen' => [
                'publicaciones_mes' => $publicacionesMes,
                'publicaciones_total' => $publicacionesTotales,
                'ofertas_mes' => $ofertasMes,
                'ofertas_total' => $ofertasTotales,
                'guardados_mes' => $guardadosMes,
                'guardados_total' => $guardadosTotales,
            ],
            'destacadas' => $publicacionesDestacadas
        ]);
    }
    
    public function getVentasData() {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'mensaje' => 'Este usuario no existe'
            ], 404);
        }
        
        Carbon::setLocale('es');        
        $inicioMes = Carbon::now()->startOfMonth();
        $finMes = Carbon::now()->endOfMonth();
        $inicioAno = Carbon::now()->startOfYear();
        $finAno = Carbon::now()->endOfYear();
    
        // --- 1. Ventas y procesos por día ---
        $ventasPorDia = DB::table('publicaciones_ventas')
            ->selectRaw('DATE(created_at) as fecha, COUNT(*) as total')
            ->whereBetween('created_at', [$inicioMes, $finMes])
            ->where('estado_venta', 2)
            ->groupBy('fecha')
            ->pluck('total', 'fecha');
    
        $procesosPorDia = DB::table('publicaciones_ventas')
            ->selectRaw('DATE(created_at) as fecha, COUNT(*) as total')
            ->whereBetween('created_at', [$inicioMes, $finMes])
            ->where('estado_venta', 1)
            ->groupBy('fecha')
            ->pluck('total', 'fecha');
    
        $diasDelMes = [];
        $dia = $inicioMes->copy();
        while ($dia <= $finMes) {
            $fecha = $dia->toDateString();
            $diasDelMes[] = [
                'dia' => $dia->format('d'), // Número del día
                'Ventas' => (int) $ventasPorDia->get($fecha, 0),
                'Procesos' => (int) $procesosPorDia->get($fecha, 0)
            ];
            $dia->addDay();
        }
    
        // --- 2. Totales de dinero y conteo ---
        $ventasMes = DB::table('publicaciones_ventas')
            ->whereBetween('created_at', [$inicioMes, $finMes])
            ->where('estado_venta', 2)
            ->sum('precio');
    
        $ventasAno = DB::table('publicaciones_ventas')
            ->whereBetween('created_at', [$inicioAno, $finAno])
            ->where('estado_venta', 2)
            ->sum('precio');
    
        $ventasFinalizadasMes = DB::table('publicaciones_ventas')
            ->whereBetween('created_at', [$inicioMes, $finMes])
            ->where('estado_venta', 2)
            ->count();
    
        $ventasFinalizadasAno = DB::table('publicaciones_ventas')
            ->whereBetween('created_at', [$inicioAno, $finAno])
            ->where('estado_venta', 2)
            ->count();
    
        $ventasProcesoMes = DB::table('publicaciones_ventas')
            ->whereBetween('created_at', [$inicioMes, $finMes])
            ->where('estado_venta', 1)
            ->count();
    
        $ventasProcesoTotal = DB::table('publicaciones_ventas')
            ->where('estado_venta', 1)
            ->count();
    
        // --- 3. Ventas en proceso (simulando receptor si no hay tabla de transacciones) ---
        $ventasEnProceso = DB::table('publicaciones_ventas')
            ->where('estado_venta', 1) // Solo ventas en proceso
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function ($venta) {
                $baseUrl = env('APP_URL');
                $emisor = User::find($venta->id_vendedor);
                $receptor = User::find($venta->id_comprador);
                if ($emisor->imagenProfile) {
                    $emisor->foto_perfil_url = $baseUrl . "/storage/" . $emisor->imagenProfile->url;
                };

                if ($receptor->imagenProfile) {
                    $receptor->foto_perfil_url = $baseUrl . "/storage/" . $receptor->imagenProfile->url;
                };

                return [
                    'idPublicacion' => $venta->id_publicacion,
                    'emisor' => [
                        'username' => $emisor?->username ?? 'desconocido',
                        'avatar' => $emisor->foto_perfil_url
                    ],
                    'receptor' => [
                        'username' => $receptor?->username ?? 'desconocido',
                        'avatar' => $receptor->foto_perfil_url
                    ],
                    'precio' => (float) $venta->precio,
                    'tiempoEnProceso' => Carbon::parse($venta->created_at)->diffForHumans()
                ];
            }
        );

        return response()->json([
            'mensaje' => 'Datos de ventas cargados correctamente',
            'grafico' => $diasDelMes,
            'resumen' => [
                'dinero_mes' => $ventasMes,
                'dinero_ano' => $ventasAno,
                'finalizadas_mes' => $ventasFinalizadasMes,
                'finalizadas_ano' => $ventasFinalizadasAno,
                'en_proceso_mes' => $ventasProcesoMes,
                'en_proceso_total' => $ventasProcesoTotal
            ],
            'ventas_en_proceso' => $ventasEnProceso
        ]);
    }    

    public function createInversion(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                "Mensaje" => "Este usuario no existe"
            ], 404);
        }

        // Crear inversión directamente con array asociativo
        $inversion = CmsInversion::create([
            'titulo' => $request->titulo["category"],
            'descripcion' => $request->descripcion,
            'monto' => $request->monto,
        ]);

        return response()->json([
            'Mensaje' => 'Inversión creada correctamente',
            'inversion' => $inversion
        ], 200);
    }

    public function getInversiones() {
        $user = auth()->user();
    
        if (!$user) {
            return response()->json([
                "Mensaje" => "Este usuario no existe"
            ], 404);
        }
    
        Carbon::setLocale('es');
    
        $inicioMes = Carbon::now()->startOfMonth();
        $finMes = Carbon::now()->endOfMonth();
    
        // Traer inversiones del mes actual, excluyendo eliminadas, ordenadas de más nuevas a más viejas
        $inversiones = DB::table('cms_inversiones')
            ->whereNull('deleted_at') // excluye soft deleted
            ->whereBetween('created_at', [$inicioMes, $finMes])
            ->orderBy('created_at', 'desc') // ordena de más nuevas a más viejas
            ->get();
    
        // Agrega fecha relativa
        foreach($inversiones as $inversion) {
            $inversion->fechaRelativa = Carbon::parse($inversion->created_at)->diffForHumans();
        }
    
        return response()->json([
            'Mensaje' => 'Inversiones obtenidas correctamente',
            'inversiones' => $inversiones,
        ], 200);
    }    

    public function eliminarInversion(Request $request) {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                "Mensaje" => "Este usuario no existe"
            ], 404);
        };

        
        $inversion = CmsInversion::find($request->inversion_id);

        if (!$inversion) {
            return response()->json([
                "Mensaje" => "Inversión no encontrada",
                "id" => $request->inversion_id
            ], 404);
        };

        $inversion->delete();

        return response()->json([
            'Mensaje' => 'Inversión creada correctamente',
            'inversion' => $request->inversion_id,
        ], 200);
    }

    public function getUsersSolicitudes() {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'message' => 'Usuario no encontrado'
            ], 404);
        };
        $baseUrl = env('APP_URL');

        $solicitudes = UserSolicitud::get();
        
        foreach($solicitudes as $solicitud) {
            $user_solicitante = User::find($solicitud->user_id);

            if ($user_solicitante->imagenProfile) {
                $user_solicitante->foto_perfil_url = $baseUrl . "/storage/" . $user->imagenProfile->url;
            };
            $solicitud->user = $user_solicitante;
        };

        return response()->json([
            "mensaje" => "Solicitudes obtenidas con exito",
            "solicitudes" => $solicitudes,
        ], 200);
    }

    public function getReportes() {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'message' => 'Usuario no encontrado'
            ], 404);
        };
        $baseUrl = env('APP_URL');

        $reportesPubs = ReportePublicacion::where("estado", "pendiente")->get();
        $reportesUsers = ReporteUsuario::where("estado", "pendiente")->get();

        foreach($reportesUsers as $reporte) {
            $user_creador = User::find($reporte->id_creador);
            $user_reportado = User::find($reporte->id_usuario_reportado);

            if ($user_reportado->imagenProfile) {
                $user_reportado->foto_perfil_url = $baseUrl . "/storage/" . $user_reportado->imagenProfile->url;
            };

            $reporte->user_reportado = $user_reportado;
            $reporte->user_creador_correo = $user_creador->correo;
            $reporte->user_creador_username = $user_creador->username;
        };

        $publicacionesReportadas = $reportesPubs->map(function ($publicacion) {
            $publicacionReset = Publicacion::find($publicacion->id_publicacion);
            $user_creador = User::find($publicacion->id_creador);

            return [
                'id' => $publicacionReset->id,
                'id_reporte' => $publicacion->id,
                'id_creador' => $publicacionReset->id_user,
                'nombre' => $publicacionReset->nombre,
                'imagenUrl' => $publicacionReset->imagen,
                'creador_username' => $user_creador->username,
                'creador_correo' => $user_creador->correo,
                'titulo' => $publicacion->titulo,
                'descripcion' => $publicacion->descripcion,
            ];
        });

        $publicacionesFinales = $this->imageControll($publicacionesReportadas);

        return response()->json([
            "mensaje" => "Verificado con exito",
            "reportesUsers" => $reportesUsers, 
            "reportesPubs" => $publicacionesFinales,
        ], 200);
    }

    public function imageControll($publicaciones) {
        $baseUrl = env('APP_URL');
    
        $publicacionesImage = $publicaciones->map(function ($pub) use ($baseUrl) {
            $pub['imagenUrl'] = isset($pub['imagenUrl']) 
                ? $baseUrl . "/storage/" . $pub['imagenUrl']["url"]
                : null;
            return $pub;
        });
    
        return $publicacionesImage;
    } 

    public function eliminarReportePub(Request $request) {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'message' => 'Usuario no encontrado'
            ], 404);
        };

        $reporte = ReportePublicacion::find($request->pub_id);
        if(empty($reporte)) {
            return response()->json([
                "mensaje" => "¡No existe esta publicacion!",
            ], 404);
        };

        $reporte->delete();

        return response()->json([
            "mensaje" => "Reporte eliminado con exito",
        ], 200);
    }

    public function eliminarReporteUser(Request $request) {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'message' => 'Usuario no encontrado'
            ], 404);
        };

        $reporte = ReporteUsuario::find($request->user_id);
        if(empty($reporte)) {
            return response()->json([
                "mensaje" => "¡No existe este usuario!",
            ], 404);
        };

        $reporte->delete();

        return response()->json([
            "mensaje" => "Reporte eliminado con exito",
        ], 200);
    }

    public function eliminarPub(Request $request) {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'message' => 'Usuario no encontrado'
            ], 404);
        };

        $pub = Publicacion::find($request->pub_id);
        $reporte = ReportePublicacion::find($request->repote_id);
        $reporte->estado = "resuelto";
        $reporte->save();

        if(empty($pub)) {
            return response()->json([
                "mensaje" => "¡No existe esta publicacion!",
            ], 404);
        };

        $pub->delete();

        return response()->json([
            "mensaje" => "Publicacion eliminada con exito",
            "publicacion" => $pub,
        ], 200);
    }

    public function eliminarUser(Request $request)
    {
        /* 1. Usuario autenticado (admin / moderador) */
        $admin = auth()->user();
        if (!$admin) {
            return response()->json(['message' => 'Usuario no autenticado'], 401);
        }

        /* 3. Usuario a eliminar */
        $usuario = User::find($request->user_id);
        if (!$usuario) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }
    
        /* 4. Marcar uno o varios reportes como resueltos */
        if ($request->filled('reporte_id')) {
            $ids = is_array($request->reporte_id)
                 ? $request->reporte_id          // array de IDs
                 : [$request->reporte_id];       // ID único en array
    
            ReporteUsuario::whereIn('id', $ids)->update(['estado' => 'resuelto']);
        }
    
        /* 5. Eliminar usuario */
        $usuario->delete();
    
        return response()->json([
            'mensaje' => 'Usuario eliminado con éxito',
            'usuario' => $usuario,
        ], 200);
    }     

    public function verificarUsuario(Request $request) {
        $admin = auth()->user(); // admin autenticado
    
        if (!$admin) {
            return response()->json([
                'message' => 'Usuario no encontrado'
            ], 404);
        };
    
        $user = User::find($request->user_id);
        
        if (!$user) {
            return response()->json([
                'message' => 'Usuario no existe'
            ], 404);
        }
    
        if ($user->verificado) {
            return response()->json([
                "mensaje" => "Usuario ya verificado!"
            ], 400);
        };
    
        $user->verificado = 1;
        $user->save();
    
        // Eliminar solicitud si existe
        $solicitud = UserSolicitud::where('user_id', $user->id)->first();
        if ($solicitud) {
            $solicitud->delete(); 
        }
    
        // Notificación (ID 17)
        $tipoNotificacion = NotificacionTipo::find(17);
        $mensaje = $tipoNotificacion->mensaje;
    
        UserNotificacion::create([
            'user_id' => $user->id,
            'notificacion_tipo_id' => $tipoNotificacion->id,
            'mensaje' => $mensaje,
            'leido' => 0,
            'fecha_creacion' => now(),
            'fecha_visto' => null,
            'ruta_destino' => $tipoNotificacion->ruta_destino ?? '/perfil',
        ]);
    
        Mail::to($user->correo)->send(new EmailVerificacionAceptada($user->correo));
    
        return response()->json([
            "mensaje" => "Verificado con éxito"
        ], 200);
    }
    
    public function rechazarVerificacion(Request $request) {
        $admin = auth()->user();
    
        if (!$admin) {
            return response()->json([
                'message' => 'Usuario no encontrado'
            ], 404);
        };
    
        $user = User::find($request->user_id);
    
        if (!$user) {
            return response()->json([
                'message' => 'Usuario no existe más'
            ], 400);
        }
    
        // Eliminar solicitud si existe
        $solicitud = UserSolicitud::where('user_id', $user->id)->first();
        if ($solicitud) {
            $solicitud->delete(); 
        }
    
        // Notificación (ID 18)
        $tipoNotificacion = NotificacionTipo::find(18);
        $mensaje = $tipoNotificacion->mensaje;
    
        UserNotificacion::create([
            'user_id' => $user->id,
            'notificacion_tipo_id' => $tipoNotificacion->id,
            'mensaje' => $mensaje,
            'leido' => 0,
            'fecha_creacion' => now(),
            'fecha_visto' => null,
            'ruta_destino' => $tipoNotificacion->ruta_destino ?? '/perfil',
        ]);
    
        Mail::to($user->correo)->send(new EmailVerificacionRechazada($user->correo));
    
        return response()->json([
            "mensaje" => "Rechazado con éxito"
        ], 200);
    }
    
}