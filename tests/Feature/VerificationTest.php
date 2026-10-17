<?php

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\VerificationStatus;
use App\Models\User;
use App\Models\VerificationDocument;
use App\Models\VerificationRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

describe('HU008: verification and KYC document upload', function () {
    beforeEach(function () {
        Storage::fake('local');
    });

    test('it allows a professional to submit initial verification documents with id, selfie and certificate', function () {
        $professional = User::factory()->professional()->create([
            'verification_status' => VerificationStatus::Unverified,
        ]);

        Sanctum::actingAs($professional);

        $payload = [
            'documents' => [
                [
                    'type' => DocumentType::IdDocument->value,
                    'title' => 'Cédula de ciudadanía',
                    'file' => UploadedFile::fake()->create('cedula.pdf', 1500, 'application/pdf'),
                ],
                [
                    'type' => DocumentType::Selfie->value,
                    'title' => 'Selfie de verificación',
                    'file' => UploadedFile::fake()->image('selfie.jpg'),
                ],
                [
                    'type' => DocumentType::Certificate->value,
                    'title' => 'Certificado técnico SENA',
                    'file' => UploadedFile::fake()->create('certificado.pdf', 2000, 'application/pdf'),
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/verification', $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Documentos enviados para verificación.')
            ->assertJsonPath('data.status', VerificationStatus::Pending->value)
            ->assertJsonCount(3, 'data.documents');

        $this->assertDatabaseHas('verification_requests', [
            'user_id' => $professional->id,
            'status' => VerificationStatus::Pending->value,
        ]);

        $this->assertDatabaseCount('verification_documents', 3);

        $this->assertEquals(VerificationStatus::Pending, $professional->fresh()->verification_status);

        // Profesional en revisión no aparece en listados públicos (HU008 Criterio 4)
        expect(User::publiclyListed()->where('users.id', $professional->id)->exists())->toBeFalse();
    });

    test('it allows a professional to submit work evidence instead of certificate', function () {
        $professional = User::factory()->professional()->create([
            'verification_status' => VerificationStatus::Unverified,
        ]);

        Sanctum::actingAs($professional);

        $payload = [
            'documents' => [
                [
                    'type' => DocumentType::IdDocument->value,
                    'file' => UploadedFile::fake()->image('cedula.jpg'),
                ],
                [
                    'type' => DocumentType::Selfie->value,
                    'file' => UploadedFile::fake()->image('selfie.jpg'),
                ],
                [
                    'type' => DocumentType::WorkEvidence->value,
                    'title' => 'Instalación de tuberías previa',
                    'file' => UploadedFile::fake()->image('trabajo1.jpg'),
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/verification', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.status', VerificationStatus::Pending->value);
    });

    test('it validates required document categories', function () {
        $professional = User::factory()->professional()->create();
        Sanctum::actingAs($professional);

        // Sin id_document (solo selfie y certificado)
        $response = $this->postJson('/api/v1/verification', [
            'documents' => [
                [
                    'type' => DocumentType::Selfie->value,
                    'file' => UploadedFile::fake()->image('selfie.jpg'),
                ],
                [
                    'type' => DocumentType::Certificate->value,
                    'file' => UploadedFile::fake()->create('cert.pdf', 500, 'application/pdf'),
                ],
                [
                    'type' => DocumentType::WorkEvidence->value,
                    'file' => UploadedFile::fake()->image('evidencia.jpg'),
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['documents']);

        // Sin selfie
        $response = $this->postJson('/api/v1/verification', [
            'documents' => [
                [
                    'type' => DocumentType::IdDocument->value,
                    'file' => UploadedFile::fake()->image('id.jpg'),
                ],
                [
                    'type' => DocumentType::Certificate->value,
                    'file' => UploadedFile::fake()->create('cert.pdf', 500, 'application/pdf'),
                ],
                [
                    'type' => DocumentType::WorkEvidence->value,
                    'file' => UploadedFile::fake()->image('evidencia.jpg'),
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['documents']);

        // Sin certificado ni evidencia
        $response = $this->postJson('/api/v1/verification', [
            'documents' => [
                [
                    'type' => DocumentType::IdDocument->value,
                    'file' => UploadedFile::fake()->image('id.jpg'),
                ],
                [
                    'type' => DocumentType::Selfie->value,
                    'file' => UploadedFile::fake()->image('selfie.jpg'),
                ],
                [
                    'type' => DocumentType::IdDocument->value,
                    'file' => UploadedFile::fake()->image('id2.jpg'),
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['documents']);
    });

    test('it validates format and max file size', function () {
        $professional = User::factory()->professional()->create();
        Sanctum::actingAs($professional);

        $payload = [
            'documents' => [
                [
                    'type' => DocumentType::IdDocument->value,
                    // Archivo no permitido (.txt)
                    'file' => UploadedFile::fake()->create('cedula.txt', 100, 'text/plain'),
                ],
                [
                    'type' => DocumentType::Selfie->value,
                    'file' => UploadedFile::fake()->image('selfie.jpg'),
                ],
                [
                    'type' => DocumentType::Certificate->value,
                    // Archivo superior a 10MB (11000 KB)
                    'file' => UploadedFile::fake()->create('pesado.pdf', 11000, 'application/pdf'),
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/verification', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['documents.0.file', 'documents.2.file']);
    });

    test('it rejects submission if user is already verified', function () {
        $professional = User::factory()->professional()->create([
            'verification_status' => VerificationStatus::Approved,
        ]);
        Sanctum::actingAs($professional);

        $response = $this->postJson('/api/v1/verification', [
            'documents' => [
                [
                    'type' => DocumentType::IdDocument->value,
                    'file' => UploadedFile::fake()->image('id.jpg'),
                ],
                [
                    'type' => DocumentType::Selfie->value,
                    'file' => UploadedFile::fake()->image('selfie.jpg'),
                ],
                [
                    'type' => DocumentType::Certificate->value,
                    'file' => UploadedFile::fake()->create('cert.pdf', 500, 'application/pdf'),
                ],
            ],
        ]);

        $response->assertStatus(409)
            ->assertJsonPath('message', 'Tu cuenta ya está verificada.');
    });

    test('it rejects submission if user already has a pending request', function () {
        $professional = User::factory()->professional()->create([
            'verification_status' => VerificationStatus::Pending,
        ]);
        VerificationRequest::factory()->for($professional)->create([
            'status' => VerificationStatus::Pending,
        ]);

        Sanctum::actingAs($professional);

        $response = $this->postJson('/api/v1/verification', [
            'documents' => [
                [
                    'type' => DocumentType::IdDocument->value,
                    'file' => UploadedFile::fake()->image('id.jpg'),
                ],
                [
                    'type' => DocumentType::Selfie->value,
                    'file' => UploadedFile::fake()->image('selfie.jpg'),
                ],
                [
                    'type' => DocumentType::Certificate->value,
                    'file' => UploadedFile::fake()->create('cert.pdf', 500, 'application/pdf'),
                ],
            ],
        ]);

        $response->assertStatus(409)
            ->assertJsonPath('message', 'Ya tienes una solicitud de verificación en revisión.');
    });

    test('it returns verification status and documents for authenticated user', function () {
        $professional = User::factory()->professional()->create([
            'verification_status' => VerificationStatus::Unverified,
        ]);
        Sanctum::actingAs($professional);

        // Sin solicitud previa
        $response = $this->getJson('/api/v1/verification');
        $response->assertOk()
            ->assertJsonPath('data.verification_status', VerificationStatus::Unverified->value)
            ->assertJsonPath('data.latest_request', null);

        // Con solicitud rechazada y motivo (HU008 Criterio 5)
        $request = VerificationRequest::factory()->for($professional)->rejected('Documento de identidad ilegible')->create();
        VerificationDocument::factory()->idDocument()->rejected('Borroso')->create([
            'verification_request_id' => $request->id,
        ]);
        VerificationDocument::factory()->selfie()->approved()->create([
            'verification_request_id' => $request->id,
        ]);

        $professional->update(['verification_status' => VerificationStatus::Rejected]);

        $response = $this->getJson('/api/v1/verification');
        $response->assertOk()
            ->assertJsonPath('data.verification_status', VerificationStatus::Rejected->value)
            ->assertJsonPath('data.latest_request.rejection_reason', 'Documento de identidad ilegible')
            ->assertJsonPath('data.latest_request.documents.0.status', DocumentStatus::Rejected->value)
            ->assertJsonPath('data.latest_request.documents.0.rejection_reason', 'Borroso')
            ->assertJsonPath('data.latest_request.documents.1.status', DocumentStatus::Approved->value);
    });

    test('it allows replacing a rejected document and resubmits to verification', function () {
        $professional = User::factory()->professional()->create([
            'verification_status' => VerificationStatus::Rejected,
        ]);
        $request = VerificationRequest::factory()->for($professional)->rejected('Por favor reenviar cédula clara')->create();
        $doc = VerificationDocument::factory()->idDocument()->rejected('Cédula cortada')->create([
            'verification_request_id' => $request->id,
        ]);

        Sanctum::actingAs($professional);

        $newFile = UploadedFile::fake()->create('cedula_nueva.pdf', 1200, 'application/pdf');

        $response = $this->postJson("/api/v1/verification/documents/{$doc->id}", [
            'file' => $newFile,
            'title' => 'Cédula de ciudadanía legible',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Documento reemplazado y solicitud reenviada a verificación.')
            ->assertJsonPath('data.status', DocumentStatus::Pending->value)
            ->assertJsonPath('data.rejection_reason', null)
            ->assertJsonPath('data.title', 'Cédula de ciudadanía legible');

        // Documento y solicitud vuelven a estado pendiente
        expect($doc->fresh()->status)->toBe(DocumentStatus::Pending)
            ->and($doc->fresh()->rejection_reason)->toBeNull()
            ->and($request->fresh()->status)->toBe(VerificationStatus::Pending)
            ->and($request->fresh()->rejection_reason)->toBeNull()
            ->and($professional->fresh()->verification_status)->toBe(VerificationStatus::Pending);
    });

    test('it prevents replacing a document of another user', function () {
        $user1 = User::factory()->professional()->create();
        $user2 = User::factory()->professional()->create();

        $request = VerificationRequest::factory()->for($user1)->rejected()->create();
        $doc = VerificationDocument::factory()->idDocument()->rejected()->create([
            'verification_request_id' => $request->id,
        ]);

        Sanctum::actingAs($user2);

        $response = $this->postJson("/api/v1/verification/documents/{$doc->id}", [
            'file' => UploadedFile::fake()->image('nueva.jpg'),
        ]);

        $response->assertForbidden();
    });

    test('it prevents replacing a document that is not rejected', function () {
        $professional = User::factory()->professional()->create();
        $request = VerificationRequest::factory()->for($professional)->create([
            'status' => VerificationStatus::Pending,
        ]);
        $doc = VerificationDocument::factory()->idDocument()->create([
            'verification_request_id' => $request->id,
            'status' => DocumentStatus::Pending,
        ]);

        Sanctum::actingAs($professional);

        $response = $this->postJson("/api/v1/verification/documents/{$doc->id}", [
            'file' => UploadedFile::fake()->image('nueva.jpg'),
        ]);

        $response->assertForbidden();
    });

    test('it requires authentication for all verification endpoints', function () {
        $this->getJson('/api/v1/verification')->assertUnauthorized();
        $this->postJson('/api/v1/verification', [])->assertUnauthorized();
        $this->postJson('/api/v1/verification/documents/1', [])->assertUnauthorized();
    });
});
