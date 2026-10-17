<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * HU009: datos del perfil profesional. Todos opcionales: solo cambia lo que se envía.
 */
class UpdateProfessionalProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'experience_years' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:70'],
            'hourly_rate' => ['sometimes', 'required', 'numeric', 'gt:0', 'max:10000000'],
            'service_radius_km' => ['sometimes', 'required', 'integer', 'min:1', 'max:100'],
            // Ubicación: actualiza la zona en la que aparece en las búsquedas (HU002).
            'commune_id' => ['sometimes', 'nullable', 'integer', 'exists:communes,id'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            // Sin `sometimes`: si llega una coordenada, la otra es obligatoria.
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
        ];
    }
}
