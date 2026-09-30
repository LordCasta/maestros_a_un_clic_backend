<?php

use App\Models\User;

it('logs in and returns a token with the user payload', function () {
    $user = User::factory()->client()->create(['email' => 'cliente@example.com']);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'cliente@example.com',
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJson(['success' => true, 'message' => 'Sesión iniciada.'])
        ->assertJsonPath('data.user.id', $user->id)
        ->assertJsonPath('data.user.role', 'client')
        ->assertJsonStructure(['data' => [
            'token',
            'user' => ['id', 'name', 'email', 'phone', 'avatar_url', 'role', 'commune', 'verification_status', 'rating', 'created_at'],
        ]]);

    $this->withToken($response->json('data.token'))
        ->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.email', 'cliente@example.com');
});

it('rejects invalid credentials', function () {
    User::factory()->create(['email' => 'cliente@example.com']);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'cliente@example.com',
        'password' => 'wrong-password',
    ])
        ->assertUnauthorized()
        ->assertExactJson(['success' => false, 'message' => 'Credenciales incorrectas.']);
});

it('does not let a blocked user log in', function () {
    User::factory()->blocked()->create(['email' => 'bloqueado@example.com']);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'bloqueado@example.com',
        'password' => 'password',
    ])
        ->assertForbidden()
        ->assertJsonPath('success', false);

    $this->assertDatabaseCount('personal_access_tokens', 0);
});
