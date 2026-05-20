<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Specialty extends Model
{
    use HasFactory;

    protected $fillable = ['name','slug','icon'];

    public function professionals(): BelongsToMany
    {
        return $this->belongsToMany(ProfessionalProfile::class, 'professional_specialty');
    }
}

