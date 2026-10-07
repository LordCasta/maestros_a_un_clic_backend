<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * HU004. El profesional, el precio y la hora de fin se derivan del servicio.
 * Quién puede reservar lo decide BookingPolicy::create, no este request.
 */
class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'professional_service_id' => ['required', 'integer', 'exists:professional_services,id'],
            'starts_at' => ['required', 'date', 'after:now'],
            'description' => ['nullable', 'string', 'max:2000'],
            'address' => ['required', 'string', 'max:255'],
            'commune_id' => ['nullable', 'integer', 'exists:communes,id'],
        ];
    }
}
