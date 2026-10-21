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
            'message' => 'Te enviamos por correo el enlace para restablecer tu contraseña.',
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

it('rejects password reset link request for unregistered email (HU020)', function () {
    Notification::fake();

    $response = $this->postJson('/api/v1/auth/forgot-password', [
        'email' => 'noexiste@example.com',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);

    Notification::assertNothingSent();
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
