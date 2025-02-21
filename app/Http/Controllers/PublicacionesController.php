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
}
