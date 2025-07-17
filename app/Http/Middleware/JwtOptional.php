<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Tymon\JWTAuth\Facades\JWTAuth;

class JwtOptional
{
    public function handle($request, Closure $next)
    {
        try {
            if ($token = JWTAuth::getToken()) {
                JWTAuth::parseToken()->authenticate();
            }
        } catch (Exception $e) {
            // No hacemos nada si no hay token o es inválido
        }

        return $next($request);
    }
}