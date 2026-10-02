<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Documento KYC. El archivo vive en el disco privado (`local`), nunca en `public`.
 */
class VerificationDocument extends Model
{
    protected $fillable = [
        'verification_request_id',
        'type',
        'title',
        'file_path',
        'status',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'type' => DocumentType::class,
            'status' => DocumentStatus::class,
        ];
    }

    public function verificationRequest(): BelongsTo
    {
        return $this->belongsTo(VerificationRequest::class);
    }
}
