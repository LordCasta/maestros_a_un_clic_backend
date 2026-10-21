<?php

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
