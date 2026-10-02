<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\ProfessionalService;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * El profesional, el precio y la hora de fin se derivan del servicio reservado.
 *
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_id' => User::factory()->client()->verified(),
            'professional_service_id' => ProfessionalService::factory(),
            'professional_id' => fn (array $attributes) => $this->service($attributes)->professionalProfile->user_id,
            'description' => fake()->sentence(10),
            'address' => fake()->streetAddress(),
            'starts_at' => Carbon::tomorrow()->setHour(fake()->numberBetween(8, 15)),
            'ends_at' => fn (array $attributes) => Carbon::parse($attributes['starts_at'])
                ->addMinutes($this->service($attributes)->estimated_duration_minutes),
            'status' => BookingStatus::Pending,
            'agreed_price' => fn (array $attributes) => $this->service($attributes)->price,
        ];
    }

    public function status(BookingStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }

    private function service(array $attributes): ProfessionalService
    {
        return ProfessionalService::with('professionalProfile')->findOrFail($attributes['professional_service_id']);
    }
}
