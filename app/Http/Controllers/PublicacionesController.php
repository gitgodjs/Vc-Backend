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
use App\Mail\EmailCodeConfirmation;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;


class PublicacionesController extends Controller
{   
    public function crearPublicacion(Request $request, $user_id)
    {
        $user = User::find($user_id);
        $categoria = RopaCategorias::where("category", $request->categoria)->first();
        $prenda = Prendas::where("prenda", $request->prenda)->first();
        $estado = EstadoRopa::where("estado", $request->estado)->first();
        $tipo = RopaTipo::where("tipo", $request->tipo)->first();

        $publicacion = Publicacion::create([
            'id_user' => $user_id, 
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

        Carbon::setLocale('es');

        $publicacionFormateada = [
            'id' => $publicacion->id,
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
        ];
        
        return response()->json([
            "mensaje" => "Publicación obtenida con éxito",
            "publicacion" => $publicacionFormateada,
            "userPublicacion" => $userPublicacion,
            "itsMe" => $itsMe,
        ], 200);
    }

    public function getPublicacionesUser($user_id, $page) {
        $limit = 5;
        $user = User::find($user_id);
    
        if (!$user) {
            return response()->json([
                "mensaje" => "Usuario no encontrado"
            ], 404); 
        }
    
        $offset = ($page - 1) * $limit;
    
        $publicaciones = Publicacion::where("id_user", $user_id)
            ->skip($offset)
            ->take($limit)
            ->get();
    
        Carbon::setLocale('es');
        
        $publicacionesFormateadas = $publicaciones->map(function ($publicacion) {
            $estado_ropa = EstadoRopa::find($publicacion->estado_ropa);
            $prenda = Prendas::find($publicacion->prenda);
            $categoria = RopaCategorias::find($publicacion->categoria);
            $tipo = RopaTipo::find($publicacion->tipo);
            return [
                'id' => $publicacion->id,
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
            ];
        });

        $publicacionesTotales = Publicacion::where("id_user", $user_id)->count();
        $hasMore = ($publicacionesTotales > $offset + $limit);
            
        return response()->json([
            'message' => 'Publicaciones obtenidas!',
            'publicaciones' => $publicacionesFormateadas,
            'publicacionesTotales' => $publicacionesTotales,
            'page' => $page,
            'hasMore' => $hasMore,
        ], 200);
    }
    
}
