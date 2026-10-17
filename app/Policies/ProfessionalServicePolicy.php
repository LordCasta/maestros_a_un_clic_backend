<?php

namespace App\Policies;

use App\Models\ProfessionalService;
use App\Models\User;

/**
 * HU009: cada profesional administra solo sus servicios.
 */
class ProfessionalServicePolicy
{
    public function update(User $user, ProfessionalService $service): bool
    {
        return $service->professionalProfile->user_id === $user->id;
    }

    public function delete(User $user, ProfessionalService $service): bool
    {
        return $this->update($user, $service);
    }
}
