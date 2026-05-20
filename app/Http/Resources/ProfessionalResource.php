<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProfessionalResource extends JsonResource
{
    public function toArray($request): array
    {
        $profile = $this->professionalProfile;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar ? asset('storage/' . $this->avatar) : null,
            'commune' => $this->commune,
            'description' => $profile?->description,
            'experience_years' => $profile?->experience_years,
            'hourly_rate' => $profile?->hourly_rate,
            'is_verified' => (bool) ($profile?->is_verified ?? false),
            'specialties' => $profile?->specialties?->map(function($s){ return ['id'=>$s->id,'name'=>$s->name,'slug'=>$s->slug]; }),
            'portfolio' => $profile?->portfolioItems?->map(function($p){ return ['image' => asset('storage/' . $p->image_path),'description'=>$p->description]; }),
        ];
    }
}

