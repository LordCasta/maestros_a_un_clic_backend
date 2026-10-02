<?php

use App\Enums\Role;
use App\Enums\VerificationStatus;
use App\Models\Commune;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

it('registers a client and returns a session', function () {
    Storage::fake('public');
    $commune = Commune::create(['name' => 'El Poblado', 'code' => '14', 'type' => 'comuna', 'latitude' => 6.2, 'longitude' => -75.5]);

    $response = $this->postJson('/api/v1/auth/register/client', [
        'name' => 'Camila Restrepo',
        'email' => 'camila@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'phone' => '3001112233',
        'commune_id' => $commune->id,
        'address' => 'Calle 10 # 43-20',
        'avatar' => UploadedFile::fake()->image('avatar.jpg'),
    ]);

    $response->assertCreated()
        ->assertJson(['success' => true, 'message' => 'Cuenta creada.'])
        ->assertJsonPath('data.user.role', 'client')
        ->assertJsonPath('data.user.verification_status', 'unverified')
        ->assertJsonPath('data.user.commune.name', 'El Poblado')
        ->assertJsonStructure(['data' => ['user', 'token']]);

    $user = User::where('email', 'camila@example.com')->firstOrFail();

    expect($user->role)->toBe(Role::Client)
        ->and($user->verification_status)->toBe(VerificationStatus::Unverified)
        ->and(Hash::check('password123', $user->password))->toBeTrue()
        ->and($user->clientProfile)->not->toBeNull();

    Storage::disk('public')->assertExists($user->avatar_path);
});

it('validates required fields with the standard error format', function () {
    $this->postJson('/api/v1/auth/register/client', ['email' => 'invalid-email'])
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Los datos enviados no son válidos.')
        ->assertJsonValidationErrors(['name', 'email', 'password']);
});

it('rejects a duplicated email', function () {
    User::factory()->create(['email' => 'camila@example.com']);

    $this->postJson('/api/v1/auth/register/client', [
        'name' => 'Otra Camila',
        'email' => 'camila@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertJsonValidationErrors(['email']);
});
