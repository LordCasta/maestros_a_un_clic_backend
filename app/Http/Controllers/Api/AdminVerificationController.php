<?php

namespace App\Http\Controllers\Api;

use App\Enums\DocumentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminRejectVerificationRequest;
use App\Http\Requests\AdminUpdateVerificationDocumentRequest;
use App\Http\Resources\VerificationDocumentResource;
use App\Http\Resources\VerificationRequestResource;
use App\Models\VerificationDocument;
use App\Models\VerificationRequest;
use App\Services\VerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminVerificationController extends Controller
{
    public function __construct(private readonly VerificationService $verification) {}

    /**
     * HU014: bandeja de solicitudes de verificación para el administrador con filtros.
     */
    public function index(Request $request): JsonResponse
    {
        $paginator = $this->verification->listForAdmin($request->only([
            'status',
            'document_type',
            'role',
            'search',
            'date_from',
            'date_to',
            'per_page',
        ]));

        return $this->paginated(VerificationRequestResource::collection($paginator), $paginator);
    }

    /**
     * HU014: detalle completo de una solicitud de verificación.
     */
    public function show(VerificationRequest $verificationRequest): JsonResponse
    {
        $verificationRequest->loadMissing([
            'user.commune',
            'user.professionalProfile',
            'documents',
            'reviewer',
        ]);

        return $this->ok(new VerificationRequestResource($verificationRequest));
    }

    /**
     * HU014: aprobar una solicitud de verificación y el usuario correspondiente.
     */
    public function approve(VerificationRequest $verificationRequest, Request $request): JsonResponse
    {
        $approved = $this->verification->approve($verificationRequest, $request->user());

        return $this->ok(
            new VerificationRequestResource($approved),
            'Solicitud de verificación aprobada.'
        );
    }

    /**
     * HU014: rechazar una solicitud de verificación con motivo obligatorio.
     */
    public function reject(AdminRejectVerificationRequest $request, VerificationRequest $verificationRequest): JsonResponse
    {
        $rejected = $this->verification->reject(
            $verificationRequest,
            $request->user(),
            $request->validated('reason')
        );

        return $this->ok(
            new VerificationRequestResource($rejected),
            'Solicitud de verificación rechazada.'
        );
    }

    /**
     * HU014: marcar documento individual como aprobado o rechazado con motivo.
     */
    public function updateDocument(AdminUpdateVerificationDocumentRequest $request, VerificationDocument $document): JsonResponse
    {
        $updated = $this->verification->updateDocumentStatus(
            $document,
            DocumentStatus::from($request->validated('status')),
            $request->validated('rejection_reason')
        );

        return $this->ok(
            new VerificationDocumentResource($updated),
            'Estado del documento actualizado.'
        );
    }

    /**
     * HU014: visualización o descarga segura del archivo privado.
     */
    public function file(VerificationDocument $document): BinaryFileResponse|StreamedResponse
    {
        return $this->verification->fileResponse($document);
    }
}
