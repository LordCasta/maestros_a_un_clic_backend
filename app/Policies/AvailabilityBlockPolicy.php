<?php

namespace App\Policies;

use App\Models\AvailabilityBlock;
use App\Models\User;

/**
 * HU003: cada profesional administra solo sus bloqueos.
 */
class AvailabilityBlockPolicy
{
    public function delete(User $user, AvailabilityBlock $block): bool
    {
        return $block->professionalProfile->user_id === $user->id;
    }
}
