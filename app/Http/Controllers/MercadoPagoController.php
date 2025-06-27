<?php

namespace App\Http\Controllers;

use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

use Illuminate\Http\Request;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Client\Payment\PaymentClient;

use App\Models\User;
use App\Models\Plan;
use App\Models\UserPlan;
use App\Models\MercadoPagoComprobante;

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
                    "plan_id" => $plan->id,
                    "monto" => (float) $plan->precio,
                ],
                "back_urls" => [
                    "success" => "https://vintageclothesarg.com/mercadopago/success",
                    "failure" => "https://vintageclothesarg.com/mercadopago/failure",
                    "pending" => "https://vintageclothesarg.com/mercadopago/pending",
                ],
                "auto_return" => "approved"
            ]);
        
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

    public function confirmTransaction(Request $request)
    {
        $user = auth()->user();
        if(!$user) {
            return response()->json([
                "mensaje" => "Este usuario no existe",
                "code" => 404,
            ], 404);
        };
        try {
            // Configurar el token de acceso
            MercadoPagoConfig::setAccessToken(env('MP_ACCESS_TOKEN'));
    
            // Crear una instancia del cliente de pagos
            $client = new PaymentClient();
    
            $payment = $client->get($request->input('payment_id'));

            $userId = $user->id;
            $planId = $payment->metadata->plan_id ?? null;
            $monto = $payment->metadata->monto ?? null;

            if (!$userId || !$planId) {
                return response()->json(['error' => 'Faltan datos en metadata'], 422);
            }

            // Acceder a los metadatos
            $planId = $payment->metadata->plan_id;
            $monto = $payment->metadata->pago;

            logger()->info('Confirmación MP', ['payment' => $payment]);

            $metadata = $payment->metadata ?? null;
            if (!$metadata || !isset($metadata->user_id, $metadata->plan_id, $metadata->pago)) {
                return response()->json(['error' => 'Metadata incompleta'], 422);
            }
    
            // Guardar el comprobante en la base de datos
            MercadoPagoComprobante::create([
                'user_id' => $userId,
                'plan_id' => $planId,
                'monto' => $monto,
                'collection_id' => $request->input('collection_id'),
                'collection_status' => $request->input('collection_status'),
                'payment_id' => $request->input('payment_id'),
                'status' => $request->input('status'),
                'external_reference' => $request->input('external_reference'),
                'payment_type' => $request->input('payment_type'),
                'merchant_order_id' => $request->input('merchant_order_id'),
                'preference_id' => $request->input('preference_id'),
                'site_id' => $request->input('site_id'),
                'processing_mode' => $request->input('processing_mode'),
                'merchant_account_id' => $request->input('merchant_account_id'),
                'created_at' => now(),
            ]);
    
            $plan = Plan::findOrFail($planId);

            $fechaCompra = now();
            $fechaVencimiento = now()->addMonth(); 

            $userPlan = UserPlan::where('user_id', $userId)->first();

            if ($userPlan) {
                $userPlan->update([
                    'plan_id' => $planId,
                    'publicaciones_disponibles' => $plan->publicaciones_mes,
                    'impulsos_disponibles' => $plan->impulsos_mes,
                    'fecha_compra' => $fechaCompra,
                    'fecha_vencimiento' => $fechaVencimiento,
                    'updated_at' => now(),
                ]);
            } else {
                UserPlan::create([
                    'user_id' => $userId,
                    'plan_id' => $planId,
                    'publicaciones_disponibles' => $plan->publicaciones_mes,
                    'impulsos_disponibles' => $plan->impulsos_mes,
                    'fecha_compra' => $fechaCompra,
                    'fecha_vencimiento' => $fechaVencimiento,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            };

            return response()->json(['message' => 'Comprobante registrado correctamente', "plan" => $plan], 200);
        } catch (\Exception $e) {
            logger()->error('MP confirmTransaction error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Error al guardar el comprobante'], 500);
        }
    }
}
