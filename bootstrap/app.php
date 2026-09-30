<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserIsNotBlocked;
use App\Support\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api/v1',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'not_blocked' => EnsureUserIsNotBlocked::class,
        ]);

        // No hay login web: sin esto, un 401 sin `Accept: application/json` intenta redirigir a route('login').
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*') || $request->expectsJson());

        // Toda respuesta de error de la API usa el formato de App\Support\ApiResponse.
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            if ($e instanceof ValidationException) {
                return ApiResponse::error('Los datos enviados no son válidos.', 422, $e->errors());
            }

            if ($e instanceof AuthenticationException) {
                return ApiResponse::error('No autenticado.', 401);
            }

            if ($e instanceof HttpExceptionInterface) {
                $status = $e->getStatusCode();
                $message = match ($status) {
                    // El mensaje original de 404 puede filtrar nombres de modelos internos.
                    404 => 'Recurso no encontrado.',
                    403 => in_array($e->getMessage(), ['', 'This action is unauthorized.'], true)
                        ? 'No tienes permiso para realizar esta acción.'
                        : $e->getMessage(),
                    405 => 'Método no permitido.',
                    429 => 'Demasiadas solicitudes. Intenta de nuevo en un momento.',
                    default => $e->getMessage() ?: 'Error en la solicitud.',
                };

                return ApiResponse::error($message, $status);
            }

            if (config('app.debug')) {
                return null;
            }

            return ApiResponse::error('Error interno del servidor.', 500);
        });
    })->create();
