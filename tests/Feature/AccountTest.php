<?php

use App\Enums\CommuneType;
use App\Models\Commune;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
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
