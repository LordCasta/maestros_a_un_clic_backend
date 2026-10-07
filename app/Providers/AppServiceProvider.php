<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Dedoc\Scramble\Support\Generator\Types\BooleanType;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
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
