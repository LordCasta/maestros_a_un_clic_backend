<?php

namespace App\Services;

use App\Models\Commune;
use App\Models\ProfessionalProfile;
use App\Models\User;
use App\Support\ProfessionalSearchResult;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Búsqueda pública de profesionales (HU010, HU033, HU039, HU046).
 * Búsqueda por geolocalización, radio de acción y comunas aledañas (HU002, HU040).
 */
class ProfessionalSearchService
{
    /**
     * @param  array{q?: string, commune_id?: int, category_id?: int, min_price?: float, max_price?: float, sort?: string, lat?: float, lng?: float}  $filters
     */
    public function search(array $filters, int $perPage): ProfessionalSearchResult
    {
        $hasCoords = isset($filters['lat'], $filters['lng']);
        $lat = $hasCoords ? (float) $filters['lat'] : null;
        $lng = $hasCoords ? (float) $filters['lng'] : null;

        $paginator = $this->buildQuery($filters, $lat, $lng)->paginate($perPage);

        // Si se buscó por una comuna específica y no hay resultados, sugerir de comunas aledañas (HU002).
        if ($paginator->total() === 0 && ! empty($filters['commune_id'])) {
            $requestedCommune = Commune::find($filters['commune_id']);
            $neighborIds = $requestedCommune?->neighbors()->pluck('communes.id')->all() ?? [];

            if (! empty($neighborIds)) {
                $suggestedFilters = $filters;
                unset($suggestedFilters['commune_id']);
                $suggestedFilters['neighbor_commune_ids'] = $neighborIds;

                // Si no se pasaron coordenadas, usar el centroide de la comuna pedida para ordenar por proximidad
                $suggestLat = $lat ?? ($requestedCommune ? (float) $requestedCommune->latitude : null);
                $suggestLng = $lng ?? ($requestedCommune ? (float) $requestedCommune->longitude : null);

                $suggestedPaginator = $this->buildQuery($suggestedFilters, $suggestLat, $suggestLng, isSuggestion: true)->paginate($perPage);

                if ($suggestedPaginator->total() > 0) {
                    $suggestedPaginator->getCollection()->transform(function ($professional) {
                        $professional->is_suggested = true;

                        return $professional;
                    });

                    return new ProfessionalSearchResult($suggestedPaginator, suggestedFromNeighbors: true);
                }
            }
        }

        return new ProfessionalSearchResult($paginator);
    }

    private function buildQuery(array $filters, ?float $lat, ?float $lng, bool $isSuggestion = false): Builder
    {
        $hasCoords = $lat !== null && $lng !== null;

        $query = User::publiclyListed()
            ->with(['commune', 'professionalProfile.categories', 'professionalProfile.availabilityRules'])
            ->when($filters['q'] ?? null, fn (Builder $q, string $term) => $q->where('name', 'like', "%{$term}%"))
            ->when($filters['commune_id'] ?? null, fn (Builder $q, int $id) => $q->where('users.commune_id', $id))
            ->when($filters['neighbor_commune_ids'] ?? null, fn (Builder $q, array $ids) => $q->whereIn('users.commune_id', $ids))
            ->when($filters['category_id'] ?? null, fn (Builder $q, int $id) => $q->whereHas(
                'professionalProfile',
                fn (Builder $profile) => $profile->where(fn (Builder $match) => $match
                    ->whereHas('categories', fn (Builder $c) => $c->whereKey($id))
                    ->orWhereHas('services', fn (Builder $s) => $s->active()->where('category_id', $id))),
            ))
            ->when($filters['subcategory_id'] ?? null, fn (Builder $q, int $id) => $q->whereHas(
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
            ))
            ->when(! empty($filters['available_now']), function (Builder $q) {
                $now = Carbon::now(config('app.timezone'));
                $weekday = $now->dayOfWeek;
                $currentTime = $now->format('H:i:s');

                $q->whereHas('professionalProfile', function (Builder $profile) use ($weekday, $currentTime, $now) {
                    $profile->whereHas('availabilityRules', function (Builder $rule) use ($weekday, $currentTime) {
                        $rule->where('weekday', $weekday)
                            ->where('start_time', '<=', $currentTime)
                            ->where('end_time', '>=', $currentTime);
                    })
                        ->whereDoesntHave('availabilityBlocks', function (Builder $block) use ($now) {
                            $block->where('starts_at', '<=', $now)
                                ->where('ends_at', '>=', $now);
                        });
                })
                    ->whereDoesntHave('bookingsAsProfessional', function (Builder $booking) use ($now) {
                        $booking->occupyingSchedule()
                            ->where('starts_at', '<=', $now)
                            ->where('ends_at', '>=', $now);
                    });
            });

        if ($hasCoords) {
            $query->leftJoin('communes', 'users.commune_id', '=', 'communes.id')
                ->leftJoin('professional_profiles', 'users.id', '=', 'professional_profiles.user_id')
                ->select('users.*');

            $distanceSql = '6371 * acos(least(1.0, greatest(-1.0, cos(radians(?)) * cos(radians(COALESCE(users.latitude, communes.latitude))) * cos(radians(COALESCE(users.longitude, communes.longitude)) - radians(?)) + sin(radians(?)) * sin(radians(COALESCE(users.latitude, communes.latitude))))))';

            $query->selectRaw("{$distanceSql} as distance_km", [$lat, $lng, $lat]);

            // Filtrar profesionales dentro del radio de acción configurado (HU002).
            if (! $isSuggestion && empty($filters['neighbor_commune_ids'])) {
                $query->whereRaw("{$distanceSql} <= COALESCE(professional_profiles.service_radius_km, 10)", [$lat, $lng, $lat]);
            }
        }

        $this->applySort($query, $filters['sort'] ?? ($hasCoords ? 'distance' : 'rating'), $hasCoords);

        return $query;
    }

    private function applySort(Builder $query, string $sort, bool $hasCoords): void
    {
        match ($sort) {
            'rating' => $query->orderByDesc('rating_avg')->orderByDesc('rating_count'),
            'distance' => $hasCoords
                ? $query->orderBy('distance_km', 'asc')
                : $query->orderByDesc('rating_avg')->orderByDesc('rating_count'),
            'price_asc', 'price_desc' => $query
                ->orderBy(
                    ProfessionalProfile::select('hourly_rate')->whereColumn('professional_profiles.user_id', 'users.id'),
                    $sort === 'price_asc' ? 'asc' : 'desc',
                ),
            default => $hasCoords
                ? $query->orderBy('distance_km', 'asc')
                : $query->orderByDesc('rating_avg')->orderByDesc('rating_count'),
        };
    }
}
