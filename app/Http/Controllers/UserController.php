<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;


class UserController extends Controller
{

    public function obtenerUserCorreo($correo) {
        $user = User::where("correo", $correo)->first();
        if(!$user){
            return response()->json([
                "mensaje" => "Usuario inexistente",
            ]);
        };

        return response()->json([
            "mensaje" => "Usuario existente",
            "user"=>$user,
        ]);
    }
}
