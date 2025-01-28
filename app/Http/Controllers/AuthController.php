<?php
namespace App\Http\Controllers;
use Validator;

use App\Models\User;
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
