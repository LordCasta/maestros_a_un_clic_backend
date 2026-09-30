<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Un usuario bloqueado por un administrador no puede usar rutas autenticadas (HU038).
 */
class EnsureUserIsNotBlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_if($request->user()?->isBlocked(), 403, 'Tu cuenta está bloqueada. Contacta a soporte.');

        return $next($request);
    }
}
