<?php

namespace App\Services;

use App\Enums\BackgroundCheckStatus;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\VerificationStatus;
use App\Models\User;
use App\Models\VerificationDocument;
use App\Models\VerificationRequest;
use App\Notifications\VerificationStatusChanged;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Lógica de negocio para verificación de cuentas y documentos KYC (HU008, HU013, HU014).
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

    /**
     * HU014: bandeja de solicitudes de verificación para el administrador.
     *
     * @param  array{status?: string, document_type?: string, role?: string, search?: string, date_from?: string, date_to?: string, per_page?: int}  $filters
     */
    public function listForAdmin(array $filters = []): LengthAwarePaginator
    {
        $query = VerificationRequest::query()
            ->with(['user.commune', 'user.professionalProfile', 'documents', 'reviewer'])
            ->latest('submitted_at');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['document_type'])) {
            $query->whereHas('documents', fn ($q) => $q->where('type', $filters['document_type']));
        }

        if (! empty($filters['role'])) {
            $query->whereHas('user', fn ($q) => $q->where('role', $filters['role']));
        }

        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('submitted_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('submitted_at', '<=', $filters['date_to']);
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * HU014: aprobar una solicitud de verificación.
     */
    public function approve(VerificationRequest $request, User $admin): VerificationRequest
    {
        if ($request->status === VerificationStatus::Approved) {
            abort(409, 'Esta solicitud de verificación ya fue aprobada.');
        }

        $hasIdDocument = $request->documents()->where('type', DocumentType::IdDocument)->exists();
        $hasSelfie = $request->documents()->where('type', DocumentType::Selfie)->exists();

        if (! $hasIdDocument || ! $hasSelfie) {
            throw ValidationException::withMessages([
                'documents' => ['Se requieren al menos el documento de identidad y la selfie para aprobar la solicitud.'],
            ]);
        }

        if ($request->documents()->where('status', DocumentStatus::Rejected)->exists()) {
            abort(422, 'No se puede aprobar la solicitud mientras existan documentos en estado rechazado.');
        }

        return DB::transaction(function () use ($request, $admin) {
            $request->update([
                'status' => VerificationStatus::Approved,
                'rejection_reason' => null,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            // Si quedaron documentos en pending, se marcan como aprobados junto con la solicitud
            $request->documents()
                ->where('status', DocumentStatus::Pending)
                ->update(['status' => DocumentStatus::Approved]);

            $request->user->update(['verification_status' => VerificationStatus::Approved]);

            $request->user->notify(new VerificationStatusChanged($request));

            return $request->fresh(['user.commune', 'user.professionalProfile', 'documents', 'reviewer']);
        });
    }

    /**
     * HU014: rechazar una solicitud de verificación con motivo obligatorio.
     */
    public function reject(VerificationRequest $request, User $admin, string $reason): VerificationRequest
    {
        if ($request->status === VerificationStatus::Approved) {
            abort(409, 'No se puede rechazar una solicitud que ya se encuentra aprobada.');
        }

        return DB::transaction(function () use ($request, $admin, $reason) {
            $request->update([
                'status' => VerificationStatus::Rejected,
                'rejection_reason' => $reason,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            $request->user->update(['verification_status' => VerificationStatus::Rejected]);

            $request->user->notify(new VerificationStatusChanged($request, $reason));

            return $request->fresh(['user.commune', 'user.professionalProfile', 'documents', 'reviewer']);
        });
    }

    /**
     * HU014: actualizar el estado individual de un documento (aprobar o rechazar).
     */
    public function updateDocumentStatus(VerificationDocument $document, DocumentStatus $status, ?string $rejectionReason = null): VerificationDocument
    {
        $document->update([
            'status' => $status,
            'rejection_reason' => $status === DocumentStatus::Rejected ? $rejectionReason : null,
        ]);

        return $document->fresh();
    }

    /**
     * HU014: descargar o visualizar archivo seguro del documento.
     */
    public function fileResponse(VerificationDocument $document): BinaryFileResponse|StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($document->file_path), 404, 'Archivo no encontrado.');

        $extension = pathinfo($document->file_path, PATHINFO_EXTENSION);
        $downloadName = ($document->title ? $document->title : basename($document->file_path)).($extension ? ".{$extension}" : '');

        return Storage::disk('local')->response(
            $document->file_path,
            $downloadName,
            ['Content-Disposition' => 'inline']
        );
    }
}
