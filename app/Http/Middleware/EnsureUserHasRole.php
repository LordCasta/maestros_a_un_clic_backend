<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe una ruta a uno o varios roles. Uso: ->middleware('role:client') o 'role:professional,admin'.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $role = $request->user()?->role?->value;

        abort_unless(in_array($role, $roles, true), 403, 'Esta acción no está disponible para tu tipo de cuenta.');

        return $next($request);
    }
}
