<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterProfessionalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required','string','max:255'],
            'email' => ['required','email','max:255','unique:users,email'],
            'password' => ['required','string','min:8','confirmed'],
            'profile_photo' => ['nullable','image','mimes:jpg,jpeg,png','max:5120'],
            'specialties' => ['required','array','min:1'],
            'specialties.*' => ['integer','exists:specialties,id'],
            'hourly_rate' => ['nullable','numeric'],
            'experience_years' => ['required','integer','min:0'],
            'description' => ['required','string','min:50'],
            'certificates' => ['nullable','array'],
            'certificates.*' => ['file','mimes:pdf,jpg,jpeg,png','max:10240'],
            'portfolio_images' => ['nullable','array'],
            'portfolio_images.*' => ['image','mimes:jpg,jpeg,png','max:5120'],
            'commune' => ['nullable','string','max:255'],
            'latitude' => ['nullable','numeric'],
            'longitude' => ['nullable','numeric'],
            'phone' => ['nullable','string','max:30'],
        ];
    }
}

