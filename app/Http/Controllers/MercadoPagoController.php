<?php

namespace App\Http\Controllers;

use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

use Illuminate\Http\Request;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\Client\Preference\PreferenceClient;

use App\Models\User;
use App\Models\Plan;

class MercadoPagoController extends Controller
{
    public function createPreference(Request $request)
    {   
        $user = auth()->user();
        if(!$user) {
            return response()->json([
                "mensaje" => "Este usuario no existe",
                "code" => 404,
            ], 404);
        };

        try {
            MercadoPagoConfig::setAccessToken(env('MP_ACCESS_TOKEN'));

            $client = new PreferenceClient();

            $plan = Plan::find($request->plan);
            
            $preference = $client->create([
                "items" => [
                    [
                        "id" => $plan->id,
                        "title" => "Compra del plan: " . $plan->titulo,
                        "description" => $plan->descripcion,
                        "quantity" => 1,
                        "unit_price" => (float) $plan->precio,
                    ]
                ],
                "metadata" => [
                    "user_id" => $user->id,
                    "user_email" => $user->correo,
                    "plan_id" => $plan->id,
                    "plan_meses" => $plan->meses_plan,
                ],
                "back_urls" => [
                    "success" => "http://localhost:3000/mercadopago/success",
                    "failure" => "http://localhost:3000/mercadopago/failure",
                    "pending" => "http://localhost:3000/mercadopago/pending",
                ],
                "auto_return" => "approved"
            ]);

            // Bacjs:urls para https con ngrok (investigar)
        
            return response()->json(['init_point' => $preference->init_point]);
        
        } catch (MPApiException $e) {
            $response = $e->getApiResponse(); // este es un MPHttpResponse
        
            logger()->error('Error al crear preferencia Mercado Pago', [
                'message' => $e->getMessage(),
                'status' => $response->getStatusCode(),
                'body' => $response->getContent()
            ]);
        
            return response()->json([
                'error' => 'Error al crear preferencia',
                'details' => $response->getContent()
            ], 500);
        }
        
    }
}
