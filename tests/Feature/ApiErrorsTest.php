<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

/*
| Todas las respuestas de error de la API usan { success: false, message, errors? }.
| Ver docs/api/convenciones.md.
*/

it('returns 401 in the standard format without a token', function () {
    $this->getJson('/api/v1/auth/me')
        ->assertUnauthorized()
        ->assertExactJson(['success' => false, 'message' => 'No autenticado.']);
});

it('returns 401 in JSON even when the client does not ask for JSON', function () {
    $this->get('/api/v1/auth/me')
        ->assertUnauthorized()
        ->assertJsonPath('success', false);
});

it('returns 404 without leaking model names', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/bookings/999')
        ->assertNotFound()
        ->assertExactJson(['success' => false, 'message' => 'Recurso no encontrado.']);
});

it('returns 404 for unknown routes, including the old unversioned API', function () {
    $this->postJson('/api/auth/login')->assertNotFound()->assertJsonPath('success', false);
    $this->getJson('/api/v1/does-not-exist')->assertNotFound()->assertJsonPath('success', false);
});

it('returns 422 with field errors', function () {
    $this->postJson('/api/v1/auth/login', [])
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonStructure(['message', 'errors' => ['email', 'password']]);
});

it('returns validation messages in Spanish with readable field names', function () {
    $this->postJson('/api/v1/auth/register/client', [])
        ->assertJsonPath('errors.email.0', 'El campo correo electrónico es obligatorio.')
        ->assertJsonPath('errors.password.0', 'El campo contraseña es obligatorio.');
});

it('returns 403 with a readable message when the role is not allowed', function () {
    Sanctum::actingAs(User::factory()->professional()->create());

    $this->getJson('/api/v1/favorites')
        ->assertForbidden()
        ->assertExactJson([
            'success' => false,
            'message' => 'Esta acción no está disponible para tu tipo de cuenta.',
        ]);
});
