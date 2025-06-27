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

use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

use Symfony\Component\HttpFoundation\Cookie;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            $existe = User::withTrashed()->where('correo', $request->correo)->first();
            if (!$existe) {
                $user = User::create([
                    'correo'   => $request->correo,
                    'password' => bcrypt($request->password),
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
                    'ruta_destino' => "/perfil/{$user->correo}",
                ]);
            } else {
                return response()->json(['message' => 'El correo ya está en uso'], 404);
            }
        } catch (QueryException $e) {
            return response()->json(['message' => $e], 422);
        }

        $token = auth()->attempt($request->only('correo', 'password'));

        return $this->respondWithToken($token, 201);  
    }

    public function login(Request $request)
    {
        $credentials = [
            'correo' => $request->input('correo'),
            'password' => $request->input('password')
        ];

        $customClaims = ['correo' => $credentials['correo']];

        $existingUser = User::withTrashed()->where('correo', $credentials['correo'])->first();

        if ($existingUser && $existingUser->trashed()) {
            return response()->json(['error' => 'Usuario borrado!', "cred"=>$credentials], 404);
        }

        $token = JWTAuth::attempt($credentials);

        if (!$token) {
            return response()->json(['error' => 'Unauthorized', "cred"=>$credentials], 401);
        }

        return $this->respondWithToken($token, 201);
    }

    protected function respondWithToken(string $token, int $status = 200)
    {
        $minutes  = auth()->factory()->getTTL();           

        $secure   = filter_var(env('COOKIE_SECURE', false), FILTER_VALIDATE_BOOLEAN);
        $sameSite = env('COOKIE_SAMESITE', 'Lax');          
    
        if ($sameSite === 'None' && !$secure) {
            $sameSite = 'Lax';                              
        };  

        $domain = env('COOKIE_DOMAIN', null);

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => $minutes * 60,
        ], $status)->withCookie(
            cookie(
                'access_token',
                $token,
                $minutes,
                '/',   
                $domain,        
                $secure,
                true,      
                false,
                $sameSite
            )
        );
    }

    public function redirect($provider)
    {
        return Socialite::driver($provider)->stateless()->redirect();
    }

    public function callback($provider)
    {
        $socialUser = Socialite::driver($provider)->stateless()->user();

        $existingUser = User::withTrashed()->where('correo', $socialUser->getEmail())->first();

        if ($existingUser && $existingUser->trashed()) {
            return redirect()->away(env('FRONTEND_URL', 'http://localhost:3000') . '/?deleted_user=deleted');
        }

        $user = User::firstOrCreate(
            ['correo' => $socialUser->getEmail()],
            [
                'password'          => bcrypt(Str::random(40)),
                'nombre'            => $socialUser->getName(),
                'email_verified_at' => now(),
            ]
        );

        $token   = auth()->login($user);                
        $minutes = auth()->factory()->getTTL();      

        $secure   = filter_var(env('COOKIE_SECURE', false), FILTER_VALIDATE_BOOLEAN);
        $sameSite = env('COOKIE_SAMESITE', 'Lax');
        if ($sameSite === 'None' && !$secure) {
            $sameSite = 'Lax';
        }
        
        $domain = env('COOKIE_DOMAIN', null);

        $cookie = cookie(
            'access_token',
            $token,
            $minutes,      
            '/',       
            $domain,
            $secure,
            true,          
            false,    
            $sameSite
        );

        return redirect()
            ->away(env('FRONTEND_URL', 'http://localhost:3000') . '/?googleSession=1')
            ->withCookie($cookie);
    }

    // AuthController.php
    private function buildParaResena($user)
    {
        $venta = PublicacionVenta::where('id_comprador', $user->id)
            ->where('estado_venta', 2)
            ->orderBy('created_at', 'asc')
            ->first();

        if (!$venta) {
            return null;
        }

        Carbon::setLocale('es');

        $oferta  = PublicacionOferta::find($venta->oferta_id);
        $mensaje = ChatMensaje::find($oferta?->mensaje_id);
        $pub     = Publicacion::find($venta->id_publicacion);
        $vendedor= User::find($pub?->id_user);

        if (!$pub) {
            return null;
        }

        return [
            'id'             => $pub->id,
            'nombre'         => $pub->nombre,
            'descripcion'    => $pub->descripcion,
            'precio'         => $pub->precio,
            'imagen'         => $pub->imagen,
            'fecha_venta'    => $venta->updated_at->diffForHumans(),
            'id_venta'       => $venta->id,
            'vendedor'       => $vendedor,
            'conversation_id'=> $mensaje?->conversation_id,
        ];
    }

    public function extract_jwt(Request $request)
    {
        $token = $request->cookie('access_token');
        if (!$token) {
            return response()->json(['error' => 'Cookie missing'], 401);
        }
    
        try {
            $payload = JWTAuth::setToken($token)->getPayload();
            $user    = JWTAuth::authenticate($token);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Token invalid'], 401);
        }
    
        $expiresIn  = $payload['exp'] - time();
        $paraReseña = $this->buildParaResena($user);   // ← nuevo
    
        return response()->json([
            'access_token' => $token,
            'expires_in'   => $expiresIn,
            'user'         => $user,
            'paraReseña'   => $paraReseña,             // ← incluido
        ]);
    }

    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'logout ok'], 200)
        ->withCookie(                       
            cookie()->forget(
                'access_token',
                '/',
                env('COOKIE_DOMAIN', null)  
            )
        );
    }

}
