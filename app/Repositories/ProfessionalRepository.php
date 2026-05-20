<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\ProfessionalProfile;
use Illuminate\Database\Eloquent\Builder;

class ProfessionalRepository
{
    public function query(): Builder
    {
        return User::query()->where('role', 'professional')->with(['professionalProfile.specialties', 'professionalProfile.portfolioItems']);
    }

    /**
     * Apply simple filters and return paginated results
     */
    public function paginate(array $filters = [], int $perPage = 15)
    {
        $q = $this->query();

        if (!empty($filters['commune'])) {
            $q->where('commune', $filters['commune']);
        }

        if (!empty($filters['specialty_id'])) {
            $q->whereHas('professionalProfile.specialties', function (Builder $sub) use ($filters) {
                $sub->where('specialties.id', $filters['specialty_id']);
            });
        }

        if (!empty($filters['min_price'])) {
            $q->whereHas('professionalProfile', function (Builder $sub) use ($filters) {
                $sub->where('hourly_rate', '>=', $filters['min_price']);
            });
        }

        if (!empty($filters['max_price'])) {
            $q->whereHas('professionalProfile', function (Builder $sub) use ($filters) {
                $sub->where('hourly_rate', '<=', $filters['max_price']);
            });
        }

        return $q->paginate($perPage);
    }
}

