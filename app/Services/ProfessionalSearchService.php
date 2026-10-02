<?php

namespace App\Services;

use App\Models\ProfessionalProfile;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Búsqueda pública de profesionales (HU010, HU033, HU039, HU046).
 * El módulo de búsqueda agrega distancia (HU040) y "disponible ahora" (HU030).
 */
class ProfessionalSearchService
{
    /**
     * @param  array{q?: string, commune_id?: int, category_id?: int, min_price?: float, max_price?: float, sort?: string}  $filters
     */
    public function search(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = User::publiclyListed()
            ->with(['commune', 'professionalProfile.categories'])
            ->when($filters['q'] ?? null, fn (Builder $q, string $term) => $q->where('name', 'like', "%{$term}%"))
            ->when($filters['commune_id'] ?? null, fn (Builder $q, int $id) => $q->where('commune_id', $id))
            // La categoría puede ser raíz (especialidad del perfil) o subcategoría (de un servicio activo).
            ->when($filters['category_id'] ?? null, fn (Builder $q, int $id) => $q->whereHas(
                'professionalProfile',
                fn (Builder $profile) => $profile->where(fn (Builder $match) => $match
                    ->whereHas('categories', fn (Builder $c) => $c->whereKey($id))
                    ->orWhereHas('services', fn (Builder $s) => $s->active()->where('category_id', $id))),
            ))
            ->when($filters['min_price'] ?? null, fn (Builder $q, float $min) => $q->whereHas(
                'professionalProfile', fn (Builder $p) => $p->where('hourly_rate', '>=', $min),
            ))
            ->when($filters['max_price'] ?? null, fn (Builder $q, float $max) => $q->whereHas(
                'professionalProfile', fn (Builder $p) => $p->where('hourly_rate', '<=', $max),
            ));

        $this->applySort($query, $filters['sort'] ?? 'rating');

        return $query->paginate($perPage);
    }

    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'price_asc', 'price_desc' => $query
                ->orderBy(
                    ProfessionalProfile::select('hourly_rate')->whereColumn('professional_profiles.user_id', 'users.id'),
                    $sort === 'price_asc' ? 'asc' : 'desc',
                ),
            default => $query->orderByDesc('rating_avg')->orderByDesc('rating_count'),
        };
    }
}
