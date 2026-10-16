<?php

namespace App\Http\Requests;

use App\Enums\BookingStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Filtros de GET /bookings (HU004: solicitudes por responder; HU016, HU017: historial;
 * HU049: servicios en proceso). `status` acepta uno o varios estados separados por coma:
 * `?status=cancelled,rejected`.
 */
class ListBookingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('status'))) {
            $this->merge(['status' => array_values(array_filter(explode(',', $this->input('status'))))]);
        }
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'array'],
            'status.*' => [Rule::enum(BookingStatus::class)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.*' => 'El estado «:input» no existe.',
        ];
    }
}
