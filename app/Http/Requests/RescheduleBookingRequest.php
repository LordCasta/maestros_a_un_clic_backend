<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * HU012: nueva hora para la reserva. Debe ser una de GET /professionals/{id}/slots;
 * el horario, los bloqueos y los cruces los valida BookingService.
 */
class RescheduleBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'starts_at' => ['required', 'date', 'after:now'],
        ];
    }
}
