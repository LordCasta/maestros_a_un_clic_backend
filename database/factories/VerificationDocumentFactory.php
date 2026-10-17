<?php

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\VerificationDocument;
use App\Models\VerificationRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VerificationDocument>
 */
class VerificationDocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'verification_request_id' => VerificationRequest::factory(),
            'type' => fake()->randomElement(DocumentType::cases()),
            'title' => fake()->sentence(3),
            'file_path' => 'verification-documents/'.fake()->uuid().'.pdf',
            'status' => DocumentStatus::Pending,
            'rejection_reason' => null,
        ];
    }

    public function idDocument(): static
    {
        return $this->state(fn () => [
            'type' => DocumentType::IdDocument,
            'title' => 'Cédula de ciudadanía',
        ]);
    }

    public function selfie(): static
    {
        return $this->state(fn () => [
            'type' => DocumentType::Selfie,
            'title' => 'Selfie de verificación',
        ]);
    }

    public function certificate(): static
    {
        return $this->state(fn () => [
            'type' => DocumentType::Certificate,
            'title' => 'Certificado profesional',
        ]);
    }

    public function workEvidence(): static
    {
        return $this->state(fn () => [
            'type' => DocumentType::WorkEvidence,
            'title' => 'Evidencia de trabajo realizado',
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => DocumentStatus::Approved,
        ]);
    }

    public function rejected(string $reason = 'Documento borroso o ilegible'): static
    {
        return $this->state(fn () => [
            'status' => DocumentStatus::Rejected,
            'rejection_reason' => $reason,
        ]);
    }
}
