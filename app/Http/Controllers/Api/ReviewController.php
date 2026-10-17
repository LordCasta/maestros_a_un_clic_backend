<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Booking;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;

class ReviewController extends Controller
{
    public function __construct(private readonly ReviewService $reviews) {}

    /**
     * HU005: calificar la experiencia con la otra parte tras finalizar el servicio.
     */
    public function store(StoreReviewRequest $request, Booking $booking): JsonResponse
    {
        $this->authorize('review', $booking);

        $review = $this->reviews->create($booking, $request->user(), $request->validated());

        return $this->created(new ReviewResource($review), 'Calificación enviada correctamente.');
    }
}
