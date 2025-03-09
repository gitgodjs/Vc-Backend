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
        $categoria = RopaCategorias::where("category", $request->categoria)->first();
        $prenda = Prendas::where("prenda", $request->prenda)->first();
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

        $categoria = RopaCategorias::find($request->categoria["id"]);
        $estado = EstadoRopa::find($request->estado["id"]);
        $prenda = Prendas::where("prenda", $request->prenda)->first();
        $tipo = RopaTipo::where("tipo", $request->tipo["category"])->first();

        $publicacion->update([
            'nombre' => $request->titulo,
            'descripcion' => $request->descripcion,
            'ubicacion' => $request->ciudad,
            'precio' => $request->precio,
            'categoria' => $categoria->id,
            'talle' => $request->talla["category"],
            'tipo' => $tipo->id, 
            'estado_ropa' => $estado->id,
            'prenda' => $prenda->id,
        ]);


        return response()->json([
            "mensaje" => "Publicacion actualizada con exito!",
            "publicacion" => $publicacion
        ], 200);
    }

    public function getPresentacion() {
        $publicaciones = Publicacion::inRandomOrder()->take(10)->get();

        if(!$publicaciones) {
            return response()->json([
                "mensaje" => "Publicacion no obtenidas!",
                "code" => 400,
            ], 400);
        }

        $publicacionesTransformadas = [];

        foreach ($publicaciones as $publicacion) {
            $publicacionesTransformadas[] = [
                'id' => $publicacion->id,
                'id_creador' => $publicacion->id_user,
                'imagen' => $publicacion->imagen
            ];
        }

        $publicaciones = $publicacionesTransformadas;


        return response()->json([
            "mensaje" => "Publicacion obtenidas!",
            "publicaciones" => $publicaciones,
            "code" => 200,
        ], 200);
    }

    public function getPublicacion($user_id, $publicacion_id) {
        $publicacion = Publicacion::with(["imagenes"])->find($publicacion_id);
    
        if(!$publicacion) {
            return response()->json([
                "mensaje" => "No existe la publicacion",
                "code" => 404,
            ], 404);
        };

        $user = User::find($user_id);

        $baseUrl = env('APP_URL');
        $userPublicacion = User::with(["imagenProfile"])->find($publicacion->id_user);
        $userPublicacion->imagen = $baseUrl . "/storage/" . $userPublicacion->imagenProfile->url;

        if ($user->id == $publicacion->id_user) {
            $itsMe = true;
        } else {
            $itsMe = false;
            
            $publicacion->visitas = $publicacion->visitas + 1;
            $publicacion->save();
        };

        $imagenesUrls = [];
        foreach ($publicacion->imagenes as $imagen) {
            $imagenesUrls[] = $baseUrl . "/storage/" . $imagen->url;
        };

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

        Carbon::setLocale('es');

        $publicacionFormateada = [
            'id' => $publicacion->id,
            'id_creador' => $publicacion->id_user,
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
                'imagen' => $publicacion->imagen,
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
                'imagen' => $publicacion->imagen,
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
                'imagen' => $publicacion->imagen,
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
            ->get();
    
        Carbon::setLocale('es');
        
        $publicaciones = $publicacionesIds->map(function ($item) use ($user) { 
            $publicacion = Publicacion::find($item->id_publicacion); 
    
            return [
                'id' => $publicacion->id,
                'id_creador' => $publicacion->id_user,
                'precio' => $publicacion->precio,
                'ubicacion' => $publicacion->ubicacion,
                'imagen' => $publicacion->imagen,
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
                'imagen' => $publicacion->imagen,
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
        $limit = 5;
        $offset = ($page - 1) * $limit;
        
        $user = auth()->user();
        $categoria = RopaCategorias::where("category", $request->categoria)->first();
        $publicacion = Publicacion::find($request->id);

        $publicaciones = Publicacion::where("categoria", $categoria->id)
            ->when($user, function ($query) use ($user, $publicacion) {
                return $query->where("id_user", "!=", $user->id)
                    ->where("id", "!=", $publicacion->id);
            })
            ->skip($offset)
            ->take($limit)
            ->get();
    
        $publicaciones = $publicaciones->filter(function ($publicacion) use ($user) {
            if ($user) {
                return !PublicacionGuardada::where('id_publicacion', $publicacion->id)
                    ->where('user_id', $user->id)
                    ->exists();
            }
            return true; 
        })->map(function ($publicacion) use ($user, $categoria) {
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
                'imagen' => $publicacion->imagen,
                'guardada' => $guardada,
            ];
        });
    
        $publicacionesTotales = Publicacion::where("categoria", $categoria->id)
            ->when($user, function ($query, $user) {
                return $query->where("id_user", "!=", $user->id);
            })
            ->count();
    
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
        }

        // Luego se acomodara en orden de cantidad de ventas
        $users = User::where("username", "!=", null)->with(["imagenProfile"])->get();
    
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
                        $query->where('categoria', $value);
                        break;
                    case 'search':
                        $searchWords = explode(' ', $value);
                        
                        foreach ($searchWords as $word) {
                            $query->where('nombre', 'LIKE', '%' . trim($word) . '%');
                        }
                        break;
                };
            };
        };
        
        $perPage = 10;
        $page = $request->input('page', 1);
        
        $publicaciones = $query->with(['imagenes'])
            ->paginate($perPage, ['*'], 'page', $page);
        
        foreach ($publicaciones as $publicacion) {
            $guardada = PublicacionGuardada::where('id_publicacion', $publicacion->id)
                ->where('user_id', $user->id)
                ->exists(); 

            $publicacion->guardada = $guardada;
        }
        return response()->json([
            "mensaje" => "Obtenidas",
            "users" => $users,
            "publicaciones" => $publicaciones->items(),
            "hasMore" => $publicaciones->hasMorePages(),
            "publicacionesTotales" => $publicaciones->total()
        ], 200);
    }
}
