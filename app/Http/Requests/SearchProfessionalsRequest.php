<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchProfessionalsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'commune_id' => ['nullable', 'integer', 'exists:communes,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'integer', 'exists:categories,id'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0', Rule::when($this->filled('min_price'), ['gte:min_price'])],
            'sort' => ['nullable', 'in:rating,price_asc,price_desc,distance'],
            'lat' => ['nullable', 'numeric', 'between:-90,90', 'required_with:lng', 'required_if:sort,distance'],
            'lng' => ['nullable', 'numeric', 'between:-180,180', 'required_with:lat', 'required_if:sort,distance'],
            'available_now' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
