<?php

namespace Database\Factories;

use App\Enums\BackgroundCheckStatus;
use App\Enums\VerificationStatus;
use App\Models\User;
use App\Models\VerificationRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VerificationRequest>
 */
class VerificationRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => VerificationStatus::Pending,
            'rejection_reason' => null,
            'background_check_status' => BackgroundCheckStatus::NotChecked,
            'background_check_notes' => null,
            'reviewed_by' => null,
            'submitted_at' => now(),
            'reviewed_at' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => VerificationStatus::Approved,
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(string $reason = 'Documentos no legibles'): static
    {
        return $this->state(fn () => [
            'status' => VerificationStatus::Rejected,
            'rejection_reason' => $reason,
            'reviewed_at' => now(),
        ]);
    }
}
