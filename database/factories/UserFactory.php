<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Enums\VerificationStatus;
use App\Models\ProfessionalProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '3'.fake()->numerify('#########'),
            'role' => Role::Client,
            'verification_status' => VerificationStatus::Unverified,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function client(): static
    {
        return $this->state(fn () => ['role' => Role::Client]);
    }

    /**
     * Profesional con su perfil creado.
     */
    public function professional(): static
    {
        return $this->state(fn () => ['role' => Role::Professional])
            ->afterCreating(function (User $user) {
                if (! $user->professionalProfile()->exists()) {
                    ProfessionalProfile::factory()->for($user)->create();
                }
            });
    }

    public function admin(): static
    {
        return $this->state(fn () => [
            'role' => Role::Admin,
            'verification_status' => VerificationStatus::Approved,
        ]);
    }

    public function verified(): static
    {
        return $this->state(fn () => ['verification_status' => VerificationStatus::Approved]);
    }

    public function blocked(string $reason = 'Comportamiento indebido'): static
    {
        return $this->state(fn () => [
            'blocked_at' => now(),
            'blocked_reason' => $reason,
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }
}
