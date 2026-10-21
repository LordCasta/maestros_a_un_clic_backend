<?php

use App\Enums\DocumentStatus;
use App\Enums\VerificationStatus;
use App\Models\User;
use App\Models\VerificationDocument;
use App\Models\VerificationRequest;
use App\Notifications\VerificationStatusChanged;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

describe('HU013 & HU014: Admin verification review and approval', function () {
    beforeEach(function () {
        Storage::fake('local');
    });

    test('it allows an admin to list verification requests with pagination', function () {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $user1 = User::factory()->professional()->create(['name' => 'Albeiro Carpintero']);
        $user2 = User::factory()->client()->create(['name' => 'Beatriz Gómez']);

        VerificationRequest::factory()->for($user1)->create(['status' => VerificationStatus::Pending]);
        VerificationRequest::factory()->for($user2)->create(['status' => VerificationStatus::Pending]);

        $response = $this->getJson('/api/v1/admin/verifications');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'status',
                        'user' => ['id', 'name', 'email', 'role'],
                        'submitted_at',
                    ],
                ],
                'meta' => ['current_page', 'total', 'per_page'],
            ]);

        expect($response->json('meta.total'))->toBe(2);
    });

    test('it filters verification requests by status, role, and search query', function () {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $pro = User::factory()->professional()->create(['name' => 'Carlos Maestro', 'email' => 'carlos@ejemplo.com']);
        $client = User::factory()->client()->create(['name' => 'Diana Cliente', 'email' => 'diana@ejemplo.com']);

        $req1 = VerificationRequest::factory()->for($pro)->create(['status' => VerificationStatus::Pending]);
        $req2 = VerificationRequest::factory()->for($client)->create(['status' => VerificationStatus::Approved]);

        // Filtro por estado pending
        $resPending = $this->getJson('/api/v1/admin/verifications?status=pending');
        $resPending->assertOk()->assertJsonCount(1, 'data');
        expect($resPending->json('data.0.id'))->toBe($req1->id);

        // Filtro por rol client
        $resClient = $this->getJson('/api/v1/admin/verifications?role=client');
        $resClient->assertOk()->assertJsonCount(1, 'data');
        expect($resClient->json('data.0.id'))->toBe($req2->id);

        // Filtro por búsqueda de nombre
        $resSearch = $this->getJson('/api/v1/admin/verifications?search=Carlos');
        $resSearch->assertOk()->assertJsonCount(1, 'data');
        expect($resSearch->json('data.0.id'))->toBe($req1->id);
    });

    test('it allows an admin to view verification request details with documents', function () {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $user = User::factory()->professional()->create();
        $request = VerificationRequest::factory()->for($user)->create([
            'status' => VerificationStatus::Pending,
        ]);

        VerificationDocument::factory()->idDocument()->for($request)->create();
        VerificationDocument::factory()->selfie()->for($request)->create();

        $response = $this->getJson("/api/v1/admin/verifications/{$request->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $request->id)
            ->assertJsonPath('data.status', VerificationStatus::Pending->value)
            ->assertJsonCount(2, 'data.documents')
            ->assertJsonPath('data.user.id', $user->id);
    });

    test('it allows an admin to approve a verification request and notifies the user', function () {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $user = User::factory()->professional()->create([
            'verification_status' => VerificationStatus::Pending,
        ]);
        $request = VerificationRequest::factory()->for($user)->create([
            'status' => VerificationStatus::Pending,
        ]);

        $idDoc = VerificationDocument::factory()->idDocument()->for($request)->create([
            'status' => DocumentStatus::Pending,
        ]);
        $selfieDoc = VerificationDocument::factory()->selfie()->for($request)->create([
            'status' => DocumentStatus::Pending,
        ]);

        $response = $this->postJson("/api/v1/admin/verifications/{$request->id}/approve");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Solicitud de verificación aprobada.')
            ->assertJsonPath('data.status', VerificationStatus::Approved->value);

        expect($request->fresh()->status)->toBe(VerificationStatus::Approved)
            ->and($request->fresh()->reviewed_by)->toBe($admin->id)
            ->and($request->fresh()->reviewed_at)->not->toBeNull()
            ->and($idDoc->fresh()->status)->toBe(DocumentStatus::Approved)
            ->and($selfieDoc->fresh()->status)->toBe(DocumentStatus::Approved)
            ->and($user->fresh()->verification_status)->toBe(VerificationStatus::Approved);

        Notification::assertSentTo($user, VerificationStatusChanged::class, function ($n) {
            return $n->verificationRequest->status === VerificationStatus::Approved;
        });
    });

    test('it prevents approving a verification request when required documents are missing', function () {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $user = User::factory()->professional()->create(['verification_status' => VerificationStatus::Pending]);
        $request = VerificationRequest::factory()->for($user)->create(['status' => VerificationStatus::Pending]);

        // Solo certificado, falta id_document y selfie
        VerificationDocument::factory()->certificate()->for($request)->create();

        $response = $this->postJson("/api/v1/admin/verifications/{$request->id}/approve");

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['documents']);
    });

    test('it prevents approving a verification request when any document is rejected', function () {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $user = User::factory()->professional()->create(['verification_status' => VerificationStatus::Pending]);
        $request = VerificationRequest::factory()->for($user)->create(['status' => VerificationStatus::Pending]);

        VerificationDocument::factory()->idDocument()->for($request)->create(['status' => DocumentStatus::Approved]);
        VerificationDocument::factory()->selfie()->for($request)->rejected('Foto oscura')->create();

        $response = $this->postJson("/api/v1/admin/verifications/{$request->id}/approve");

        $response->assertUnprocessable();
    });

    test('it prevents approving an already approved verification request', function () {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $user = User::factory()->professional()->create(['verification_status' => VerificationStatus::Approved]);
        $request = VerificationRequest::factory()->for($user)->approved()->create();
        VerificationDocument::factory()->idDocument()->for($request)->approved()->create();
        VerificationDocument::factory()->selfie()->for($request)->approved()->create();

        $response = $this->postJson("/api/v1/admin/verifications/{$request->id}/approve");

        $response->assertStatus(409);
    });

    test('it allows an admin to reject a verification request with a mandatory reason', function () {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $user = User::factory()->professional()->create(['verification_status' => VerificationStatus::Pending]);
        $request = VerificationRequest::factory()->for($user)->create(['status' => VerificationStatus::Pending]);

        $response = $this->postJson("/api/v1/admin/verifications/{$request->id}/reject", [
            'reason' => 'La cédula y la selfie no coinciden con la persona registrada.',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Solicitud de verificación rechazada.')
            ->assertJsonPath('data.status', VerificationStatus::Rejected->value)
            ->assertJsonPath('data.rejection_reason', 'La cédula y la selfie no coinciden con la persona registrada.');

        expect($request->fresh()->status)->toBe(VerificationStatus::Rejected)
            ->and($request->fresh()->reviewed_by)->toBe($admin->id)
            ->and($request->fresh()->rejection_reason)->toBe('La cédula y la selfie no coinciden con la persona registrada.')
            ->and($user->fresh()->verification_status)->toBe(VerificationStatus::Rejected);

        Notification::assertSentTo($user, VerificationStatusChanged::class, function ($n) {
            return $n->verificationRequest->status === VerificationStatus::Rejected
                && $n->reason === 'La cédula y la selfie no coinciden con la persona registrada.';
        });
    });

    test('it validates required reason when rejecting a verification request', function () {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $user = User::factory()->professional()->create();
        $request = VerificationRequest::factory()->for($user)->create();

        $response = $this->postJson("/api/v1/admin/verifications/{$request->id}/reject", []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['reason']);
    });

    test('it allows an admin to update an individual document status', function () {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $doc = VerificationDocument::factory()->idDocument()->create([
            'status' => DocumentStatus::Pending,
        ]);

        // Aprobar documento individual
        $resApprove = $this->patchJson("/api/v1/admin/verification-documents/{$doc->id}", [
            'status' => DocumentStatus::Approved->value,
        ]);

        $resApprove->assertOk()
            ->assertJsonPath('data.status', DocumentStatus::Approved->value);
        expect($doc->fresh()->status)->toBe(DocumentStatus::Approved);

        // Rechazar documento individual con motivo
        $resReject = $this->patchJson("/api/v1/admin/verification-documents/{$doc->id}", [
            'status' => DocumentStatus::Rejected->value,
            'rejection_reason' => 'El documento está borroso.',
        ]);

        $resReject->assertOk()
            ->assertJsonPath('data.status', DocumentStatus::Rejected->value)
            ->assertJsonPath('data.rejection_reason', 'El documento está borroso.');
        expect($doc->fresh()->status)->toBe(DocumentStatus::Rejected)
            ->and($doc->fresh()->rejection_reason)->toBe('El documento está borroso.');
    });

    test('it requires a reason when rejecting an individual document', function () {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $doc = VerificationDocument::factory()->idDocument()->create();

        $response = $this->patchJson("/api/v1/admin/verification-documents/{$doc->id}", [
            'status' => DocumentStatus::Rejected->value,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['rejection_reason']);
    });

    test('it allows an admin to download or view a private document file', function () {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $fakeFile = UploadedFile::fake()->create('cedula.pdf', 100, 'application/pdf');
        $storedPath = Storage::disk('local')->putFile('verification-documents', $fakeFile);

        $doc = VerificationDocument::factory()->idDocument()->create([
            'file_path' => $storedPath,
            'title' => 'Cédula de ciudadanía',
        ]);

        $response = $this->get("/api/v1/admin/verification-documents/{$doc->id}/file");

        $response->assertOk();
    });

    test('it returns 404 when downloading a non-existent document file', function () {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $doc = VerificationDocument::factory()->idDocument()->create([
            'file_path' => 'verification-documents/no-existe.pdf',
        ]);

        $response = $this->getJson("/api/v1/admin/verification-documents/{$doc->id}/file");

        $response->assertNotFound();
    });

    test('it forbids non-admin users from accessing admin verification endpoints', function () {
        $client = User::factory()->client()->create();
        $pro = User::factory()->professional()->create();
        $req = VerificationRequest::factory()->create();
        $doc = VerificationDocument::factory()->create();

        Sanctum::actingAs($client);
        $this->getJson('/api/v1/admin/verifications')->assertForbidden();
        $this->postJson("/api/v1/admin/verifications/{$req->id}/approve")->assertForbidden();

        Sanctum::actingAs($pro);
        $this->getJson('/api/v1/admin/verifications')->assertForbidden();
        $this->postJson("/api/v1/admin/verifications/{$req->id}/reject", ['reason' => 'test'])->assertForbidden();
        $this->patchJson("/api/v1/admin/verification-documents/{$doc->id}", ['status' => 'approved'])->assertForbidden();
    });

    test('it requires authentication for admin verification endpoints', function () {
        $this->getJson('/api/v1/admin/verifications')->assertUnauthorized();
        $this->getJson('/api/v1/admin/verifications/1')->assertUnauthorized();
        $this->postJson('/api/v1/admin/verifications/1/approve')->assertUnauthorized();
        $this->postJson('/api/v1/admin/verifications/1/reject', ['reason' => 'test'])->assertUnauthorized();
        $this->patchJson('/api/v1/admin/verification-documents/1', ['status' => 'approved'])->assertUnauthorized();
    });
});
