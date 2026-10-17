<?php

namespace App\Http\Requests;

use App\Enums\PriceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * HU009, HU047: servicio nuevo con su precio y duración estimada. La categoría es una
 * subcategoría del catálogo; que pertenezca a sus especialidades lo revisa el servicio.
 */
class StoreProfessionalServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->whereNotNull('parent_id')],
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price_type' => ['required', Rule::enum(PriceType::class)],
            'price' => ['required', 'numeric', 'gt:0', 'max:100000000'],
            'estimated_duration_minutes' => ['required', 'integer', 'min:15', 'max:720'],
        ];
    }
}
