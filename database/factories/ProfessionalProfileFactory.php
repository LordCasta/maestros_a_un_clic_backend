<?php

namespace Database\Factories;

use App\Models\ProfessionalProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProfessionalProfileFactory extends Factory
{
    protected $model = ProfessionalProfile::class;

    public function definition(): array
    {
        return [
            'description' => fake()->paragraph(3),
            'experience_years' => fake()->numberBetween(0,20),
            'hourly_rate' => fake()->randomFloat(2,10,100),
            'commune' => fake()->city(),
            'is_verified' => fake()->boolean(20),
            'availability_status' => 'available',
        ];
    }
}

