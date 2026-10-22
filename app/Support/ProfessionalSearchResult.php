<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Resultado de ProfessionalSearchService::search(). Si la comuna pedida no tenía profesionales,
 * los resultados son sugerencias de comunas aledañas (HU002) y $suggestedFromNeighbors es true.
 */
final readonly class ProfessionalSearchResult
{
    public function __construct(
        public LengthAwarePaginator $paginator,
        public bool $suggestedFromNeighbors = false,
    ) {}
}
