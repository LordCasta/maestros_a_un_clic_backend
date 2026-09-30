<?php

namespace Database\Factories;

use App\Enums\PriceType;
use App\Models\Category;
use App\Models\ProfessionalProfile;
use App\Models\ProfessionalService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProfessionalService>
 */
class ProfessionalServiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'professional_profile_id' => ProfessionalProfile::factory(),
            'category_id' => Category::factory(),
            'title' => ucfirst(fake()->words(3, true)),
            'description' => fake()->sentence(12),
            'price_type' => PriceType::Fixed,
            'price' => fake()->numberBetween(8, 40) * 5000,
            'estimated_duration_minutes' => fake()->randomElement([60, 90, 120, 180]),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
