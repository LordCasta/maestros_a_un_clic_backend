<?php

namespace App\Http\Controllers\Api;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\EarningsRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

/**
 * HU037: ingresos del profesional por los servicios finalizados en un rango de fechas.
 * El ingreso de cada servicio es su precio acordado (`agreed_price`) al reservar.
 */
class EarningsController extends Controller
{
    public function __invoke(EarningsRequest $request): JsonResponse
    {
        $timezone = config('app.timezone');
        $from = $request->filled('from')
            ? Carbon::createFromFormat('Y-m-d', $request->validated('from'), $timezone)->startOfDay()
            : Carbon::now($timezone)->startOfMonth();
        $to = $request->filled('to')
            ? Carbon::createFromFormat('Y-m-d', $request->validated('to'), $timezone)->endOfDay()
            : Carbon::now($timezone)->endOfDay();

        $bookings = Booking::where('professional_id', $request->user()->id)
            ->where('status', BookingStatus::Completed)
            ->whereBetween('completed_at', [$from, $to])
            ->with(['service.category', 'client', 'professional', 'commune'])
            ->orderByDesc('completed_at')
            ->get();

        $total = round((float) $bookings->sum('agreed_price'), 2);

        return $this->ok([
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'total' => $total,
            'count' => $bookings->count(),
            'average' => $bookings->isEmpty() ? 0 : round($total / $bookings->count(), 2),
            // Por día, solo los días con ingresos (para la gráfica).
            'by_day' => $bookings
                ->groupBy(fn (Booking $booking) => $booking->completed_at->copy()->setTimezone($timezone)->toDateString())
                ->map(fn ($day, string $date) => [
                    'date' => $date,
                    'total' => round((float) $day->sum('agreed_price'), 2),
                    'count' => $day->count(),
                ])
                ->sortKeys()
                ->values(),
            'bookings' => BookingResource::collection($bookings),
        ]);
    }
}
