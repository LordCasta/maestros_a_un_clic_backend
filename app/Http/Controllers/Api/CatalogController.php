<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\CommuneResource;
use App\Models\Category;
use App\Models\Commune;
use Illuminate\Http\JsonResponse;

/**
 * Catálogos públicos que usan formularios y filtros.
 */
class CatalogController extends Controller
{
    public function communes(): JsonResponse
    {
        return $this->ok(CommuneResource::collection(Commune::orderBy('id')->get()));
    }

    /**
     * Categorías raíz con sus subcategorías.
     */
    public function categories(): JsonResponse
    {
        return $this->ok(CategoryResource::collection(
            Category::roots()->with('children')->orderBy('name')->get(),
        ));
    }
}
