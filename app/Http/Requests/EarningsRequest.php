<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * HU037: rango de fechas (Y-m-d, en la hora de Colombia). Sin fechas: el mes en curso.
 */
class EarningsRequest extends FormRequest
{
    /** Rango máximo que se puede consultar de una vez. */
    public const MAX_DAYS = 366;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date_format:Y-m-d', 'required_with:to'],
            'to' => ['nullable', 'date_format:Y-m-d', 'required_with:from', 'after_or_equal:from'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                if ($validator->errors()->isNotEmpty() || ! $this->filled(['from', 'to'])) {
                    return;
                }
                $days = (strtotime($this->input('to')) - strtotime($this->input('from'))) / 86400;
                if ($days > self::MAX_DAYS) {
                    $validator->errors()->add('to', 'Consulta como máximo un año a la vez.');
                }
            },
        ];
    }
}
