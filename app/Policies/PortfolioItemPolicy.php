<?php

namespace App\Policies;

use App\Models\PortfolioItem;
use App\Models\User;

/**
 * HU009: cada profesional administra solo las fotos de su portafolio.
 */
class PortfolioItemPolicy
{
    public function delete(User $user, PortfolioItem $item): bool
    {
        return $item->professionalProfile->user_id === $user->id;
    }
}
