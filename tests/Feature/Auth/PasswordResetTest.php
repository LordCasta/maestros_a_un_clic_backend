<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

it('sends password reset link to registered user (HU020)', function () {
    Notification::fake();

    $user = User::factory()->client()->create(['email' => 'cliente@example.com']);

    $response = $this->postJson('/api/v1/auth/forgot-password', [
        'email' => 'cliente@example.com',
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Si el correo está registrado, te enviamos el enlace para restablecer tu contraseña.',
            'data' => null,
        ]);

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
        $mail = $notification->toMail($user);
        expect($mail->actionUrl)->toContain('/reset-password?token=')
            ->and($mail->actionUrl)->toContain('email='.urlencode($user->email));

        return true;
    });

    expect(DB::table('password_reset_tokens')->where('email', 'cliente@example.com')->exists())->toBeTrue();
});

it('answers the same for an unregistered email so accounts cannot be discovered (HU020)', function () {
    Notification::fake();
    User::factory()->client()->create(['email' => 'cliente@example.com']);

    $registered = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'cliente@example.com']);
    $unknown = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'noexiste@example.com']);

    $unknown->assertOk();
    expect($unknown->json())->toBe($registered->json());
    Notification::assertCount(1);
});

it('answers the same when the link was requested a moment ago (HU020)', function () {
    Notification::fake();
    User::factory()->client()->create(['email' => 'cliente@example.com']);

    $first = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'cliente@example.com']);
    $again = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'cliente@example.com']);

    $again->assertOk();
    expect($again->json())->toBe($first->json());
    Notification::assertCount(1);
});

it('points the reset link to the configured frontend (HU020)', function () {
    Notification::fake();
    config(['app.frontend_url' => 'https://maestros.example/']);
    $user = User::factory()->client()->create();

    $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])->assertOk();

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
        expect($notification->toMail($user)->actionUrl)->toStartWith('https://maestros.example/reset-password?token=');

        return true;
    });
});

it('validates email field on forgot-password (HU020)', function () {
    $response = $this->postJson('/api/v1/auth/forgot-password', [
        'email' => 'correo-invalido',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('resets password successfully with valid token (HU020)', function () {
    $user = User::factory()->client()->create([
        'email' => 'cliente@example.com',
        'password' => 'AntiguaClave123!',
    ]);

    $token = Password::broker()->createToken($user);

    $response = $this->postJson('/api/v1/auth/reset-password', [
        'token' => $token,
        'email' => 'cliente@example.com',
        'password' => 'NuevaClaveSegura789!',
        'password_confirmation' => 'NuevaClaveSegura789!',
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Tu contraseña fue restablecida.',
            'data' => null,
        ]);

    $user->refresh();
    expect(Hash::check('NuevaClaveSegura789!', $user->password))->toBeTrue();

    // Puede iniciar sesión con la nueva contraseña
    $login = $this->postJson('/api/v1/auth/login', [
        'email' => 'cliente@example.com',
        'password' => 'NuevaClaveSegura789!',
    ]);

    $login->assertOk();
});

it('rejects reset password with invalid token (HU020)', function () {
    User::factory()->client()->create([
        'email' => 'cliente@example.com',
        'password' => 'AntiguaClave123!',
    ]);

    $response = $this->postJson('/api/v1/auth/reset-password', [
        'token' => 'token-invalido-123456',
        'email' => 'cliente@example.com',
        'password' => 'NuevaClaveSegura789!',
        'password_confirmation' => 'NuevaClaveSegura789!',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('rejects reset password when confirmation does not match (HU020)', function () {
    $user = User::factory()->client()->create(['email' => 'cliente@example.com']);
    $token = Password::broker()->createToken($user);

    $response = $this->postJson('/api/v1/auth/reset-password', [
        'token' => $token,
        'email' => 'cliente@example.com',
        'password' => 'NuevaClaveSegura789!',
        'password_confirmation' => 'OtraClaveDistinta!',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

it('rejects reset password when token has expired (HU020)', function () {
    $user = User::factory()->client()->create(['email' => 'cliente@example.com']);
    $token = Password::broker()->createToken($user);

    // Viajamos en el tiempo más allá del tiempo de expiración (60 minutos)
    Carbon::setTestNow(now()->addMinutes(61));

    $response = $this->postJson('/api/v1/auth/reset-password', [
        'token' => $token,
        'email' => 'cliente@example.com',
        'password' => 'NuevaClaveSegura789!',
        'password_confirmation' => 'NuevaClaveSegura789!',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);

    Carbon::setTestNow();
});
