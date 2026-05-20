<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->role === 'client';
    }

    public function rules(): array
    {
        return [
            'professional_id' => ['required','integer','exists:users,id'],
            'service_description' => ['nullable','string','max:2000'],
            'scheduled_date' => ['required','date','after:now'],
            'total' => ['nullable','numeric'],
        ];
    }
}

