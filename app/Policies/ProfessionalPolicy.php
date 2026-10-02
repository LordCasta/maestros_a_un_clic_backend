<?php

namespace App\Policies;

use App\Models\ProfessionalProfile;
use App\Models\User;

class ProfessionalPolicy
{
    public function update(User $user, ProfessionalProfile $profile): bool
    {
        return $user->id === $profile->user_id || $user->isAdmin();
    }
}
