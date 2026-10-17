<?php

namespace App\Services;

use App\Enums\BackgroundCheckStatus;
use App\Enums\DocumentStatus;
use App\Enums\VerificationStatus;
use App\Models\User;
use App\Models\VerificationDocument;
use App\Models\VerificationRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Lógica de negocio para verificación de cuentas y documentos KYC (HU008, HU013).
 */
class VerificationService
{
    public function __construct(private readonly UploadService $uploads) {}

    public function getLatestForUser(User $user): ?VerificationRequest
    {
        return $user->verificationRequests()
            ->with('documents')
            ->latest('id')
            ->first();
    }

    /**
     * @param  array{documents: array<int, array{file: UploadedFile, type: string, title?: string|null}>}  $data
     */
    public function submit(User $user, array $data): VerificationRequest
    {
        if ($user->verification_status === VerificationStatus::Approved) {
            abort(409, 'Tu cuenta ya está verificada.');
        }

        $hasPending = $user->verificationRequests()
            ->where('status', VerificationStatus::Pending)
            ->exists();

        if ($hasPending) {
            abort(409, 'Ya tienes una solicitud de verificación en revisión.');
        }

        return DB::transaction(function () use ($user, $data) {
            $verificationRequest = VerificationRequest::create([
                'user_id' => $user->id,
                'status' => VerificationStatus::Pending,
                'rejection_reason' => null,
                'background_check_status' => BackgroundCheckStatus::NotChecked,
                'submitted_at' => now(),
            ]);

            foreach ($data['documents'] as $docData) {
                /** @var UploadedFile $file */
                $file = $docData['file'];
                $path = $this->uploads->storePrivate($file, 'verification-documents');

                $verificationRequest->documents()->create([
                    'type' => $docData['type'],
                    'title' => $docData['title'] ?? null,
                    'file_path' => $path,
                    'status' => DocumentStatus::Pending,
                ]);
            }

            $user->update(['verification_status' => VerificationStatus::Pending]);

            return $verificationRequest->load('documents');
        });
    }

    public function replaceDocument(User $user, VerificationDocument $document, UploadedFile $newFile, ?string $title = null): VerificationDocument
    {
        return DB::transaction(function () use ($user, $document, $newFile, $title) {
            $oldPath = $document->file_path;
            $newPath = $this->uploads->storePrivate($newFile, 'verification-documents');

            $document->update([
                'file_path' => $newPath,
                'title' => $title ?? $document->title,
                'status' => DocumentStatus::Pending,
                'rejection_reason' => null,
            ]);

            if ($oldPath && $oldPath !== $newPath) {
                $this->uploads->deletePrivate($oldPath);
            }

            $request = $document->verificationRequest;
            if ($request->status === VerificationStatus::Rejected) {
                $request->update([
                    'status' => VerificationStatus::Pending,
                    'rejection_reason' => null,
                    'submitted_at' => now(),
                ]);

                $user->update(['verification_status' => VerificationStatus::Pending]);
            }

            return $document->fresh();
        });
    }
}
