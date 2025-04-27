<?php
namespace App\Http\Controllers;
use Validator;

use App\Models\User;
use App\Models\UserPlan;
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
        if(!$user) {
            return response()->json(["mensaje"=>"Error al obtener el token"], 400);
        }
        return response()->json($user, 200);
        
    }
}
