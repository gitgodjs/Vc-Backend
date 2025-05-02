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
use App\Models\ChatMensaje;
use App\Models\PublicacionVenta;
use App\Models\PublicacionOferta;
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
        $categoria = RopaCategorias::where("category", $request->categoria["category"])->first();
        $prenda = Prendas::where("prenda", $request->categoria["name"])->first();
        $estado = EstadoRopa::where("estado", $request->estado)->first();
        $tipo = RopaTipo::where("tipo", $request->tipo)->first();
        
        $publicacion = Publicacion::create([
            'id_user' => $user->id, 
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

        return response()->json([
            "publicacion" => $publicacion,
        ]);
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
    
        $publicaciones = Publicacion::where("id_user", $userProfile_id)
            ->skip($offset)
            ->take($limit)
            ->get();
    
        Carbon::setLocale('es');
        
        $publicacionesFormateadas = $publicaciones->map(function ($publicacion) use ($user) {
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

        $publicacionesTotales = Publicacion::where("id_user", $userProfile_id)->count();
        $hasMore = ($publicacionesTotales > $offset + $limit);
            
        return response()->json([
            'message' => 'Publicaciones obtenidas!',
            'publicaciones' => $publicacionesFormateadas,
            'publicacionesTotales' => $publicacionesTotales,
            'page' => $page,
            'hasMore' => $hasMore
        ], 200);
    } 
    
    public function getPublicacionesGuardadasProfile($user_id, $userProfile_id, $page) {
        $limit = 5;
        $userProfile = User::find($userProfile_id);
    
        if (!$userProfile) {
            return response()->json([
                "mensaje" => "Usuario no encontrado!"
            ], 404); 
        }

        $user = User::find($user_id);

        $offset = ($page - 1) * $limit;
    
        $publicacionesIds = PublicacionGuardada::where("user_id", $userProfile_id)
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
                'ubicacion' => $publicacion->ubicacion,
                'fecha_publicacion' => Carbon::parse($publicacion->created_at)->diffForHumans(), 
                'fecha_original' => $publicacion->created_at, 
                'guardada' => true,
            ];
        });

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

    public function getPublicacionesRecomendadas(Request $request, $user_id, $page) {
        $user = auth()->user();
    
        if(!$user) {
            return response()->json([
                'message' => 'Usuario no encontrado!',
            ], 404);
        };
    
        $limit = 5;
        $offset = ($page - 1) * $limit;
    
        $publicacionesTotales = Publicacion::where('id_user', '!=', $user_id)->count();

        $publicaciones = Publicacion::where("id_user", '!=', $user_id)
            ->skip($offset)
            ->take($limit)
            ->get();
    
        Carbon::setLocale('es');
        
        $publicaciones = $publicaciones->map(function ($publicacion) use ($user) {     
            $guardada = false;
            
            if ($user) {
                $guardada = PublicacionGuardada::where('id_publicacion', $publicacion->id)
                    ->where('user_id', $user->id)
                    ->exists();
            };
            
            return [
                'id' => $publicacion->id,
                'id_creador' => $publicacion->id_user,
                'precio' => $publicacion->precio,
                'ubicacion' => $publicacion->ubicacion,
                'imagenUrl' => $publicacion->imagen,
                'guardada' => $guardada,
            ];
        });
    
        $hasMore = ($publicacionesTotales > $offset + $limit);
            
        return response()->json([
            'message' => 'Publicaciones obtenidas!',
            'publicaciones' => $publicaciones,
            'publicacionesTotales' => $publicacionesTotales,
            'page' => $page,
            'hasMore' => $hasMore
        ], 200);
    }

    public function getPublicacionesGuardadasHome(Request $request, $user_id, $page) {
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
                'precio' => $publicacion->precio,
                'ubicacion' => $publicacion->ubicacion,
                'imagenUrl' => $publicacion->imagen,
                'guardada' => true,
            ];
        });
    
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
    
        if(!$user) {
            return response()->json([
                'message' => 'Usuario no encontrado!',
            ], 404);
        };
    
        $limit = 10;
        $offset = ($page - 1) * $limit;
    
        $publicacionesTotales = Publicacion::where('id_user', '!=', $user_id)->count();

        $publicaciones = Publicacion::where("id_user", '!=', $user_id)
            ->skip($offset)
            ->take($limit)
            ->get();
    
        Carbon::setLocale('es');
        
        $publicaciones = $publicaciones->map(function ($publicacion) use ($user) {     
            $guardada = false;
            
            if ($user) {
                $guardada = PublicacionGuardada::where('id_publicacion', $publicacion->id)
                    ->where('user_id', $user->id)
                    ->exists();
            };
            
            return [
                'id' => $publicacion->id,
                'id_creador' => $publicacion->id_user,
                'precio' => $publicacion->precio,
                'ubicacion' => $publicacion->ubicacion,
                'imagenUrl' => $publicacion->imagen,
                'guardada' => $guardada,
            ];
        });
    
        $hasMore = ($publicacionesTotales > $offset + $limit);
            
        return response()->json([
            'message' => 'Publicaciones obtenidas!',
            'publicaciones' => $publicaciones,
            'publicacionesTotales' => $publicacionesTotales,
            'page' => $page,
            'hasMore' => $hasMore
        ], 200);
    }
    
    public function getPublicacionesCategoria(Request $request, $page) {
        $limit = 10; 
        $offset = ($page - 1) * $limit;
        $user = auth()->user();
    
        $categoria = RopaCategorias::find($request->categoria);
        $publicacion = Publicacion::find($request->id);
    
        $query = Publicacion::where("categoria", $request->categoria)
            ->when($user, function ($query) use ($user, $publicacion) {
                return $query->where("id_user", "!=", $user->id)
                    ->where("id", "!=", $publicacion->id);
            });
    
        $publicaciones = $query->skip($offset)
            ->take($limit)
            ->get()
            ->map(function ($publicacion) use ($user, $categoria) {
                $guardada = false;
        
                if ($user) {
                    $guardada = PublicacionGuardada::where('id_publicacion', $publicacion->id)
                        ->where('user_id', $user->id)
                        ->exists();
                }
        
                return [
                    'id' => $publicacion->id,
                    'id_creador' => $publicacion->id_user,
                    'categoria' => $categoria->category,
                    'precio' => $publicacion->precio,
                    'ubicacion' => $publicacion->ubicacion,
                    'imagenUrl' => $publicacion->imagen,
                    'guardada' => $guardada,
                ];
            });
    
        $publicacionesTotales = $query->count(); 
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
    
        if(!$user) {
            return response()->json(["mensaje" => "Usuario no encontrado"], 404);
        };

        $query = Publicacion::whereNull('deleted_at');
        $filters = $request->only(['categoria', 'talla', 'ciudad', 'prenda', 'search']);
        
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
                        if($value != "Todos") {
                            $query->where('categoria', $value);
                        }
                        break;
                        
                    case 'search':
                            $searchWords = explode(' ', $value);
                            $query->where(function($q) use ($searchWords) {
                                foreach ($searchWords as $word) {
                                    $q->orWhere('nombre', 'LIKE', '%'.trim($word).'%')
                                        ->orWhere('descripcion', 'LIKE', '%'.trim($word).'%');
                                }
                            });
                        break;
                };
            };
        };
        
        $perPage = 10;
        $page = $request->input('page', 1);
        
        $publicaciones = $query->with(['imagen'])
            ->paginate($perPage, ['*'], 'page', $page);
      
        $publicaciones->getCollection()->transform(function ($publicacion) use ($user) {
            $guardada = PublicacionGuardada::where('id_publicacion', $publicacion->id)
                ->where('user_id', $user->id)
                ->exists();
    
            $publicacion->imagenUrl = $publicacion->imagen; 
            $publicacion->guardada = $guardada;
            return $publicacion;
        });
        return response()->json([
            "mensaje" => "Publicaciones obtenidas",
            "publicaciones" => $publicaciones->items(),
            "hasMore" => $publicaciones->hasMorePages(),
            "publicacionesTotales" => $publicaciones->total()
        ], 200);
    }

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
}