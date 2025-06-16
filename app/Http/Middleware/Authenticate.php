<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        // Para requests API que esperan JSON, deja que Laravel lance la excepción normal
        if ($request->expectsJson()) {
            return null;
        }

        // Para cualquier otra request, respondé 401 directamente (sin redirigir)
        abort(401, 'Unauthenticated');
    }
}
