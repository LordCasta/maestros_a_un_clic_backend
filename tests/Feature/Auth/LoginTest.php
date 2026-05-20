<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('logs in and returns a sanctum token with user payload', function () {
    $user = User::factory()->create([
        'name' => 'Cliente Prueba',
        'email' => 'cliente-login@example.com',
        'password' => Hash::make('password123'),
        'role' => 'client',
        'commune' => 'Santiago',
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'cliente-login@example.com',
        'password' => 'password123',
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Autenticado',
        ])
        ->assertJsonPath('data.user.role', 'client')
        ->assertJsonPath('data.role', 'client')
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'token',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                    'avatar',
                    'role',
                    'commune',
                    'is_verified',
                    'created_at',
                ],
                'role',
            ],
        ]);

    $token = $response->json('data.token');

    expect($token)->not->toBeEmpty();

    $this->assertDatabaseCount('personal_access_tokens', 1);
    $this->assertDatabaseHas('personal_access_tokens', [
        'tokenable_id' => $user->id,
        'tokenable_type' => User::class,
        'name' => 'api-token',
    ]);

    $meResponse = $this->withToken($token)->getJson('/api/auth/me');

    $meResponse->assertOk()
        ->assertJsonPath('data.email', 'cliente-login@example.com')
        ->assertJsonPath('data.role', 'client');
});

it('rejects invalid credentials on login', function () {
    User::factory()->create([
        'email' => 'cliente-login@example.com',
        'password' => Hash::make('password123'),
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'cliente-login@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertUnauthorized()
        ->assertJson([
            'success' => false,
            'message' => 'Credenciales incorrectas',
        ]);
});


