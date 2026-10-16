<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * HU003: horario semanal completo (reemplaza al anterior) y tiempo de descanso entre servicios.
 */
class UpdateAvailabilityRulesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'buffer_minutes' => ['required', 'integer', 'min:0', 'max:240'],
            'rules' => ['present', 'array', 'max:28'],
            'rules.*.weekday' => ['required', 'integer', 'between:0,6'],
            'rules.*.start_time' => ['required', 'date_format:H:i'],
            'rules.*.end_time' => ['required', 'date_format:H:i', 'after:rules.*.start_time'],
        ];
    }

    /**
     * Dos franjas del mismo día no pueden cruzarse.
     *
     * @return list<callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                collect($this->input('rules'))
                    ->map(fn (array $rule, int $index) => $rule + ['index' => $index])
                    ->groupBy('weekday')
                    ->each(function ($day) use ($validator) {
                        $sorted = $day->sortBy('start_time')->values();

                        for ($i = 1; $i < $sorted->count(); $i++) {
                            if ($sorted[$i]['start_time'] < $sorted[$i - 1]['end_time']) {
                                $validator->errors()->add(
                                    "rules.{$sorted[$i]['index']}.start_time",
                                    'Esta franja se cruza con otra del mismo día.',
                                );
                            }
                        }
                    });
            },
        ];
    }
}
