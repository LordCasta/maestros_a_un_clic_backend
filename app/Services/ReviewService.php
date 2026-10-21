<?php

namespace App\Services;

use App\Enums\AlertType;
use App\Enums\BookingStatus;
use App\Models\AdminAlert;
use App\Models\Booking;
use App\Models\Review;
use App\Models\User;
use App\Notifications\ReviewReceived;
use Illuminate\Support\Facades\DB;

class ReviewService
{
    /**
     * HU005, HU045: calificación recíproca tras finalizar el servicio y alerta por baja calificación.
     *
     * @param  array{rating: int, comment?: ?string}  $data
     */
    public function create(Booking $booking, User $reviewer, array $data): Review
    {
        if ($booking->status !== BookingStatus::Completed) {
            abort(409, 'Solo se pueden calificar reservas completadas.');
        }

        if ($booking->reviews()->where('reviewer_id', $reviewer->id)->exists()) {
            abort(409, 'Ya calificaste esta reserva.');
        }

        $revieweeId = $reviewer->id === $booking->client_id
            ? $booking->professional_id
            : $booking->client_id;

        return DB::transaction(function () use ($booking, $reviewer, $revieweeId, $data) {
            $review = Review::create([
                'booking_id' => $booking->id,
                'reviewer_id' => $reviewer->id,
                'reviewee_id' => $revieweeId,
                'rating' => $data['rating'],
                'comment' => $data['comment'] ?? null,
            ]);

            $this->recalculateUserRating($revieweeId);

            $reviewee = User::find($revieweeId);
            $reviewee?->notify(new ReviewReceived($review));

            // HU045: alerta a administradores si la calificación es baja (<= 2) o promedio < 3.0
            if ($data['rating'] <= 2 || ($reviewee && $reviewee->rating_count >= 3 && $reviewee->rating_avg < 3.0)) {
                AdminAlert::create([
                    'user_id' => $revieweeId,
                    'type' => AlertType::LowRating,
                    'data' => [
                        'review_id' => $review->id,
                        'rating' => $data['rating'],
                        'comment' => $data['comment'] ?? null,
                        'current_avg' => $reviewee?->rating_avg,
                        'rating_count' => $reviewee?->rating_count,
                    ],
                ]);
            }

            return $review->load(['reviewer', 'reviewee']);
        });
    }

    public function recalculateUserRating(int $userId): void
    {
        $stats = Review::query()
            ->where('reviewee_id', $userId)
            ->selectRaw('COUNT(*) as total, AVG(rating) as avg_rating')
            ->first();

        User::where('id', $userId)->update([
            'rating_avg' => round((float) ($stats->avg_rating ?? 0), 2),
            'rating_count' => (int) ($stats->total ?? 0),
        ]);
    }
}
