<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterClientRequest extends FormRequest
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
            'selfie' => ['required','image','mimes:jpg,jpeg,png','max:5120'],
            'document' => ['required','file','mimes:pdf,jpg,jpeg,png','max:10240'],
            'commune' => ['nullable','string','max:255'],
            'latitude' => ['nullable','numeric'],
            'longitude' => ['nullable','numeric'],
            'phone' => ['nullable','string','max:30'],
        ];
    }
}

