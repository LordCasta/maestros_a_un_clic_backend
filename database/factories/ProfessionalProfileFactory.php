<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\ProfessionalProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProfessionalProfile>
 */
class ProfessionalProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => Role::Professional]),
            'description' => fake()->paragraph(3),
            'experience_years' => fake()->numberBetween(1, 25),
            'hourly_rate' => fake()->numberBetween(8, 30) * 5000,
            'service_radius_km' => fake()->randomElement([5, 10, 15]),
            'buffer_minutes' => fake()->randomElement([15, 30, 60]),
        ];
    }
}
