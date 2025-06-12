<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\EmailRecibisteOferta;
use App\Mail\EmailAceptasteOferta;
use App\Mail\EmailOfertaAceptada;
use App\Mail\EmailOfertaRechazada;
use App\Models\User;

class NotificacionesController extends Controller
{
    public function recibisteOferta(Request $request) {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json([
                "mensaje" => "Usuario no encontrado!"
            ], 404);
        };

        $ofertado = User::find($request->ofertado_id);

        // Necesita: $request->monto, $request->prenda
        Mail::to($ofertado->correo)->send(new EmailRecibisteOferta(
            $ofertado->correo,
            $ofertado->nombre,
            $request->monto,
            $request->prenda
        ));

        return response()->json([
            "mensaje" => "Correo enviado correctamente"
        ], 200);
    }
    
    public function AceptasteOferta(Request $request) {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json([
                "mensaje" => "Usuario no encontrado!"
            ], 404);
        };

        // Necesita: $request->tituloPublicacion
        Mail::to($user->correo)->send(new EmailAceptasteOferta(
            $user->correo,
            $user->nombre,
            $request->tituloPublicacion
        ));

        return response()->json([
            "mensaje" => "Correo enviado correctamente"
        ], 200);
    }

    public function OfertaAceptada(Request $request) {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json([
                "mensaje" => "Usuario no encontrado!"
            ], 404);
        };

        $aceptado = User::find($request->aceptado_id);

        // Necesita: $request->correo_aceptado, $request->tituloPublicacion
        Mail::to($aceptado->correo)->send(new EmailOfertaAceptada(
            $aceptado->correo,            // quien aceptó
            $aceptado->nombre,
            $request->tituloPublicacion
        ));

        return response()->json([
            "mensaje" => "Correo enviado correctamente"
        ], 200);
    }

    public function OfertaRechazada(Request $request) {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json([
                "mensaje" => "Usuario no encontrado!"
            ], 404);
        };

        $rechazado = User::find($request->rechazado_id);

        // Necesita: $request->tituloPublicacion
        Mail::to($rechazado->correo)->send(new EmailOfertaRechazada(
            $rechazado->correo,
            $rechazado->nombre,
            $request->tituloPublicacion
        ));

        return response()->json([
            "mensaje" => "Correo enviado correctamente"
        ], 200);
    }
}
