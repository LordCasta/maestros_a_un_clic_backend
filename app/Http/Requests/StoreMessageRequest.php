<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * HU011, HU042: texto, imagen o ambos. La imagen: JPG, PNG o WEBP de hasta 5 MB.
 */
class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['nullable', 'string', 'max:2000', 'required_without:attachment'],
            'attachment' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required_without' => 'Escribe un mensaje o adjunta una imagen.',
        ];
    }
}
