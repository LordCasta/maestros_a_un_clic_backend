<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelBookingRequest;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    private const RELATIONS = ['service.category', 'client', 'professional', 'commune'];

    public function __construct(private readonly BookingService $bookings) {}

    /**
     * Reservas del usuario autenticado según su rol (HU016, HU017). El admin ve todas.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $paginator = Booking::query()
            ->when($user->isClient(), fn ($q) => $q->where('client_id', $user->id))
            ->when($user->isProfessional(), fn ($q) => $q->where('professional_id', $user->id))
            ->with(self::RELATIONS)
            ->orderByDesc('starts_at')
            ->paginate((int) $request->input('per_page', 15));

        return $this->paginated(BookingResource::collection($paginator), $paginator);
    }

    public function store(StoreBookingRequest $request): JsonResponse
    {
        $this->authorize('create', Booking::class);

        $booking = $this->bookings->create($request->user(), $request->validated());

        return $this->created(new BookingResource($booking->load(self::RELATIONS)), 'Solicitud de reserva enviada.');
    }

    public function show(Booking $booking): JsonResponse
    {
        $this->authorize('view', $booking);

        return $this->ok(new BookingResource($booking->load(self::RELATIONS)));
    }

    public function cancel(CancelBookingRequest $request, Booking $booking): JsonResponse
    {
        $this->authorize('cancel', $booking);

        $booking = $this->bookings->cancel($booking, $request->user(), $request->validated('reason'));

        return $this->ok(new BookingResource($booking->load(self::RELATIONS)), 'Reserva cancelada.');
    }
}
