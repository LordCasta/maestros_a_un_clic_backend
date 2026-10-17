<?php

namespace App\Http\Requests;

use App\Enums\PriceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * HU009, HU047: editar un servicio (solo los campos enviados). `is_active` lo activa o lo oculta.
 */
class UpdateProfessionalServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['sometimes', 'integer', Rule::exists('categories', 'id')->whereNotNull('parent_id')],
            'title' => ['sometimes', 'required', 'string', 'max:120'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'price_type' => ['sometimes', Rule::enum(PriceType::class)],
            'price' => ['sometimes', 'numeric', 'gt:0', 'max:100000000'],
            'estimated_duration_minutes' => ['sometimes', 'integer', 'min:15', 'max:720'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
