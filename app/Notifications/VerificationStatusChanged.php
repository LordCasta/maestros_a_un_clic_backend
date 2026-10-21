<?php

namespace App\Notifications;

use App\Enums\VerificationStatus;
use App\Models\VerificationRequest;
use Illuminate\Notifications\Notification;

/**
 * HU013, HU014: aviso al usuario (cliente o profesional) de la aprobación o rechazo de su verificación.
 */
class VerificationStatusChanged extends Notification
{
    public function __construct(
        public VerificationRequest $verificationRequest,
        public ?string $reason = null
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function databaseType(object $notifiable): string
    {
        return $this->type();
    }

    public function broadcastType(): string
    {
        return $this->type();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $approved = $this->verificationRequest->status === VerificationStatus::Approved;

        return [
            'verification_request_id' => $this->verificationRequest->id,
            'status' => $this->verificationRequest->status->value,
            'title' => $approved ? 'Cuenta verificada' : 'Solicitud de verificación rechazada',
            'body' => $approved
                ? 'Tu solicitud de verificación ha sido aprobada. Tu cuenta ya cuenta con la insignia de verificación.'
                : 'Tu solicitud de verificación fue rechazada.'.($this->reason ? " Motivo: {$this->reason}" : ' Puedes actualizar tus documentos e intentarlo nuevamente.'),
        ];
    }

    private function type(): string
    {
        return $this->verificationRequest->status === VerificationStatus::Approved
            ? 'verification.approved'
            : 'verification.rejected';
    }
}
