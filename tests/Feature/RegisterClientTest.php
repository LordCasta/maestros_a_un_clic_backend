<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('registers a client with multipart form data', function () {
    Storage::fake('public');

    $response = $this->withHeaders(['Accept' => 'application/json'])->post('/api/auth/register/client', [
        'name' => 'Juan Cliente',
        'email' => 'juancliente@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'commune' => 'Santiago',
        'latitude' => -33.4489,
        'longitude' => -70.6693,
        'phone' => '+56911112222',
        'selfie' => UploadedFile::fake()->image('selfie.jpg'),
        'document' => UploadedFile::fake()->create('document.pdf', 500, 'application/pdf'),
    ]);

    $response->assertCreated()
        ->assertJson([
            'success' => true,
            'message' => 'Usuario registrado',
        ])
        ->assertJsonPath('data.user.role', 'client')
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
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
                'token',
            ],
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'juancliente@example.com',
        'role' => 'client',
        'commune' => 'Santiago',
    ]);

    $user = User::where('email', 'juancliente@example.com')->firstOrFail();

    expect(Hash::check('password123', $user->password))->toBeTrue();

    $this->assertDatabaseHas('client_profiles', [
        'user_id' => $user->id,
    ]);

    Storage::disk('public')->assertExists($user->clientProfile->selfie_path);
    Storage::disk('public')->assertExists($user->clientProfile->document_path);
});

it('validates required fields for client registration', function () {
    $response = $this->withHeaders(['Accept' => 'application/json'])->post('/api/auth/register/client', [
        'email' => 'invalid-email',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors([
            'name',
            'email',
            'password',
            'selfie',
            'document',
        ]);
});
