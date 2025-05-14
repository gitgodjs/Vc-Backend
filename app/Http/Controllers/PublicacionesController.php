<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Prendas;
use App\Models\RopaTipo;
use App\Models\UsersTalla;
use App\Models\UserCodigo;
use App\Models\EstadoRopa;
use App\Models\Publicacion;
use App\Models\RopaCategorias;
use Illuminate\Http\Request;
use App\Models\OpinionUser;
use App\Models\ChatMensaje;
use App\Models\PublicacionVenta;
use App\Models\PublicacionOferta;
use App\Models\NotificacionTipo;
use App\Models\UserNotificacion;
use App\Models\RopaEstilo;
use App\Models\ChatConversacion;
use App\Models\ImagePublicacion;
use App\Models\PublicacionGuardada;
use App\Mail\EmailCodeConfirmation;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
class PublicacionesController extends Controller
{   
    public function crearPublicacion(Request $request)
    {
        $user = auth()->user();
        
        // Obtener los detalles de la prenda, estado, tipo, etc.
        $categoria = RopaCategorias::where("category", $request->categoria["category"])->first();
        $prenda = Prendas::where("prenda", $request->categoria["name"])->first();
        $estado = EstadoRopa::where("estado", $request->estado)->first();
        $tipo = RopaTipo::where("tipo", $request->tipo)->first();
        $estilo = RopaEstilo::where("estilo", $request->estilo)->first();
        
        // Contar las publicaciones del usuario
        $publicacionesCount = Publicacion::where("id_user", $user->id)->count();
    
        // Crear la nueva publicación
        $publicacion = Publicacion::create([
            'id_user' => $user->id, 
            'id_estilo' => $estilo->id,
            'nombre' => $request->titulo,
            'descripcion' => $request->descripcion,
            'ubicacion' => $request->ciudad,
            'precio' => $request->precio,
            'categoria' => $categoria->id,
            'talle' => $request->talla,
            'tipo' => $tipo->id, 
            'estado_ropa' => $estado->id,
            'prenda' => $prenda->id, 
            'estado_publicacion' => 1,
        ]);
    
        // Obtener la plantilla de la notificación correspondiente
        $tipoNotificacion = NotificacionTipo::where('clave', $publicacionesCount > 0 ? 'nueva_publicacion' : 'primera_publicacion')->first();
    
        // Reemplazar las variables de la plantilla con datos reales
        $mensaje = str_replace('{{prenda}}', $request->titulo, $tipoNotificacion->mensaje);
    
        // Crear la notificación para el usuario
        UserNotificacion::create([
            'user_id' => $user->id,
            'notificacion_tipo_id' => $tipoNotificacion->id,
            'mensaje' => $mensaje,
            'leido' => 0,
            'fecha_creacion' => now(),
            'fecha_visto' => null,
            'ruta_destino' => "/publicaciones/{$publicacion->id}",
        ]);
    
        // Respuesta con los datos de la publicación
        return response()->json([
            "mensaje" => "Publicación subida con éxito",
            "publicacion" => $publicacion,
        ], 200);
    }    

    public function eliminarPublicacion(Request $request, $publicacion_id) {
        $publicacion = Publicacion::find($publicacion_id);
        
        if(!$publicacion) {
            return response()->json([
                "mensaje" => "No existe la publicacion",
                "code" => 404,
            ], 404);
        };
        
        $images_publicacion = ImagePublicacion::where("id_publicacion", $publicacion_id)->get();
        foreach ($images_publicacion as $image) {
            if (Storage::disk('public')->exists($image->url)) {
                Storage::disk('public')->delete($image->url); 
            }

            $image->delete();
        };

        $publicacion->delete();

        return response()->json([
            "mensaje" => "Publicacion eliminada con exito",
            "code" => 200,
        ], 200);
    }

    public function editarPublicacion(Request $request, $publicacion_id) {
        $publicacion = Publicacion::find($publicacion_id);

        if(!$publicacion) {
            return response()->json([
                "mensaje" => "No existe la publicacion",
                "code" => 404,
            ], 404);
        };

        $categoria = RopaCategorias::where("category", $request->categoria["category"])->first();
        $prenda = Prendas::where("prenda", $request->categoria["name"])->first();
        $tipo = RopaTipo::where("tipo", $request->tipo)->first();
        $estado = EstadoRopa::where("estado", $request->estado)->first();

        $publicacion->update([
            'nombre' => $request->titulo,
            'descripcion' => $request->descripcion,
            'ubicacion' => $request->ciudad,
            'precio' => $request->precio,
            'categoria' => $categoria->id,
            'prenda' => $prenda->id,
            'talle' => $request->talla,
            'tipo' => $tipo->id, 
            'estado_ropa' => $estado->id,
        ]);

        return response()->json([
            "mensaje" => "Publicacion actualizada con exito!",
            "publicacion" => $publicacion,
        ], 200);
    }

    /////////////////////////////////

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

    public function getPublicacion($user_id, $publicacion_id) {
        $publicacion = Publicacion::with(["imagenes"])->find($publicacion_id);
    
        if (!$publicacion) {
            return response()->json([
                "mensaje" => "No existe la publicación",
                "code" => 404,
            ], 404);
        }
    
        $user = User::find($user_id);
        $baseUrl = env('APP_URL');
    
        $userPublicacion = User::with(["imagenProfile"])->find($publicacion->id_user);
        if ($userPublicacion->imagenProfile !== null) {
            $userPublicacion->imagen = $baseUrl . "/storage/" . $userPublicacion->imagenProfile->url;
        }
    
        $itsMe = $user->id === $publicacion->id_user;
    
        $yaFueOfertada = false;
        $mensajeOferta = null;

        if (!$itsMe) {
            $conversacion = ChatConversacion::where(function ($query) use ($user, $publicacion) {
                $query->where('emisor_id', $user->id)
                    ->where('receptor_id', $publicacion->id_user);
            })->orWhere(function ($query) use ($user, $publicacion) {
                $query->where('emisor_id', $publicacion->id_user)
                    ->where('receptor_id', $user->id);
            })->first();
    
            if ($conversacion) {
                $oferta = PublicacionOferta::whereHas('mensaje', function ($q) use ($conversacion) {
                    $q->where('conversation_id', $conversacion->id);
                })
                ->where('publicacion_id', $publicacion->id)
                ->whereNull('deleted_at')
                ->with('mensaje')
                ->orderBy('created_at', 'desc')
                ->first();
            
                if ($oferta && $oferta->estado_oferta_id != 3) {
                    $yaFueOfertada = true;
                    $mensajeOferta = $oferta->mensaje;
                }
            };
    
            $publicacion->visitas += 1;
            $publicacion->save();
        };
    
        $imagenesUrls = [];
        foreach ($publicacion->imagenes as $imagen) {
            $imagenesUrls[] = $baseUrl . "/storage/" . $imagen->url;
        }
    
        $estado_ropa = EstadoRopa::find($publicacion->estado_ropa);
        $prenda = Prendas::find($publicacion->prenda);
        $categoria = RopaCategorias::find($publicacion->categoria);
        $tipo = RopaTipo::find($publicacion->tipo);

        if($publicacion->id_estilo != null){
            $estilo = RopaEstilo::find($publicacion->id_estilo);
        };

        $guardada = PublicacionGuardada::where('id_publicacion', $publicacion->id)
            ->where('user_id', $user->id)
            ->exists();
    
        Carbon::setLocale('es');
        $creador = User::find($publicacion->id_user);
    
        $publicacionFormateada = [
            'id' => $publicacion->id,
            'creador' => $creador,
            'nombre' => $publicacion->nombre,
            'descripcion' => $publicacion->descripcion,
            'precio' => $publicacion->precio,
            'imagenes' => $imagenesUrls,
            'estado_publicacion' => $publicacion->estado_publicacion,
            'estado_ropa' => $estado_ropa->estado,
            'estilo_ropa' => $estilo->estilo ?? null,
            'categoria' => $categoria->category,
            'prenda' => $prenda->prenda,
            'talle' => $publicacion->talle,
            'tipo' => $tipo->tipo,
            'ubicacion' => $publicacion->ubicacion,
            'visitas' => $publicacion->visitas,
            'fecha_publicacion' => Carbon::parse($publicacion->created_at)->diffForHumans(),
            'fecha_original' => $publicacion->created_at,
            'images_array' => $publicacion->imagenes,
            'guardada' => $guardada,
        ];
    
        return response()->json([
            "mensaje" => "Publicación obtenida con éxito",
            "publicacion" => $publicacionFormateada,
            "userPublicacion" => $userPublicacion,
            "itsMe" => $itsMe,
            "ofertaExistente" => $yaFueOfertada,
            "mensajeOferta" => $mensajeOferta,
        ], 200);
    }    

    public function getPublicacionesUser($user_id, $userProfile_id, $page) {
        $limit = 5;
        $userProfile = User::find($userProfile_id);
    
        if (!$userProfile) {
            return response()->json([
                "mensaje" => "Usuario no encontrado!"
            ], 404); 
        }

        $user = User::find($user_id);

        $offset = ($page - 1) * $limit;
    
        $publicacionesUser = Publicacion::where("id_user", $userProfile_id)
            ->skip($offset)
            ->take($limit)
            ->get();
    
        Carbon::setLocale('es');
        
        $publicaciones = $publicacionesUser->map(function ($publicacion) use ($user) {
            $estado_ropa = EstadoRopa::find($publicacion->estado_ropa);
            $prenda = Prendas::find($publicacion->prenda);
            $categoria = RopaCategorias::find($publicacion->categoria);
            $tipo = RopaTipo::find($publicacion->tipo);
            $guardada = false;
            
            if ($user) {
                $guardada = PublicacionGuardada::where('id_publicacion', $publicacion->id)
                    ->where('user_id', $user->id)
                    ->exists();
            };

            return [
                'id' => $publicacion->id,
                'id_creador' => $publicacion->id_user,
                'nombre' => $publicacion->nombre,
                'descripcion' => $publicacion->descripcion,
                'precio' => $publicacion->precio,
                'imagenUrl' => $publicacion->imagen,
                'estado_publicacion' => $publicacion->estado_publicacion,
                'estado_ropa' => $estado_ropa->estado,
                'categoria' => $categoria->category,
                'prenda' => $prenda->prenda,
                'talle' => $publicacion->talle,
                'tipo' => $tipo->tipo,
                'ubicacion' => $publicacion->ubicacion,
                'fecha_publicacion' => Carbon::parse($publicacion->created_at)->diffForHumans(), 
                'fecha_original' => $publicacion->created_at, 
                'guardada' => $guardada,
            ];
        });

        $publicaciones = $this->imageControll($publicaciones);
        $publicacionesTotales = Publicacion::where("id_user", $userProfile_id)->count();
        $hasMore = ($publicacionesTotales > $offset + $limit);
            
        return response()->json([
            'message' => 'Publicaciones obtenidas!',
            'publicaciones' => $publicaciones,
            'publicacionesTotales' => $publicacionesTotales,
            'page' => $page,
            'hasMore' => $hasMore
        ], 200);
    } 

    public function getPublicacionesEnCompra($page) {
        $limit = 5;
        $user = auth()->user();
    
        if (!$user) {
            return response()->json([
                "mensaje" => "Usuario no encontrado!"
            ], 404); 
        }

        $offset = ($page - 1) * $limit;
    
        $publicacionesIds = PublicacionVenta::where("id_comprador", $user->id)
            ->select('id_publicacion')
            ->distinct()
            ->skip($offset)
            ->take($limit)
            ->get();
    
        Carbon::setLocale('es');
        
        $publicaciones = $publicacionesIds->map(function ($id) use ($user) {
            $publicacion = Publicacion::find($id->id_publicacion);

            return [
                'id' => $publicacion->id,
                'id_creador' => $publicacion->id_user,
                'nombre' => $publicacion->nombre,
                'descripcion' => $publicacion->descripcion,
                'precio' => $publicacion->precio,
                'imagenUrl' => $publicacion->imagen,
                'estado_publicacion' => $publicacion->estado_publicacion,
            ];
        })->filter()->values();
        
        if ($publicaciones->isNotEmpty()) {
            $publicaciones = $this->imageControll($publicaciones);
        };        

        $publicacionesTotales = PublicacionVenta::where("id_comprador", $user->id)->count();
        $hasMore = ($publicacionesTotales > $offset + $limit);
            
        return response()->json([
            'message' => 'Publicaciones obtenidas!',
            'publicaciones' => $publicaciones,
            'publicacionesTotales' => $publicacionesTotales,
            'page' => $page,
            'hasMore' => $hasMore
        ], 200);
    }
    
    public function getPublicacionesGuardadasProfile($page) {
        $limit = 5;
        $user = auth()->user();
    
        if (!$user) {
            return response()->json([
                "mensaje" => "Usuario no encontrado!"
            ], 404); 
        };

        $offset = ($page - 1) * $limit;
    
        $publicacionesIds = PublicacionGuardada::where("user_id", $user->id)
            ->skip($offset)
            ->take($limit)
            ->get();
    
        Carbon::setLocale('es');
        
        $publicaciones = $publicacionesIds->map(function ($id) use ($user) {
            $publicacion = Publicacion::find($id->id_publicacion);

            if (!$publicacion) {
                return null; 
            }

            return [
                'id' => $publicacion->id,
                'id_creador' => $publicacion->id_user,
                'nombre' => $publicacion->nombre,
                'descripcion' => $publicacion->descripcion,
                'precio' => $publicacion->precio,
                'imagenUrl' => $publicacion->imagen,
                'estado_publicacion' => $publicacion->estado_publicacion,
                'ubicacion' => $publicacion->ubicacion,
                'fecha_publicacion' => Carbon::parse($publicacion->created_at)->diffForHumans(), 
                'fecha_original' => $publicacion->created_at, 
                'guardada' => true,
            ];
        })->filter()->values();
        
        if ($publicaciones->isNotEmpty()) {
            $publicaciones = $this->imageControll($publicaciones);
        };        
        
        $publicacionesTotales = PublicacionGuardada::where("user_id", $user->id)->count();
        $hasMore = ($publicacionesTotales > $offset + $limit);
            
        return response()->json([
            'message' => 'Publicaciones obtenidas!',
            'publicaciones' => $publicaciones,
            'publicacionesTotales' => $publicacionesTotales,
            'page' => $page,
            'hasMore' => $hasMore
        ], 200);
    }

    public function guardadosPublicacion(Request $request, $publicacion_id) {
        $publicacion = Publicacion::find($publicacion_id);
    
        if(!$publicacion) {
            return response()->json([
                'message' => 'Error al obtener la publicacion!'
            ], 404);
        };

        $user = auth()->user();

        $guardada = PublicacionGuardada::where('id_publicacion', $publicacion_id)
            ->where('user_id', $user->id)
            ->first();
        
        $estaGuardada = $guardada ? true : false;
        if(!$estaGuardada) {
            $guardadar = PublicacionGuardada::create([
                'id_publicacion' => $publicacion->id,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'message' => 'Publicacion guardada!',
                'publicacion' => $publicacion,
                'guardadar' => $guardadar
            ], 200);
        } else {
            $guardada->delete();
            return response()->json([
                'message' => 'Publicacion quitada de guardados!',
            ], 200);
        };
    }

    public function getPublicacionesRecomendadas(Request $request, $page) {
        $user = auth()->user();
    
        if (!$user) {
            return response()->json([
                'message' => 'Usuario no encontrado!',
            ], 404);
        }
    
        $limit = 10;
        $offset = ($page - 1) * $limit;
    
        // Obtener tallas del usuario
        $tallas = UsersTalla::where('user_id', $user->id)->first();
        $tallesArray = $tallas ? array_filter([
            $tallas->remeras,
            $tallas->pantalones,
            $tallas->shorts,
            $tallas->trajes,
            $tallas->vestidos,
            $tallas->abrigos,
            $tallas->calzados,
        ]) : [];
    
        // Obtener estilos favoritos del usuario
        $estilos = $user->estilos;
        $estiloIds = collect([
            $estilos->id_estilo_1 ?? null,
            $estilos->id_estilo_2 ?? null,
            $estilos->id_estilo_3 ?? null,
        ])->filter()->toArray();
    
        // Publicaciones guardadas
        $idsGuardados = PublicacionGuardada::where('user_id', $user->id)
            ->pluck('id_publicacion')
            ->toArray();
    
        // Consulta base con filtros condicionales
        $queryBase = Publicacion::where('id_user', '!=', $user->id)
            ->whereNotIn('id', $idsGuardados)
            ->when(!empty($user->ubicacion), function ($query) use ($user) {
                $query->where('ubicacion', $user->ubicacion);
            })
            ->when(!empty($estiloIds), function ($query) use ($estiloIds) {
                $query->whereIn('id_estilo', $estiloIds);
            })
            ->when(!empty($tallesArray), function ($query) use ($tallesArray) {
                $query->whereIn('talle', $tallesArray);
            });
    
        // Obtener publicaciones con filtros
        $publicaciones = (clone $queryBase)
            ->skip($offset)
            ->take($limit)
            ->get();
    
        // Si no se encontró nada, buscar sin filtros adicionales
        if ($publicaciones->isEmpty()) {
            $publicaciones = Publicacion::where('id_user', '!=', $user->id)
                ->whereNotIn('id', $idsGuardados)
                ->skip($offset)
                ->take($limit)
                ->get();
    
            $publicacionesTotales = Publicacion::where('id_user', '!=', $user->id)
                ->whereNotIn('id', $idsGuardados)
                ->count();
        } else {
            $publicacionesTotales = $queryBase->count();
        }
    
        Carbon::setLocale('es');
    
        $publicaciones = $publicaciones->map(function ($publicacion) {
            return [
                'id' => $publicacion->id,
                'id_creador' => $publicacion->id_user,
                'nombre' => $publicacion->nombre,
                'precio' => $publicacion->precio,
                'ubicacion' => $publicacion->ubicacion,
                'imagenUrl' => $publicacion->imagen,
                'guardada' => false,
            ];
        })->filter()->values();
        
        if ($publicaciones->isNotEmpty()) {
            $publicaciones = $this->imageControll($publicaciones);
        };

        $hasMore = ($publicacionesTotales > $offset + $limit);
    
        return response()->json([
            'message' => 'Publicaciones recomendadas obtenidas!',
            'publicaciones' => $publicaciones,
            'publicacionesTotales' => $publicacionesTotales,
            'page' => $page,
            'hasMore' => $hasMore
        ], 200);
    }       

    public function getPublicacionesGuardadas(Request $request, $user_id, $page) {
        $user = auth()->user();
    
        if(!$user) {
            return response()->json([
                'message' => 'Usuario no encontrado!',
            ], 404);
        };
    
        $limit = 5;
        $offset = ($page - 1) * $limit;
    
        $publicacionesTotales = PublicacionGuardada::where("user_id", $user_id)->count();
        
        $publicacionesIds = PublicacionGuardada::where("user_id", $user_id)
            ->skip($offset)
            ->take($limit)
            ->pluck('id_publicacion');
    
        Carbon::setLocale('es');
        
        $publicaciones = Publicacion::whereIn('id', $publicacionesIds)->get()->map(function ($publicacion) {
            return [
                'id' => $publicacion->id,
                'id_creador' => $publicacion->id_user,
                'nombre' => $publicacion->nombre,
                'precio' => $publicacion->precio,
                'ubicacion' => $publicacion->ubicacion,
                'imagenUrl' => $publicacion->imagen,
                'guardada' => true,
            ];
        })->filter()->values();
        
        if ($publicaciones->isNotEmpty()) {
            $publicaciones = $this->imageControll($publicaciones);
        };

        $hasMore = ($publicacionesTotales > $offset + $limit);
            
        return response()->json([
            'message' => 'Publicaciones obtenidas!',
            'publicaciones' => $publicaciones,
            'publicacionesTotales' => $publicacionesTotales,
            'page' => $page,
            'hasMore' => $hasMore
        ], 200);
    }

    public function getPublicacionesExplorar(Request $request, $user_id, $page) {
        $user = auth()->user();
    
        if (!$user) {
            return response()->json([
                'message' => 'Usuario no encontrado!',
            ], 404);
        }
    
        $limit = 10;
        $offset = ($page - 1) * $limit;
    
        $idsGuardados = PublicacionGuardada::where('user_id', $user->id)
            ->pluck('id_publicacion')
            ->toArray();
    
        $publicaciones = Publicacion::where('id_user', '!=', $user_id)
            ->whereNotIn('id', $idsGuardados)
            ->skip($offset)
            ->take($limit)
            ->get();
    
        $publicacionesTotales = Publicacion::where('id_user', '!=', $user_id)
            ->whereNotIn('id', $idsGuardados)
            ->count();
    
        Carbon::setLocale('es');
    
        $publicaciones = $publicaciones->map(function ($publicacion) {
            return [
                'id' => $publicacion->id,
                'id_creador' => $publicacion->id_user,
                'nombre' => $publicacion->nombre,
                'precio' => $publicacion->precio,
                'ubicacion' => $publicacion->ubicacion,
                'imagenUrl' => $publicacion->imagen,
                'guardada' => false,
            ];
        });
    
        $publicaciones = $this->imageControll($publicaciones);
    
        $hasMore = ($publicacionesTotales > $offset + $limit);
    
        return response()->json([
            'message' => 'Publicaciones obtenidas!',
            'publicaciones' => $publicaciones,
            'publicacionesTotales' => $publicacionesTotales,
            'page' => $page,
            'hasMore' => $hasMore
        ], 200);
    }   

    public function getPublicacionesFiltro(Request $request) {
        $user = auth()->user();
    
        if (!$user) {
            return response()->json(["mensaje" => "Usuario no encontrado"], 404);
        }
    
        $query = Publicacion::whereNull('deleted_at');
        $filters = $request->only(['categoria', 'talla', 'ciudad', 'prenda', 'search', 'estilo']);
    
        foreach ($filters as $key => $value) {
            if (!empty($value)) {
                switch ($key) {
                    case 'prenda':
                        $prenda = Prendas::where('prenda', $value)->first();
                        if (!$prenda) {
                            return response()->json([
                                "mensaje" => "Prenda no encontrada",
                                "publicaciones" => []
                            ], 200);
                        }
                        $query->where('prenda', $prenda->id);
                        break;
    
                    case 'talla':
                        $query->where('talle', $value);
                        break;
    
                    case 'ciudad':
                        $query->where('ubicacion', $value);
                        break;
    
                    case 'categoria':
                        if ($value != "Todos") {
                            $query->where('categoria', $value);
                        }
                        break;
    
                    case 'estilo':
                        $estilo = RopaEstilo::where('estilo', $value)->first();
                        if (!$estilo) {
                            return response()->json([
                                "mensaje" => "Estilo no encontrado",
                                "publicaciones" => [],
                                "publicacionesTotales" => 0
                            ], 200);
                        }
                        $query->where('id_estilo', $estilo->id);
                        break;
    
                    case 'search':
                        $searchWords = explode(' ', $value);
                        $query->where(function ($q) use ($searchWords) {
                            foreach ($searchWords as $word) {
                                $q->orWhere('nombre', 'LIKE', '%' . trim($word) . '%')
                                    ->orWhere('descripcion', 'LIKE', '%' . trim($word) . '%');
                            }
                        });
                        break;
                }
            }
        }
    
        $perPage = 10;
        $page = $request->input('page', 1);
    
        $publicaciones = $query->paginate($perPage, ['*'], 'page', $page);
    
        $hasMore = $publicaciones->hasMorePages();
        $publicacionesTotales = $publicaciones->total();
    
        $publicacionesMapped = $publicaciones->getCollection()->map(function ($publicacion) use ($user) {
            return [
                'id' => $publicacion->id,
                'id_creador' => $publicacion->id_user,
                'nombre' => $publicacion->nombre,
                'precio' => $publicacion->precio,
                'ubicacion' => $publicacion->ubicacion,
                'imagenUrl' => $publicacion->imagen, 
                'guardada' => PublicacionGuardada::where('id_publicacion', $publicacion->id)
                    ->where('user_id', $user->id)
                    ->exists(),
            ];
        })->filter()->values();
    
        if ($publicacionesMapped->isNotEmpty()) {
            $publicacionesMapped = $this->imageControll($publicacionesMapped);
        };
    
        return response()->json([
            "mensaje" => "Publicaciones obtenidas",
            "publicaciones" => $publicacionesMapped,
            "hasMore" => $hasMore,
            "publicacionesTotales" => $publicacionesTotales
        ], 200);
    }
        
    public function getPublicacionesEnVenta($page) {
        $limit = 5;
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                "mensaje" => "Usuario no encontrado!"
            ], 404); 
        }

        $offset = ($page - 1) * $limit;

        // Paginadas
        $publicaciones = PublicacionVenta::where("id_vendedor", $user->id)
            ->where("estado_venta", 1)
            ->skip($offset)
            ->take($limit)
            ->get();

        Carbon::setLocale('es');

        $publicacionesFormateadas = $publicaciones->map(function ($publicacionVenta) use ($user) {  
            $compradorPublicacion = User::find($publicacionVenta->id_comprador);
            $publicacion = Publicacion::find($publicacionVenta->id_publicacion);
            if($publicacion->imagen != null) {$publicacion->imagenUrl = $publicacion->imagen;}

            $oferta = PublicacionOferta::find($publicacionVenta->oferta_id);
            if (!$oferta) return null;

            $mensajeInicial = ChatMensaje::find($oferta->mensaje_id);
            if (!$mensajeInicial) return null;

            return [
                'id' => $publicacionVenta->id,
                'id_creador_publicacion' => $publicacion->id_user,
                'precio' => $publicacionVenta->precio,
                'imagenUrl' => $publicacion->imagenUrl,
                'estado_venta' => $publicacionVenta->estado_venta,
                'publicacionOriginal' => $publicacion,
                'compradorPublicacion' => $compradorPublicacion,
                'conversacion_id' => $mensajeInicial->conversation_id,
            ];
        })->filter(); 

        $publicacionesTotales = Publicacion::where("id_user", $user->id)->count();
        $hasMore = ($publicacionesTotales > $offset + $limit);

        return response()->json([
            'message' => 'Publicaciones obtenidas!',
            'publicaciones' => $publicacionesFormateadas->values(), 
            'publicacionesTotales' => $publicacionesTotales,
            'page' => $page,
            'hasMore' => $hasMore,
        ], 200);
    }

    /////////////////////////////////

    public function eliminarOferta(Request $request) {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                "Mensaje" => "Este usuario no existe"
            ], 404);
        }

        // Buscar la oferta en base al ID recibido
        $oferta = PublicacionOferta::find($request->oferta_id);

        if (!$oferta) {
            return response()->json([
                "Mensaje" => "Esta oferta no existe"
            ], 400);
        }

        $mensaje = ChatMensaje::find($oferta->mensaje_id);

        if ($mensaje) {
            $mensaje->delete(); 
        }

        $oferta->delete();

        $conversacion = ChatConversacion::find($mensaje->conversation_id);

        if ($conversacion) {
            $cantidadMensajes = ChatMensaje::where("conversation_id", $conversacion->id)
                ->whereNull("deleted_at")
                ->count();

            if ($cantidadMensajes < 1) {
                $conversacion->delete(); 
            }
        }

        return response()->json([
            "Mensaje" => "Oferta y mensaje eliminados. Conversación verificada."
        ], 200);
    }

    public function getEstadisticas($periodo) {
        $user = auth()->user();
    
        if (!$user) {
            return response()->json([
                "mensaje" => "Usuario no encontrado!"
            ], 404);
        }
        
        $baseUrl = env('APP_URL');
        $ventas = PublicacionVenta::where("id_vendedor", $user->id)->get();
        $estadisticas = [];

        $publicacionesDestacadas = null;
        $inicioMes = Carbon::now()->startOfMonth();
        $finMes = Carbon::now()->endOfMonth();

        $publicacionesMes = Publicacion::where('id_user', $user->id)
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$inicioMes, $finMes])
            ->get();

        if ($publicacionesMes->count()) {
            $masVista = $publicacionesMes->where('visitas', '>', 0)->sortByDesc('visitas')->first();
            $masGuardada = $publicacionesMes->sortByDesc(function ($pub) {
                return PublicacionGuardada::where('id_publicacion', $pub->id)->count();
            })->filter(function ($pub) {
                return PublicacionGuardada::where('id_publicacion', $pub->id)->count() > 0;
            })->first();

            $masOfertada = $publicacionesMes->sortByDesc(function ($pub) {
                return PublicacionOferta::where('publicacion_id', $pub->id)->count();
            })->filter(function ($pub) {
                return PublicacionOferta::where('publicacion_id', $pub->id)->count() > 0;
            })->first();

            $publicacionesDestacadas = [
                'mas_vista' => $masVista ? [
                    'id' => $masVista->id,
                    'titulo' => $masVista->nombre ?? null,
                    'precio' => $masVista->precio,
                    'imagenUrl' => $masVista->imagen ? $baseUrl . "/storage/" . $masVista->imagen->url : null,
                ] : null,

                'mas_guardada' => $masGuardada ? [
                    'id' => $masGuardada->id,
                    'titulo' => $masGuardada->nombre ?? null,
                    'precio' => $masGuardada->precio,
                    'imagenUrl' => $masGuardada->imagen ? $baseUrl . "/storage/" . $masGuardada->imagen->url : null,
                ] : null,

                'mas_ofertada' => $masOfertada ? [
                    'id' => $masOfertada->id,
                    'titulo' => $masOfertada->nombre ?? null,
                    'precio' => $masOfertada->precio,
                    'imagenUrl' => $masOfertada->imagen ? $baseUrl . "/storage/" . $masOfertada->imagen->url : null,
                ] : null,
            ];
        };

        if ($periodo === 'semana') {
            $diasSemana = [
                1 => ['nombre' => 'Lun', 'Ventas' => 0, 'Pendientes' => 0],
                2 => ['nombre' => 'Mar', 'Ventas' => 0, 'Pendientes' => 0],
                3 => ['nombre' => 'Mie', 'Ventas' => 0, 'Pendientes' => 0],
                4 => ['nombre' => 'Jue', 'Ventas' => 0, 'Pendientes' => 0],
                5 => ['nombre' => 'Vie', 'Ventas' => 0, 'Pendientes' => 0],
                6 => ['nombre' => 'Sab', 'Ventas' => 0, 'Pendientes' => 0],
                7 => ['nombre' => 'Dom', 'Ventas' => 0, 'Pendientes' => 0],
            ];
    
            foreach ($ventas as $venta) {
                $dia = Carbon::parse($venta->created_at)->dayOfWeekIso;
    
                if (!isset($diasSemana[$dia])) continue;
    
                if ($venta->estado_venta == 1) {
                    $diasSemana[$dia]['Pendientes']++;
                } else {
                    $diasSemana[$dia]['Ventas']++;
                }
            }
    
            foreach ($diasSemana as $info) {
                $estadisticas[] = [
                    'dia' => $info['nombre'],
                    'Ventas' => $info['Ventas'],
                    'Pendientes' => $info['Pendientes'],
                ];
            }
    
        } elseif ($periodo === 'mes') {
            // Inicializa todos los días del mes (1 al 31)
            for ($i = 1; $i <= 31; $i++) {
                $diasMes[str_pad($i, 2, '0', STR_PAD_LEFT)] = ['Ventas' => 0, 'Pendientes' => 0];
            }
    
            foreach ($ventas as $venta) {
                $dia = Carbon::parse($venta->created_at)->format('d');
    
                if ($venta->estado_venta == 1) {
                    $diasMes[$dia]['Pendientes']++;
                } else {
                    $diasMes[$dia]['Ventas']++;
                }
            }
    
            foreach ($diasMes as $dia => $valores) {
                $estadisticas[] = [
                    'dia' => (int)$dia, // lo devuelvo como número
                    'Ventas' => $valores['Ventas'],
                    'Pendientes' => $valores['Pendientes'],
                ];
            }
    
        } elseif ($periodo === 'año') {
            $meses = [
                1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
                5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
                9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'
            ];
    
            // Inicializa todos los meses
            $datosMes = [];
            foreach ($meses as $i => $nombre) {
                $datosMes[$i] = ['Ventas' => 0, 'Pendientes' => 0];
            }
    
            foreach ($ventas as $venta) {
                $mes = Carbon::parse($venta->created_at)->month;
    
                if ($venta->estado_venta == 1) {
                    $datosMes[$mes]['Pendientes']++;
                } else {
                    $datosMes[$mes]['Ventas']++;
                }
            }
    
            foreach ($datosMes as $mes => $valores) {
                $estadisticas[] = [
                    'mes' => $meses[$mes],
                    'Ventas' => $valores['Ventas'],
                    'Pendientes' => $valores['Pendientes'],
                ];
            }
        }
    
        return response()->json([
            "mensaje" => "Estadisticas obtenidas con exito",
            "graficoVentas" => $estadisticas,
            "publicacionesDestacadas" => $publicacionesDestacadas,
        ], 200);
    }      

    public function finalizarConCalificacion(Request $request, $publicacion_id, $comprador = false) {
        $user = auth()->user();
    
        if (!$user) {
            return response()->json([
                "mensaje" => "Usuario no autenticado"
            ], 404);
        }
    
        $reseña = $request->input('reseña');
        $rating = $request->input('rating');
        $receptorId = $request->input('receptor_id');
        $receptor = User::find($receptorId);
    
        $publicacion = Publicacion::find($publicacion_id);
        $publicacionVenta = PublicacionVenta::where("id_publicacion", $publicacion_id)->first();

        $reseña = OpinionUser::create([
            'id_comentador' => $user->id,
            'id_comentado' => $receptorId,
            'comentario' => $reseña,
            'rate_general' => $rating["general"],
            'rate_calidad_precio' => $rating["calidad_precio"],
            'rate_atencion' => $rating["atencion"],
            'rate_flexibilidad' => $rating["flexibilidad"],
        ]);

        if($comprador == false) {
            $publicacion->estado_publicacion = 3;
            $publicacionVenta->estado_venta = 2;
        } else {
            $publicacionVenta->estado_venta = 3;
        };
        $publicacionVenta->updated_at = now();
    
        $publicacion->save();
        $publicacionVenta->save();
        $reseña->save();

        // ✅ Notificación para quien CALIFICA (ID 10)
        $tipoEnvio = NotificacionTipo::find(10);
        UserNotificacion::create([
            'user_id' => $user->id,
            'notificacion_tipo_id' => 10,
            'mensaje' => str_replace(
                ['{{prenda}}', '{{usuario}}'],
                [
                    '<span style="color:#864a00;">' . e($publicacion->nombre) . '</span>',
                    '<span style="color:#864a00;">' . e($receptor->nombre) . '</span>'
                ],
                $tipoEnvio->mensaje
            ),
            'leido' => false,
            'fecha_creacion' => now(),
            'ruta_destino' => $tipoEnvio->ruta_destino,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ✅ Notificación para quien RECIBE la calificación (ID 11)
        $tipoRecibe = NotificacionTipo::find(11);
        UserNotificacion::create([
            'user_id' => $receptorId,
            'notificacion_tipo_id' => 11,
            'mensaje' => str_replace(
                ['{{prenda}}', '{{usuario}}'],
                [
                    '<span style="color:#864a00;">' . e($publicacion->nombre) . '</span>',
                    '<span style="color:#864a00;">' . e($user->nombre) . '</span>'
                ],
                $tipoRecibe->mensaje
            ),
            'leido' => false,
            'fecha_creacion' => now(),
            'ruta_destino' => $tipoRecibe->ruta_destino,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            "mensaje" => "Datos recibidos correctamente",
            "reseña" => $reseña,
            "rating" => $rating,
            "receptor_id" => $receptorId,
            "publicacion_id" => $publicacion_id
        ], 200);
    }
}