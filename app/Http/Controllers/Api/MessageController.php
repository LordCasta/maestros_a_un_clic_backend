<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMessageRequest;
use App\Http\Resources\MessageResource;
use App\Models\Booking;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * HU011, HU042: chat de una reserva. Solo el cliente y el profesional de la reserva.
 */
class MessageController extends Controller
{
    /** Mensajes más recientes que se devuelven (del más antiguo al más nuevo). */
    private const HISTORY_LIMIT = 200;

    public function __construct(private readonly ChatService $chat) {}

    public function index(Booking $booking): JsonResponse
    {
        $this->authorize('chat', $booking);

        $messages = $booking->messages()
            ->with('sender')
            ->latest('id')
            ->limit(self::HISTORY_LIMIT)
            ->get()
            ->reverse()
            ->values();

        return $this->ok([
            // Se puede leer siempre; escribir solo mientras la reserva esté activa.
            'can_send' => $this->chat->isOpen($booking),
            'messages' => MessageResource::collection($messages),
        ]);
    }

    public function store(StoreMessageRequest $request, Booking $booking): JsonResponse
    {
        $this->authorize('chat', $booking);

        $message = $this->chat->send(
            $booking,
            $request->user(),
            $request->validated('body'),
            $request->file('attachment'),
        );

        return $this->created(new MessageResource($message), 'Mensaje enviado.');
    }

    public function read(Request $request, Booking $booking): JsonResponse
    {
        $this->authorize('chat', $booking);

        return $this->ok(['marked' => $this->chat->markAsRead($booking, $request->user())]);
    }
}
