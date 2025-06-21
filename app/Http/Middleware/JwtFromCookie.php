<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class JwtFromCookie
{
    public function handle(Request $request, Closure $next)
    {
        // 1) Si ya hay Authorization: Beer token, no tocamos nada
        if ($request->bearerToken()) {
            return $next($request);
        }

        // 2) Si existe la cookie "access_token", la subimos a Authorization
        if ($token = $request->cookie('access_token')) {
            $request->headers->set('Authorization', 'Bearer '.$token);
        }

        return $next($request);
    }
}
