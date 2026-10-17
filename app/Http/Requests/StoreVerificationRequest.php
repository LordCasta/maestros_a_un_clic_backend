<?php

namespace App\Http\Requests;

use App\Enums\DocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * HU008 / HU013: Solicitud inicial de verificación de cuenta y cargue de documentos KYC.
 */
class StoreVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'documents' => ['required', 'array', 'min:3'],
            'documents.*.type' => ['required', 'string', Rule::enum(DocumentType::class)],
            'documents.*.file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
            'documents.*.title' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $documents = $this->input('documents');
            if (! is_array($documents)) {
                return;
            }

            $types = collect($documents)->pluck('type')->filter();

            if (! $types->contains(DocumentType::IdDocument->value)) {
                $validator->errors()->add('documents', 'Debes incluir al menos un documento de identidad.');
            }

            if (! $types->contains(DocumentType::Selfie->value)) {
                $validator->errors()->add('documents', 'Debes incluir una selfie para verificar tu identidad.');
            }

            $hasWorkOrCert = $types->contains(DocumentType::Certificate->value)
                || $types->contains(DocumentType::WorkEvidence->value);

            if (! $hasWorkOrCert) {
                $validator->errors()->add('documents', 'Debes incluir al menos un certificado o evidencia de trabajo.');
            }
        });
    }
}
