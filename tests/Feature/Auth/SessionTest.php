<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('returns the authenticated user through me', function () {
    Sanctum::actingAs(User::factory()->create(['email' => 'cliente@example.com']));

    $this->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.email', 'cliente@example.com');
});

it('logs out and revokes the current token', function () {
    $token = User::factory()->create()->createToken('api-token')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/auth/logout')
        ->assertOk()
        ->assertJson(['success' => true, 'message' => 'Sesión cerrada.', 'data' => null]);

    $this->assertDatabaseCount('personal_access_tokens', 0);
});

it('blocks a user that was blocked after logging in', function () {
    Sanctum::actingAs(User::factory()->blocked()->create());

    $this->getJson('/api/v1/auth/me')->assertForbidden();
});
