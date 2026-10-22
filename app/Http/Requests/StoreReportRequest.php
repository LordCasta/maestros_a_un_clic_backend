<?php

namespace App\Http\Requests;

use App\Models\Booking;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * HU024. Se reporta desde el perfil de un profesional (sin reserva) o desde una reserva;
 * en ese caso, quien reporta y el reportado deben ser sus dos participantes.
 */
class StoreReportRequest extends FormRequest
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
            'reported_user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->whereNull('deleted_at'),
                'different:reporter_id',
            ],
            'booking_id' => ['nullable', 'integer', 'exists:bookings,id'],
            'reason' => ['required', 'string', 'min:3', 'max:100'],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty() || ! $this->filled('booking_id')) {
                    return;
                }

                $booking = Booking::find($this->integer('booking_id'));
                $participants = [(int) $booking->client_id, (int) $booking->professional_id];

                // reported_user_id ya es distinto de quien reporta: si ambos están, es la otra parte.
                if (! in_array($this->user()->id, $participants, true)
                    || ! in_array($this->integer('reported_user_id'), $participants, true)) {
                    $validator->errors()->add(
                        'booking_id',
                        'Solo puedes reportar a la otra parte de una reserva en la que participaste.',
                    );
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reporter_id' => $this->user()?->id,
        ]);
    }
}
