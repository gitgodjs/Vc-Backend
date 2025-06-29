<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\UsersTalla;
use App\Models\UserCodigo;
use App\Models\OpinionUser;
use App\Models\Publicacion;
use App\Models\UserEstilos;
use App\Models\ReporteUsuario;
use App\Models\PublicacionVenta;
use App\Models\NotificacionTipo;
use App\Models\UserNotificacion;
use App\Models\ReportePublicacion;
use App\Models\UserSolicitud;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use App\Mail\EmailVerificacionSolicitada;
use App\Mail\EmailReportasteCuenta;
use App\Mail\EmailCuentaReportada;
use App\Mail\EmailPublicacionReportada;
use App\Mail\EmailReportastePublicacion;
use App\Mail\EmailCodeConfirmation;
use App\Mail\EmailNuevaPublicacion;
use Illuminate\Support\Facades\Mail;



class UserController extends Controller
{   
    public function crearCodigoVerficacion($correo)
    {
        $user = User::where("correo", $correo)->first();
    
        if (!$user) {
            return response()->json([
                "mensaje" => "Usuario inexistente",
            ]);
        };
    
        $codigo = rand(100000, 999999);  
    
        $userCodigo = UserCodigo::where('id_user', $user->id)->first();
    
        if ($userCodigo) {
            $userCodigo->codigo = $codigo;
            $userCodigo->update_at = now();
            $userCodigo->save();
        } else {
            UserCodigo::create([
                'id_user' => $user->id,
                'codigo' => $codigo,
                'create_at' => now(),
                'update_at' => now(),
            ]);
        };
    
        Mail::to($correo)->send(new EmailCodeConfirmation($correo, $codigo));
    
        return response()->json([
            "mensaje" => "Código de verificación enviado",
        ]);
    }
    
    public function verficarCodigo(Request $request, $correo, $codigo) {
        $user = User::where("correo", $correo)->first();
        if(!$user){
            return response()->json([
                "mensaje" => "Usuario inexistente",
            ], 404);
        };

        $userCodigo = UserCodigo::where('id_user', $user->id)->first();
        if(!$userCodigo){
            return response()->json([
                "mensaje" => "No tiene codigos",
            ], 404);
        };

        if($codigo == $userCodigo->codigo) {
            // Perfil verificado
            $user->email_verified_at = now(); 
            $user->save();

            return response()->json([
                "mensaje" => "verificado"
            ], 200);
        } else {
            return response()->json([
                "mensaje" => "Codigo incorrecto",
            ], 401);
        };
    }

    public function obtenerUserCorreo($correo) {
        $user = User::where("correo", $correo)
            ->whereNull("deleted_at")
            ->first();
        
        if(!$user){
            return response()->json([
                "mensaje" => "Usuario inexistente",
                "code" => 400,
            ], 400);
        };
    
        $baseUrl = env('APP_URL');
    
        if ($user->imagenPortada) {
            $user->foto_portada_url = $baseUrl . "/storage/" . $user->imagenPortada->url;
        }
    
        if ($user->imagenProfile) {
            $user->foto_perfil_url = $baseUrl . "/storage/" . $user->imagenProfile->url;
        };
    
        return response()->json([
            "mensaje" => "Usuario existente",
            "user" => $user,
        ], 200);
    }

    public function obtenerUserToken() {
        $user = auth()->user();
        
        if(!$user){
            return response()->json([
                "mensaje" => "Usuario inexistente",
            ]);
        };

        $baseUrl = env('APP_URL');
    
        if ($user->imagenPortada) {
            $user->foto_portada_url = $baseUrl . "/storage/" . $user->imagenPortada->url;
        }
    
        if ($user->imagenProfile) {
            $user->foto_perfil_url = $baseUrl . "/storage/" . $user->imagenProfile->url;
        };
    
        return response()->json([
            "mensaje" => "Usuario existente",
            "user" => $user,
        ], 200);
    }

    public function completarPerfil(Request $request) {
        $user = auth()->user();
        if(!$user){
            return response()->json([
                "mensaje" => "Usuario inexistente",
            ]);
        };

        $fechaNacimiento = Carbon::parse($request->fecha_nacimiento);

        $user->update([
            'username' => $request->userName,
            'nombre' => $request->name,
            'descripcion' => $request->descripcion,
            'red_social' => $request->red_social != null ? $request->red_social : null,
            'fecha_nacimiento' => $request->fecha_nacimiento, 
            'ubicacion' => $request->ciudad,
            'telefono' => $request->telefono,
            'genero' => $request->genero,
        ]);
        
        $user->save();

        return response()->json([
            'mensaje' => 'Perfil actualizado correctamente',
            'data' => $user,
        ]);
    }

    public function actualizarTallasUser(Request $request) 
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        };

        $userTallas = UsersTalla::updateOrCreate(
            ['user_id' => $user->id], 
            [
                'remeras' => $request->talleRemera ?? null,
                'pantalones' => $request->tallePantalon ?? null,
                'shorts' => $request->talleShort ?? null,
                'trajes' => $request->talleTraje ?? null,
                'abrigos' => $request->talleAbrigo ?? null,
                'vestidos' => $request->talleVestido ?? null,
                'calzados' => $request->talleCalzado ?? null,
            ]
        );

        return response()->json(['message' => 'Tallas guardadas correctamente'], 200);
    }

    public function actualizarEstilosUser(Request $request) 
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        $estilos = $request->input('estilosSeleccionados', []);

        if (count($estilos) < 1) {
            return response()->json(['message' => 'Debes seleccionar al menos un estilo'], 422);
        }

        $userEstilos = UserEstilos::updateOrCreate(
            ['id_user' => $user->id], 
            [
                'id_estilo_1' => $estilos[0] ?? null, 
                'id_estilo_2' => $estilos[1] ?? null, 
                'id_estilo_3' => $estilos[2] ?? null
            ]
        );

        return response()->json(['message' => 'Estilos guardados correctamente'], 200);
    }

    public function obtenerTallasUser($correo) {
        $user = User::where("correo", $correo)->first();
        
        if (!$user) {
            return response()->json([
                'message' => 'Usuario no encontrado',
                'code' => 404
            ], 404);
        };

        $estilos = UserEstilos::where("id_user", $user->id)->first();

        if($user->tallas != null) {
            $tallas = $user->tallas->prendas;
        } else {
            $tallas = $user->tallas;
        };
        
        $tallas = [
            'remeras' => $tallas['remeras'] ?? null,
            'pantalones' => $tallas['pantalones'] ?? null,
            'shorts' => $tallas['shorts'] ?? null,
            'abrigos' => $tallas['abrigos'] ?? null,
            'calzados' => $tallas['calzados'] ?? null,
            'trajes' => $tallas['trajes'] ?? null,
            'vestidos' => $tallas['vestidos'] ?? null,
        ];

        return response()->json([
            'tallas' => $tallas,
            'estilos' => $estilos ?? null,
            'code' => 200
        ], 200);
    }

    public function getUsers(Request $request, $page) {
        $user = auth()->user();
        
        if(!$user) {
            return response()->json(["mensaje" => "Usuario no encontrado"], 404);
        };
    
        $query = User::whereNotNull('username');

        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = trim($request->search);
            $query->where(function ($q) use ($searchTerm) {
                $q->where('username', 'LIKE', $searchTerm . '%') 
                ->orWhere('nombre', 'LIKE', '%' . $searchTerm . '%');
            });
        };
    
        $perPage = 10;
        $users = $query->with(['imagenProfile'])
            ->paginate($perPage, ['*'], 'page', $page);
        
        return response()->json([
            "mensaje" => "usuarios obtenidos con exito",
            "users" => $users->items(),
            "hasMore" => $users->hasMorePages(),
            "usersTotales" => $users->total(),
        ]);
    }

    public function borrarCuenta(Request $request, $user_id) {
        $user = User::find($user_id);

        if(!$user) {
            return response()->json([
                "Mensaje" => "Usuario no encontrado",
                "code" => 404,
            ], 404);
        };

        $user->delete();

        return response()->json([
            "Mensaje" => "Usuario eliminado con exito",
        ], 200);
    }

    public function obtenerReseñas($correo_user) {
        $user = User::where("correo", $correo_user)->first();
    
        if (!$user) {
            return response()->json([
                "mensaje" => "Usuario no encontrado",
                "code" => 404,
            ], 404);
        };

        Carbon::setLocale('es');
        $baseUrl = env('APP_URL');

        $reseñas = OpinionUser::where("id_comentado", $user->id)->get();
    
        $resultado = [];
        $total = count($reseñas);
        $sumaGeneral = 0;
        $sumaCalidadPrecio = 0;
        $sumaAtencion = 0;
        $sumaFlexibilidad = 0;
    
        // Para desglose de calificaciones
        $breakdown = [
            "Excelente" => 0, 
            "Muy bueno" => 0, 
            "Regular" => 0,   
            "Malo" => 0,      
            "Muy malo" => 0,  
        ];
    
        foreach ($reseñas as $reseña) {
            $comentador = User::find($reseña->id_comentador)->with(['imagenProfile']);
        
            if ($comentador) {
                if ($comentador->imagenProfile) {
                    $comentador->foto_perfil_url = $baseUrl . "/storage/" . $comentador->imagenProfile->url;
                }
        
                $resultado[] = [
                    'id' => $reseña->id,
                    'user' => $comentador->username,
                    'user_image' => $comentador->foto_perfil_url ?? null,
                    'date' => $reseña->created_at->diffForHumans(),
                    'comment' => $reseña->comentario,
                    'rating' => $reseña->rate_general,
                ];
            }
        
            // Actualizamos promedios y clasificaciones solo si hay comentador
            $sumaGeneral += $reseña->rate_general;
            $sumaCalidadPrecio += $reseña->rate_calidad_precio;
            $sumaAtencion += $reseña->rate_atencion;
            $sumaFlexibilidad += $reseña->rate_flexibilidad;
        
            switch ($reseña->rate_general) {
                case 5: $breakdown["Excelente"]++; break;
                case 4: $breakdown["Muy bueno"]++; break;
                case 3: $breakdown["Regular"]++; break;
                case 2: $breakdown["Malo"]++; break;
                case 1: $breakdown["Muy malo"]++; break;
            }
        }        
    
        return response()->json([
            "mensaje" => "Reseñas obtenidas con éxito",
            "reseñas" => $resultado,
            "averageRating" => $total > 0 ? round($sumaGeneral / $total, 2) : 0,
            "totalReviews" => $total,
            "ratingsBreakdown" => $breakdown,
            "promedios" => [
                "calidad_precio" => $total > 0 ? round($sumaCalidadPrecio / $total * 20) : 0,
                "atencion" => $total > 0 ? round($sumaAtencion / $total * 20) : 0,
                "flexibilidad" => $total > 0 ? round($sumaFlexibilidad / $total * 20) : 0,
            ],
        ], 200);
    }    

    public function obtenerReseñasBasicas($correo_user) {
        $user = User::where("correo", $correo_user)->first();
    
        if (!$user) {
            return response()->json([
                "mensaje" => "Usuario no encontrado",
                "code" => 404,
            ], 404);
        }
    
        $reseñas = OpinionUser::where("id_comentado", $user->id)->get();
        $total = $reseñas->count();
        $sumaGeneral = $reseñas->sum('rate_general');
    
        return response()->json([
            "mensaje" => "Reseñas básicas obtenidas con éxito",
            "user" => $user,
            "averageRating" => $total > 0 ? round($sumaGeneral / $total, 2) : 0,
            "totalReviews" => $total,
        ], 200);
    }    

    public function obtenerInformacion($correo_user) {
        $user = User::where("correo", $correo_user)->first();
    
        if (!$user) {
            return response()->json([
                "mensaje" => "Usuario no encontrado",
                "code" => 404,
            ], 404);
        };

        $estilos = UserEstilos::where("id_user", $user->id)->first();

        $publicaciones = Publicacion::where("id_user", $user->id)->count();
        $compras = PublicacionVenta::where("id_comprador", $user->id)
            ->where("estado_venta", "!=", 1)
            ->count();
        $ventas = PublicacionVenta::where("id_vendedor", $user->id)
            ->where("estado_venta", "!=", 1)
            ->count();

        return response()->json([
            "mensaje" => "Informacion básica obtenidas con éxito",
            "estilos" => $estilos,
            "publicaciones" => $publicaciones,
            "compras" => $compras,
            "ventas" => $ventas
        ], 200);
    }
    
    public function obtenerDescDeVc($correo_user) {
        $user = User::where("correo", $correo_user)->first();
    
        if (!$user) {
            return response()->json([
                "mensaje" => "Usuario no encontrado",
                "code" => 404,
            ], 404);
        }
    
        $ventas = $user->ventas->count();
        $rol = $ventas > 5 ? "vendedor" : "comprador";
    
        // VERIFICACIÓN
        if ($user->verificado) {
            $confianza = "¡Verificado por VC! Lo aceptamos como $rol confiable en la comunidad.";
        } else {
            $confianza = "Todavía no fue verificado por VC, te recomendamos interactuar con precaución hasta conocerlo mejor.";
        }
    
        // ACTIVIDAD COMO COMPRADOR
        $ofertas = $user->ofertas->count();
        $guardados = $user->guardados->count();
        $actividad = ($ofertas + $guardados) > 5 ? "Se nota activo como comprador, haciendo ofertas y guardando publicaciones." : "Por ahora, parece que solo está explorando la app.";
    
        // OPINIONES
        $opiniones = $user->opinionesRecibidas;
        $cantidadOpiniones = $opiniones->count();
    
        if ($cantidadOpiniones < 3) {
            $opinion = "Aún no recibió muchas reseñas, así que es difícil sacar conclusiones sobre su comportamiento.";
            $comportamiento = "Con el tiempo, podremos saber mejor cómo es en las transacciones.";
        } else {
            $rateGeneral = $opiniones->avg('rate_general');
            $rateAtencion = $opiniones->avg('rate_atencion') ?? 0;
            $rateFlexibilidad = $opiniones->avg('rate_flexibilidad') ?? 0;
    
            if ($rateGeneral >= 4) {
                $opinion = "La comunidad tiene muy buena opinión de él (★" . round($rateGeneral, 1) . ").";
            } elseif ($rateGeneral >= 2.5) {
                $opinion = "Las opiniones son mixtas (★" . round($rateGeneral, 1) . "), puede mejorar con más experiencias.";
            } else {
                $opinion = "Ha recibido algunas valoraciones bajas (★" . round($rateGeneral, 1) . ").";
            }
    
            $comportamiento = "Como $rol, se destaca con una atención de " . round($rateAtencion, 1) . " y flexibilidad de " . round($rateFlexibilidad, 1) . ".";
        }
    
        // ANTIGÜEDAD
        $dias = now()->diffInDays($user->created_at);
        if ($dias < 30) {
            $antiguedad = "Es nuevo en VC, se unió hace apenas $dias días.";
        } elseif ($dias < 180) {
            $antiguedad = "Ya lleva un tiempo con nosotros, está aprendiendo a moverse por la app.";
        } else {
            $antiguedad = "Tiene bastante experiencia en VC, con más de $dias días en la comunidad.";
        }
    
        // MENSAJE FINAL
        $mensaje = "Este usuario es principalmente $rol. $confianza $actividad $opinion $comportamiento $antiguedad";
    
        return response()->json([
            "mensaje" => $mensaje,
            "code" => 200,
        ]);
    }
    
    public function obtenerNotificaciones() {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'message' => 'Usuario no encontrado'
            ], 404);
        };

        $notificacionesNoLeidas = UserNotificacion::where("user_id", $user->id)
            ->where("leido", 0)
            ->orderBy('created_at', 'desc') // Ordenar de las más nuevas a las más antiguas
            ->get();

        // Obtener las notificaciones leídas, ordenadas de las más recientes a las más antiguas
        $notificacionesLeidas = UserNotificacion::where("user_id", $user->id)
            ->where("leido", 1)
            ->orderBy('created_at', 'desc') // Ordenar de las más recientes a las más antiguas
            ->get();

        Carbon::setLocale('es');
        // Para las notificaciones leídas
        foreach($notificacionesLeidas as $notificacionLeida) {
            $tipoDeNotificacion = NotificacionTipo::find($notificacionLeida->notificacion_tipo_id);
            $notificacionLeida->notificacion_tipo_id = $tipoDeNotificacion;
            $notificacionLeida->fecha_relativa = $notificacionLeida->updated_at->diffForHumans();
        };

        // Para las notificaciones no leídas
        foreach($notificacionesNoLeidas as $notificacionNoLeida) {
            $notificacionNoLeida->leido = 1;
            $notificacionNoLeida->save();
            $tipoDeNotificacion = NotificacionTipo::find($notificacionNoLeida->notificacion_tipo_id);
            $notificacionNoLeida->notificacion_tipo_id = $tipoDeNotificacion;
            $notificacionNoLeida->fecha_relativa = $notificacionNoLeida->created_at->diffForHumans();
        };

        
        return response()->json([
            "mensaje" => "Notificaciones obtenidas con exito",
            "notifiaciones_leidas" => $notificacionesLeidas,
            "notifiaciones_noLeidas" => $notificacionesNoLeidas, 
        ], 200);
    }

    public function reportarPublicacion(Request $request) {
        $user = auth()->user();
    
        if (!$user) {
            return response()->json([
                'message' => 'Usuario no encontrado'
            ], 404);
        }
    
        $reporteExiste = ReportePublicacion::where("id_creador", $user->id)
            ->where("id_publicacion", $request->publicacion)
            ->exists();
    
        if ($reporteExiste) {
            return response()->json([
                'message' => '¡Publicación ya reportada!'
            ], 400);
        }
    
        $publicacion = Publicacion::find($request->publicacion);
    
        if (!$publicacion) {
            return response()->json([
                'message' => 'Publicación no encontrada'
            ], 404);
        }
    
        $creadorPublicacion = User::find($publicacion->id_user);
    
        // Guardar el reporte
        $reporte = ReportePublicacion::create([
            'id_publicacion' => $publicacion->id,
            'id_creador' => $user->id,
            'id_dueño_publicacion' => $publicacion->id_user,
            'titulo' => $request->titulo["category"],
            'descripcion' => $request->texto,
        ]);
    
        // Tipo de notificaciones
        $tipoNotificacionReportas = NotificacionTipo::find(13); // reportaste_publicacion
        $tipoNotificacionReportado = NotificacionTipo::find(15); // publicacion_reportada
    
        // Mensaje para quien REPORTA
        $mensajeReportas = str_replace(
            ['{{prenda}}'],
            ['<span style="color:#864a00;">' . e($publicacion->nombre) . '</span>'],
            $tipoNotificacionReportas->mensaje
        );
    
        // Notificación para quien REPORTA
        UserNotificacion::create([
            'user_id' => $user->id,
            'notificacion_tipo_id' => $tipoNotificacionReportas->id,
            'mensaje' => $mensajeReportas,
            'leido' => 0,
            'fecha_creacion' => now(),
            'fecha_visto' => null,
            'ruta_destino' => "/publicaciones/{$publicacion->id}",
        ]);
    
        Mail::to($user->correo)->send(new EmailReportastePublicacion($user->correo, $publicacion->nombre));
    
        // Mensaje para el DUEÑO de la publicación
        $mensajeReportado = str_replace(
            ['{{prenda}}'],
            ['<span style="color:#864a00;">' . e($publicacion->nombre) . '</span>'],
            $tipoNotificacionReportado->mensaje
        );
    
        // Notificación para el DUEÑO de la publicación reportada
        UserNotificacion::create([
            'user_id' => $creadorPublicacion->id,
            'notificacion_tipo_id' => $tipoNotificacionReportado->id,
            'mensaje' => $mensajeReportado,
            'leido' => 0,
            'fecha_creacion' => now(),
            'fecha_visto' => null,
            'ruta_destino' => "/publicaciones/{$publicacion->id}",
        ]);
    
        Mail::to($creadorPublicacion->correo)->send(new EmailPublicacionReportada($creadorPublicacion->correo, $publicacion->nombre));
    
        return response()->json([
            "mensaje" => "Reporte enviado con éxito",
        ], 200);
    }    

    public function reportarUsuario(Request $request) {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'message' => 'Usuario no encontrado'
            ], 404);
        };

        $reporteExiste = ReporteUsuario::where("id_creador", $user->id)
            ->where("id_usuario_reportado", $request->reportado_id)
            ->exists();

        $reportado = User::find($request->reportado_id);

        if ($reporteExiste) {
            return response()->json([
                'message' => '¡Usuario ya reportada!'
            ], 400);
        };

        $reporte = ReporteUsuario::create([
            'id_creador' => $user->id,
            'id_usuario_reportado' => $request->reportado_id,
            'titulo' => $request->titulo["category"],
            'descripcion' => $request->texto,
        ]);
        
        //reportas
        $tipoNotificacionReportas = NotificacionTipo::find(12);
        $mensajeReportas = str_replace(
            ['{{nombre_reportado}}'],
            ['<span style="color:#864a00;">' . e($reportado->nombre) . '</span>'],
            $tipoNotificacionReportas->mensaje
        );
        
        UserNotificacion::create([
            'user_id' => $user->id,
            'notificacion_tipo_id' => $tipoNotificacionReportas->id,
            'mensaje' => $mensajeReportas,
            'leido' => 0,
            'fecha_creacion' => now(),
            'fecha_visto' => null,
            'ruta_destino' => "/perfil/{$reportado->correo}",
        ]);        

        Mail::to($user->correo)->send(new EmailReportasteCuenta($user->correo, $reportado->nombre));
        //te reportaron
        $tipoNotificacionReportado = NotificacionTipo::find(14);
        $mensajeReportado = $tipoNotificacionReportado->mensaje; // No hay variables a reemplazar

        UserNotificacion::create([
            'user_id' => $reportado->id,
            'notificacion_tipo_id' => $tipoNotificacionReportado->id,
            'mensaje' => $mensajeReportado,
            'leido' => 0,
            'fecha_creacion' => now(),
            'fecha_visto' => null,
            'ruta_destino' => $tipoNotificacionReportado->ruta_destino,
        ]);

        Mail::to($reportado->correo)->send(new EmailCuentaReportada($reportado->correo));
        
        return response()->json([
            "mensaje" => "Reporte enviado con exito",
        ], 200);
    }

    public function solicitarVerificado() {
        $user = auth()->user();
    
        if (!$user) {
            return response()->json([
                'message' => 'Usuario no encontrado'
            ], 404);
        }
    
        $solicitudExistente = UserSolicitud::where("user_id", $user->id)->exists();
        if ($solicitudExistente) {
            return response()->json([
                'message' => '¡Solicitud enviada activa!'
            ], 400);
        }
    
        // Crear solicitud
        $solicitud = UserSolicitud::create([
            "user_id" => $user->id
        ]);
    
        // Notificación (ID 16)
        $tipoNotificacion = NotificacionTipo::find(16);
        $mensaje = $tipoNotificacion->mensaje; // No tiene variables para reemplazar
    
        UserNotificacion::create([
            'user_id' => $user->id,
            'notificacion_tipo_id' => $tipoNotificacion->id,
            'mensaje' => $mensaje,
            'leido' => 0,
            'fecha_creacion' => now(),
            'fecha_visto' => null,
            'ruta_destino' => $tipoNotificacion->ruta_destino ?? '/',
        ]);
    
        // Enviar correo
        Mail::to($user->correo)->send(new EmailVerificacionSolicitada($user->correo));
    
        return response()->json([
            "mensaje" => "Solicitud enviada con éxito",
        ], 200);
    }    
}
