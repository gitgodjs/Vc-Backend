<?php
namespace App\Http\Controllers;

use Validator;
use Carbon\Carbon;
use App\Models\User;
use App\Models\UserPlan;
use App\Models\Publicacion;
use App\Models\PublicacionVenta;
use App\Models\PublicacionOferta;
use App\Models\ChatMensaje;

use App\Models\NotificacionTipo;
use App\Models\UserNotificacion;

use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = request()->all();

        $user = User::create([
            'correo' => $data['correo'],
            'password' => bcrypt($data['password']),
            'created_at' => now(),
            'updated_at' => now(),
        ]); 

        UserPlan::create([
            'user_id' => $user->id, 
            'plan_id' => 1,
            'publicaciones_disponibles' => 5,
            'impulsos_disponibles' => 0,
            'fecha_compra' => now(),
            'fecha_vencimiento' => now()->addMonths(12),
        ]);

        UserNotificacion::create([
            'user_id' => $user->id,
            'notificacion_tipo_id' => 1,
            'mensaje' => 'Te damos la bienvenida a <span style="color:#864a00;">Vintage Clothes</span>. Personaliza tu perfil y empieza a explorar.',
            'fecha_creacion' => now(),
            'ruta_destino' => `/perfil/${$user->correo}`,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $credentials = $request->only('correo', 'password');
        $token = auth()->attempt($credentials);

        return $this->respondWithToken($token);    
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'code' => '200',
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('correo', 'password');
        $token = auth()->attempt($credentials);
    
        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized', "cred"=>$credentials], 401);
        }
    
        return $this->respondWithToken($token);    
    }

    public function get_credentials_from_token()
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(["mensaje" => "Error al obtener el token"], 400);
        }

        $ventaParaReseñar = PublicacionVenta::where('id_comprador', $user->id)
            ->where('estado_venta', 2)
            ->orderBy('created_at', 'asc')
            ->first();

        
        $paraReseña = null;

        Carbon::setLocale('es');
        if ($ventaParaReseñar) {
            $oferta = PublicacionOferta::find($ventaParaReseñar->oferta_id);
            $conversation_id = ChatMensaje::find($oferta->mensaje_id)->conversation_id;

            $publicacion = Publicacion::find($ventaParaReseñar->id_publicacion);
            $vendedor = User::find($publicacion->id_user);

            if ($publicacion) {
                $paraReseña = [
                    'id' => $publicacion->id,
                    'nombre' => $publicacion->nombre,
                    'descripcion' => $publicacion->descripcion,
                    'precio' => $publicacion->precio,
                    'imagen' => $publicacion->imagen,
                    'fecha_venta' => $ventaParaReseñar->updated_at->diffForHumans(),
                    'id_venta' => $ventaParaReseñar->id,
                    'vendedor' => $vendedor,
                    'conversation_id' => $conversation_id
                ];
            }
        }

        return response()->json([
            "user" => $user,
            "paraReseña" => $paraReseña
        ], 200);
    }
}
