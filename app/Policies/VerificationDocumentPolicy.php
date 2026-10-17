<?php

namespace App\Policies;

use App\Enums\DocumentStatus;
use App\Enums\VerificationStatus;
use App\Models\User;
use App\Models\VerificationDocument;
use Illuminate\Auth\Access\Response;

class VerificationDocumentPolicy
{
    public function replace(User $user, VerificationDocument $document): Response
    {
        if ($document->verificationRequest->user_id !== $user->id) {
            return Response::deny('No tienes permiso para modificar este documento.');
        }

        if ($document->status !== DocumentStatus::Rejected && $document->verificationRequest->status !== VerificationStatus::Rejected) {
            return Response::deny('Solo puedes reemplazar documentos que hayan sido rechazados.');
        }

        return Response::allow();
    }
}
