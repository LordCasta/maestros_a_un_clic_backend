<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListNotificationsRequest;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * HU018: notificaciones del usuario autenticado (solicitudes, respuestas, cambios de estado,
 * mensajes del chat…). Se crean en app/Notifications con el canal `database`.
 */
class NotificationController extends Controller
{
    public function index(ListNotificationsRequest $request): JsonResponse
    {
        $paginator = $request->user()->notifications()
            ->when($request->boolean('unread'), fn ($q) => $q->whereNull('read_at'))
            ->paginate((int) $request->validated('per_page', 20));

        return $this->paginated(NotificationResource::collection($paginator), $paginator);
    }

    public function read(Request $request, string $id): JsonResponse
    {
        // Solo entre las propias: la de otro usuario responde 404.
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return $this->ok(new NotificationResource($notification));
    }

    public function readAll(Request $request): JsonResponse
    {
        $marked = $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return $this->ok(['marked' => $marked], 'Notificaciones marcadas como leídas.');
    }
}
