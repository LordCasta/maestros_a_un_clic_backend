<?php

namespace App\Http\Controllers\Api;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\CancelBookingRequest;
use App\Http\Requests\ListBookingsRequest;
use App\Http\Requests\RejectBookingRequest;
use App\Http\Requests\RescheduleBookingRequest;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Http\Resources\BookingStatusChangeResource;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    private const RELATIONS = ['service.category', 'client', 'professional', 'commune'];

    private const DETAIL_RELATIONS = ['service.category', 'client', 'professional', 'commune', 'reviews.reviewer', 'reviews.reviewee'];

    public function __construct(private readonly BookingService $bookings) {}

    /**
     * Reservas del usuario autenticado según su rol (HU016, HU017). El admin ve todas.
     * `?status=pending` da las solicitudes por responder del panel del profesional (HU004).
     */
    public function index(ListBookingsRequest $request): JsonResponse
    {
        $paginator = Booking::query()
            ->visibleTo($request->user())
            ->when($request->validated('status'), fn ($q, array $statuses) => $q->whereIn('status', $statuses))
            ->with(self::RELATIONS)
            ->orderByDesc('starts_at')
            ->paginate((int) $request->validated('per_page', 15));

        return $this->paginated(BookingResource::collection($paginator), $paginator);
    }

    /**
     * HU017: cuántas reservas tiene el usuario en cada estado (total de servicios realizados,
     * cancelados, próximos…). Mismo alcance que el listado.
     */
    public function summary(Request $request): JsonResponse
    {
        $counts = Booking::query()
            ->visibleTo($request->user())
            ->toBase()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $byStatus = collect(BookingStatus::cases())
            ->mapWithKeys(fn (BookingStatus $status) => [$status->value => (int) ($counts[$status->value] ?? 0)]);

        return $this->ok([
            'total' => $byStatus->sum(),
            'by_status' => $byStatus,
        ]);
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

        return $this->ok(new BookingResource($booking->load(self::DETAIL_RELATIONS)));
    }

    /**
     * HU026: historial de cambios de estado, del más antiguo al más reciente.
     */
    public function history(Booking $booking): JsonResponse
    {
        $this->authorize('view', $booking);

        $changes = $booking->statusChanges()->with('changedBy')->orderBy('created_at')->orderBy('id')->get();

        return $this->ok(BookingStatusChangeResource::collection($changes));
    }

    public function accept(Request $request, Booking $booking): JsonResponse
    {
        $this->authorize('respond', $booking);

        $booking = $this->bookings->accept($booking, $request->user());

        return $this->ok(new BookingResource($booking->load(self::RELATIONS)), 'Solicitud aceptada. El cliente debe confirmarla.');
    }

    public function reject(RejectBookingRequest $request, Booking $booking): JsonResponse
    {
        $this->authorize('respond', $booking);

        $booking = $this->bookings->reject($booking, $request->user(), $request->validated('reason'));

        return $this->ok(new BookingResource($booking->load(self::RELATIONS)), 'Solicitud rechazada.');
    }

    public function confirm(Request $request, Booking $booking): JsonResponse
    {
        $this->authorize('confirm', $booking);

        $booking = $this->bookings->confirm($booking, $request->user());

        return $this->ok(new BookingResource($booking->load(self::RELATIONS)), 'Reserva confirmada.');
    }

    public function start(Request $request, Booking $booking): JsonResponse
    {
        $this->authorize('work', $booking);

        $booking = $this->bookings->start($booking, $request->user());

        return $this->ok(new BookingResource($booking->load(self::RELATIONS)), 'Servicio iniciado.');
    }

    public function complete(Request $request, Booking $booking): JsonResponse
    {
        $this->authorize('work', $booking);

        $booking = $this->bookings->complete($booking, $request->user());

        return $this->ok(new BookingResource($booking->load(self::RELATIONS)), 'Servicio finalizado.');
    }

    public function reschedule(RescheduleBookingRequest $request, Booking $booking): JsonResponse
    {
        $this->authorize('reschedule', $booking);

        $booking = $this->bookings->reschedule($booking, $request->user(), $request->validated('starts_at'));

        return $this->ok(
            new BookingResource($booking->load(self::RELATIONS)),
            'Reserva reprogramada. El profesional debe aceptar el nuevo horario.',
        );
    }

    public function cancel(CancelBookingRequest $request, Booking $booking): JsonResponse
    {
        $this->authorize('cancel', $booking);

        $booking = $this->bookings->cancel($booking, $request->user(), $request->validated('reason'));

        return $this->ok(new BookingResource($booking->load(self::RELATIONS)), 'Reserva cancelada.');
    }
}
