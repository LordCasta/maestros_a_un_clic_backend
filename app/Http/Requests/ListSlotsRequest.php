<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * HU025: horas disponibles de un profesional para un servicio en una fecha.
 */
class ListSlotsRequest extends FormRequest
{
    /** Hasta cuántos días adelante se puede consultar la agenda. */
    public const MAX_DAYS_AHEAD = 60;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:today',
                'before_or_equal:'.now()->addDays(self::MAX_DAYS_AHEAD)->toDateString(),
            ],
            'service_id' => ['required', 'integer'],
        ];
    }
}
