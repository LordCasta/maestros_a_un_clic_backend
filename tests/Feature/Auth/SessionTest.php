<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('returns the authenticated user through me endpoint', function () {
    $user = User::factory()->create([
        'email' => 'cliente-me@example.com',
        'password' => Hash::make('password123'),
        'role' => 'client',
        'commune' => 'Santiago',
    ]);

    $token = $user->createToken('api-token')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/auth/me');

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ])
        ->assertJsonPath('data.email', 'cliente-me@example.com')
        ->assertJsonPath('data.role', 'client');
});

it('logs out and revokes the current sanctum token', function () {
    $user = User::factory()->create([
        'email' => 'cliente-logout@example.com',
        'password' => Hash::make('password123'),
        'role' => 'client',
    ]);

    $token = $user->createToken('api-token')->plainTextToken;

    $logoutResponse = $this->withToken($token)->postJson('/api/auth/logout');

    $logoutResponse->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Sesión cerrada',
        ]);

    $this->assertDatabaseCount('personal_access_tokens', 0);
});

