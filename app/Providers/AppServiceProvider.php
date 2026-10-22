<?php

namespace App\Providers;

use App\Models\User;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Dedoc\Scramble\Support\Generator\Types\BooleanType;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureApiDocs();
        $this->registerSqliteGeoFunctions();
        $this->configurePasswordResetUrl();
    }

    /**
     * Configura la URL del frontend para el enlace de restablecimiento de contraseña (HU020).
     */
    private function configurePasswordResetUrl(): void
    {
        ResetPassword::createUrlUsing(function (User $user, string $token) {
            $frontendUrl = rtrim(config('app.frontend_url'), '/');

            return "{$frontendUrl}/reset-password?token={$token}&email=".urlencode($user->email);
        });
    }

    /**
     * Registra funciones matemáticas en SQLite para pruebas de distancia y geolocalización (HU002).
     */
    private function registerSqliteGeoFunctions(): void
    {
        Event::listen(ConnectionEstablished::class, function (ConnectionEstablished $event) {
            if ($event->connection->getDriverName() === 'sqlite') {
                $pdo = $event->connection->getPdo();
                $pdo->sqliteCreateFunction('least', fn (...$args) => min($args));
                $pdo->sqliteCreateFunction('greatest', fn (...$args) => max($args));
                $pdo->sqliteCreateFunction('radians', fn ($deg) => deg2rad((float) $deg));
                $pdo->sqliteCreateFunction('degrees', fn ($rad) => rad2deg((float) $rad));
                $pdo->sqliteCreateFunction('acos', fn ($val) => acos(min(1.0, max(-1.0, (float) $val))));
                $pdo->sqliteCreateFunction('cos', fn ($val) => cos((float) $val));
                $pdo->sqliteCreateFunction('sin', fn ($val) => sin((float) $val));
            }
        });
    }

    /**
     * Ajusta la documentación que genera Scramble (/docs/api) al contrato real de la API.
     */
    private function configureApiDocs(): void
    {
        Scramble::configure()->withDocumentTransformers(function (OpenApi $openApi) {
            // Botón de autenticación con el token de POST /auth/login.
            $openApi->secure(SecurityScheme::http('bearer'));

            // Los errores llevan `success: false` (bootstrap/app.php → ApiResponse::error).
            foreach ($openApi->components->responses as $response) {
                $schema = $response->content['application/json'] ?? null;

                if ($schema?->type instanceof ObjectType) {
                    $schema->type->properties = [
                        'success' => (new BooleanType)->const(false),
                        ...$schema->type->properties,
                    ];
                    $schema->type->setRequired(['success', ...$schema->type->required]);
                }
            }
        });
    }
}
