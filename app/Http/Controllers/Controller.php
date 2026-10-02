<?php

namespace App\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;

abstract class Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected function ok(mixed $data = null, ?string $message = null): JsonResponse
    {
        return ApiResponse::success($data, $message);
    }

    protected function created(mixed $data = null, ?string $message = null): JsonResponse
    {
        return ApiResponse::success($data, $message, 201);
    }

    protected function paginated(ResourceCollection $collection, LengthAwarePaginator $paginator): JsonResponse
    {
        return ApiResponse::paginated($collection, $paginator);
    }
}
