<?php

use App\Enums\BookingStatus;
use App\Enums\CommuneType;
use App\Models\Booking;
use App\Models\Commune;
use App\Models\Favorite;
use App\Models\User;
use App\Models\VerificationDocument;
use App\Models\VerificationRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

it('changes password successfully when current password is valid (HU019)', function () {
    $user = User::factory()->client()->create([
        'password' => 'AntiguaClave123!',
    ]);

    Sanctum::actingAs($user);

    $response = $this->putJson('/api/v1/me/password', [
        'current_password' => 'AntiguaClave123!',
        'password' => 'NuevaClaveSegura456!',
        'password_confirmation' => 'NuevaClaveSegura456!',
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Contraseña actualizada correctamente.',
            'data' => null,
        ]);

    $user->refresh();
    expect(Hash::check('NuevaClaveSegura456!', $user->password))->toBeTrue()
        ->and(Hash::check('AntiguaClave123!', $user->password))->toBeFalse();
});

it('rejects password change when current password is wrong (HU019)', function () {
    $user = User::factory()->client()->create([
        'password' => 'ContrasenaReal123!',
    ]);

    Sanctum::actingAs($user);

    $response = $this->putJson('/api/v1/me/password', [
        'current_password' => 'ContrasenaEquivocada!',
        'password' => 'NuevaClaveSegura456!',
        'password_confirmation' => 'NuevaClaveSegura456!',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['current_password']);

    $user->refresh();
    expect(Hash::check('ContrasenaReal123!', $user->password))->toBeTrue();
});

it('rejects password change when confirmation does not match (HU019)', function () {
    $user = User::factory()->client()->create([
        'password' => 'AntiguaClave123!',
    ]);

    Sanctum::actingAs($user);

    $response = $this->putJson('/api/v1/me/password', [
        'current_password' => 'AntiguaClave123!',
        'password' => 'NuevaClaveSegura456!',
        'password_confirmation' => 'OtraClaveDiferente!',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

it('rejects password change when new password is too short (HU019)', function () {
    $user = User::factory()->client()->create([
        'password' => 'AntiguaClave123!',
    ]);

    Sanctum::actingAs($user);

    $response = $this->putJson('/api/v1/me/password', [
        'current_password' => 'AntiguaClave123!',
        'password' => 'corta',
        'password_confirmation' => 'corta',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

it('requires authentication to change password (HU019)', function () {
    $response = $this->putJson('/api/v1/me/password', [
        'current_password' => 'AntiguaClave123!',
        'password' => 'NuevaClaveSegura456!',
        'password_confirmation' => 'NuevaClaveSegura456!',
    ]);

    $response->assertUnauthorized();
});

it('allows a professional to change their password as well (HU019)', function () {
    $user = User::factory()->professional()->create([
        'password' => 'ProPassword123!',
    ]);

    Sanctum::actingAs($user);

    $response = $this->putJson('/api/v1/me/password', [
        'current_password' => 'ProPassword123!',
        'password' => 'NuevoProPass789!',
        'password_confirmation' => 'NuevoProPass789!',
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Contraseña actualizada correctamente.',
        ]);

    $user->refresh();
    expect(Hash::check('NuevoProPass789!', $user->password))->toBeTrue();
});

it('updates manual location and address successfully (HU022)', function () {
    $commune = Commune::create([
        'name' => 'El Poblado',
        'code' => '14',
        'type' => CommuneType::Comuna,
        'latitude' => 6.2089,
        'longitude' => -75.5682,
    ]);

    $user = User::factory()->client()->create([
        'address' => 'Carrera 70 # 10-20',
        'latitude' => null,
        'longitude' => null,
    ]);

    Sanctum::actingAs($user);

    $response = $this->patchJson('/api/v1/me', [
        'address' => 'Calle 10 # 43E-12',
        'commune_id' => $commune->id,
        'latitude' => 6.2089,
        'longitude' => -75.5682,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.address', 'Calle 10 # 43E-12')
        ->assertJsonPath('data.commune.id', $commune->id)
        ->assertJsonPath('data.latitude', 6.2089)
        ->assertJsonPath('data.longitude', -75.5682);

    $user->refresh();
    expect($user->address)->toBe('Calle 10 # 43E-12')
        ->and($user->commune_id)->toBe($commune->id)
        ->and($user->latitude)->toBe(6.2089)
        ->and($user->longitude)->toBe(-75.5682);
});

it('updates location without coordinates when only address and commune are provided (HU022)', function () {
    $commune = Commune::create([
        'name' => 'Laureles-Estadio',
        'code' => '11',
        'type' => CommuneType::Comuna,
        'latitude' => 6.2453,
        'longitude' => -75.5946,
    ]);

    $user = User::factory()->client()->create([
        'address' => 'Calle 50 # 50-50',
    ]);

    Sanctum::actingAs($user);

    $response = $this->patchJson('/api/v1/me', [
        'address' => 'Circular 1 # 70-30',
        'commune_id' => $commune->id,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.address', 'Circular 1 # 70-30')
        ->assertJsonPath('data.commune.id', $commune->id);

    $user->refresh();
    expect($user->address)->toBe('Circular 1 # 70-30')
        ->and($user->commune_id)->toBe($commune->id);
});

it('rejects invalid commune id when updating location (HU022)', function () {
    $user = User::factory()->client()->create();

    Sanctum::actingAs($user);

    $response = $this->patchJson('/api/v1/me', [
        'commune_id' => 999999,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['commune_id']);
});

it('rejects out of range coordinates (HU022)', function () {
    $user = User::factory()->client()->create();

    Sanctum::actingAs($user);

    $response = $this->patchJson('/api/v1/me', [
        'latitude' => 150.0,
        'longitude' => -250.0,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['latitude', 'longitude']);
});

it('requires authentication to update location (HU022)', function () {
    $response = $this->patchJson('/api/v1/me', [
        'address' => 'Calle 10 # 10-10',
    ]);

    $response->assertUnauthorized();
});

it('denies blocked user from updating location (HU022)', function () {
    $user = User::factory()->client()->blocked()->create();

    Sanctum::actingAs($user);

    $response = $this->patchJson('/api/v1/me', [
        'address' => 'Calle 10 # 10-10',
    ]);

    $response->assertForbidden();
});

it('uploads and updates user avatar (HU032)', function () {
    Storage::fake('public');

    $user = User::factory()->client()->create();
    Sanctum::actingAs($user);

    $file = UploadedFile::fake()->image('avatar.jpg', 200, 200);

    $response = $this->postJson('/api/v1/me/avatar', [
        'avatar' => $file,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => ['avatar_url']]);

    $user->refresh();
    expect($user->avatar_path)->not->toBeNull();
    Storage::disk('public')->assertExists($user->avatar_path);
});

it('rejects invalid avatar file (HU032)', function () {
    $user = User::factory()->client()->create();
    Sanctum::actingAs($user);

    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    $response = $this->postJson('/api/v1/me/avatar', [
        'avatar' => $file,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['avatar']);
});

it('soft-deletes account when password matches and has no active bookings (HU023)', function () {
    $user = User::factory()->client()->create([
        'password' => 'Password123!',
    ]);

    Sanctum::actingAs($user);

    $response = $this->deleteJson('/api/v1/me', [
        'current_password' => 'Password123!',
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Tu cuenta ha sido eliminada correctamente.',
        ]);

    expect(User::find($user->id))->toBeNull()
        ->and(User::withTrashed()->find($user->id))->not->toBeNull();
});

it('anonymizes the deleted account and removes its files and sessions (HU023, D12)', function () {
    Storage::fake('public');
    Storage::fake('local');
    Notification::fake();

    $pro = User::factory()->professional()->verified()->create([
        'name' => 'Juan Castaño',
        'email' => 'juan@example.com',
        'phone' => '3001234567',
        'address' => 'Calle 10 # 20-30',
        'latitude' => 6.25,
        'longitude' => -75.56,
        'password' => 'Password123!',
        'avatar_path' => UploadedFile::fake()->image('yo.jpg')->store('avatars', 'public'),
    ]);
    $profile = $pro->professionalProfile;
    $portfolioPath = UploadedFile::fake()->image('obra.jpg')->store('portfolio', 'public');
    $profile->portfolioItems()->create(['image_path' => $portfolioPath]);
    $documentPath = UploadedFile::fake()->create('cedula.pdf', 100)->store('verification-documents', 'local');
    VerificationDocument::factory()
        ->for(VerificationRequest::factory()->for($pro))
        ->create(['file_path' => $documentPath]);
    $fan = User::factory()->client()->create();
    Favorite::create(['client_id' => $fan->id, 'professional_id' => $pro->id]);
    $pro->createToken('api-token');
    $avatarPath = $pro->avatar_path;

    Sanctum::actingAs($pro);
    $this->deleteJson('/api/v1/me', ['current_password' => 'Password123!'])->assertOk();

    $deleted = User::withTrashed()->find($pro->id);
    expect($deleted->name)->toBe('Usuario eliminado')
        ->and($deleted->email)->toBe("eliminado-{$pro->id}@maestros.invalid")
        ->and($deleted->phone)->toBeNull()
        ->and($deleted->address)->toBeNull()
        ->and($deleted->latitude)->toBeNull()
        ->and($deleted->avatar_path)->toBeNull()
        ->and($deleted->tokens()->count())->toBe(0)
        ->and($deleted->verificationRequests()->count())->toBe(0)
        ->and($profile->portfolioItems()->count())->toBe(0)
        ->and($profile->fresh()->description)->toBeNull()
        ->and($profile->services()->where('is_active', true)->count())->toBe(0)
        ->and(Favorite::where('professional_id', $pro->id)->exists())->toBeFalse();

    Storage::disk('public')->assertMissing([$avatarPath, $portfolioPath]);
    Storage::disk('local')->assertMissing($documentPath);

    // Ya no puede entrar con su correo de antes.
    $this->postJson('/api/v1/auth/login', ['email' => 'juan@example.com', 'password' => 'Password123!'])
        ->assertUnauthorized();
});

it('keeps the bookings and reviews of the other party after deleting the account (HU023, D12)', function () {
    $client = User::factory()->client()->create(['password' => 'Password123!']);
    $booking = Booking::factory()->create([
        'client_id' => $client->id,
        'status' => BookingStatus::Completed,
    ]);
    $pro = $booking->professional;

    Sanctum::actingAs($client);
    $this->deleteJson('/api/v1/me', ['current_password' => 'Password123!'])->assertOk();

    Sanctum::actingAs($pro);
    $this->getJson("/api/v1/bookings/{$booking->id}")
        ->assertOk()
        ->assertJsonPath('data.client.id', $client->id)
        ->assertJsonPath('data.client.name', 'Usuario eliminado');
});

it('rejects account deletion when current password is invalid (HU023)', function () {
    $user = User::factory()->client()->create([
        'password' => 'Password123!',
    ]);

    Sanctum::actingAs($user);

    $response = $this->deleteJson('/api/v1/me', [
        'current_password' => 'WrongPassword!',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['current_password']);

    expect(User::find($user->id))->not->toBeNull();
});

it('prevents account deletion if user has pending or in-progress bookings (HU023)', function () {
    $user = User::factory()->client()->create([
        'password' => 'Password123!',
    ]);
    $pro = User::factory()->professional()->create();

    Booking::factory()->create([
        'client_id' => $user->id,
        'professional_id' => $pro->id,
        'status' => BookingStatus::Pending,
    ]);

    Sanctum::actingAs($user);

    $response = $this->deleteJson('/api/v1/me', [
        'current_password' => 'Password123!',
    ]);

    $response->assertStatus(422);
    expect(User::find($user->id))->not->toBeNull();
});
