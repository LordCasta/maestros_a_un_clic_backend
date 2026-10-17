<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReplaceVerificationDocumentRequest;
use App\Http\Requests\StoreVerificationRequest;
use App\Http\Resources\VerificationDocumentResource;
use App\Http\Resources\VerificationRequestResource;
use App\Models\VerificationDocument;
use App\Services\VerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function __construct(private readonly VerificationService $verification) {}

    /**
     * HU008, HU013: consultar el estado actual de verificación y los documentos cargados.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $latest = $this->verification->getLatestForUser($user);

        return $this->ok([
            'verification_status' => $user->verification_status,
            'latest_request' => $latest ? new VerificationRequestResource($latest) : null,
        ]);
    }

    /**
     * HU008, HU013: enviar solicitud inicial de verificación con documentos KYC.
     */
    public function store(StoreVerificationRequest $request): JsonResponse
    {
        $verificationRequest = $this->verification->submit($request->user(), $request->validated());

        return $this->created(
            new VerificationRequestResource($verificationRequest),
            'Documentos enviados para verificación.'
        );
    }

    /**
     * HU008: reemplazar un documento rechazado y reenviar a revisión.
     */
    public function replaceDocument(ReplaceVerificationDocumentRequest $request, VerificationDocument $document): JsonResponse
    {
        $this->authorize('replace', $document);

        $updated = $this->verification->replaceDocument(
            $request->user(),
            $document,
            $request->file('file'),
            $request->input('title')
        );

        return $this->ok(
            new VerificationDocumentResource($updated),
            'Documento reemplazado y solicitud reenviada a verificación.'
        );
    }
}
