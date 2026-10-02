<?php

use App\Models\ProfessionalService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Configuración
|--------------------------------------------------------------------------
| Todos los tests de Feature usan una BD SQLite en memoria (phpunit.xml) que
| se reinicia en cada test. Nunca tocan la base de datos de desarrollo.
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Helpers compartidos
|--------------------------------------------------------------------------
*/

/**
 * Profesional verificado (visible en búsquedas) con un servicio activo.
 *
 * @param  array<string, mixed>  $serviceAttributes
 * @return array{0: User, 1: ProfessionalService}
 */
function professionalWithService(array $serviceAttributes = []): array
{
    $professional = User::factory()->professional()->verified()->create();
    $service = ProfessionalService::factory()
        ->for($professional->professionalProfile)
        ->create($serviceAttributes);

    return [$professional, $service];
}
