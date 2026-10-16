<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * HU004: rechazar una solicitud. El motivo es opcional; si se da, lo ve el cliente.
 */
class RejectBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'min:5', 'max:1000'],
        ];
    }
}
