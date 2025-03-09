<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\UsersTalla;
use App\Models\UserCodigo;
use Illuminate\Http\Request;
use App\Mail\EmailCodeConfirmation;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;


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
        $user = User::where("correo", $correo)->first();
        
        if(!$user){
            return response()->json([
                "mensaje" => "Usuario inexistente",
                "code" => 400,
            ], 400);
        };

        return response()->json([
            "mensaje" => "Usuario existente",
            "user" => $user,
        ], 200);
    }

    public function completarPerfil(Request $request, $correo) {
        $user = User::where("correo", $correo)->first();
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

    public function actualizarTallasUser(Request $request, $correo) 
    {
        $user = User::where('correo', $correo)->first();

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        };

        $userTallas = UsersTalla::updateOrCreate(
            ['user_id' => $user->id], 
            [
                'remeras' => $request->talleRemera,
                'pantalones' => $request->tallePantalon,
                'shorts' => $request->talleShort,
                'trajes' => $request->talleTraje,
                'abrigos' => $request->talleAbrigo,
                'vestidos' => $request->talleVestido,
                'calzados' => $request->talleCalzado,
            ]
        );

        return response()->json(['message' => 'Tallas guardadas correctamente', "req" => $request], 200);
    }

    public function obtenerTallasUser($correo) {
        $user = User::where("correo", $correo)->first();
        
        if (!$user) {
            return response()->json([
                'message' => 'Usuario no encontrado',
                'code' => 404
            ], 404);
        };

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
            'code' => 200
        ], 200);
    }
}
