<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'reported_user_id' => ['required', 'integer', 'exists:users,id', 'different:reporter_id'],
            'booking_id' => ['nullable', 'integer', 'exists:bookings,id'],
            'reason' => ['required', 'string', 'min:3', 'max:100'],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reporter_id' => $this->user()?->id,
        ]);
    }
}
