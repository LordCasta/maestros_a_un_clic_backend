<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Events\MessageSent;
use App\Models\Booking;
use App\Models\Message;
use App\Models\User;
use App\Notifications\MessageReceived;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;

/**
 * HU011, HU042: chat de una reserva entre el cliente y el profesional.
 * Los adjuntos van al disco privado y se entregan con URLs firmadas que vencen.
 */
class ChatService
{
    /** Estados en los que se puede escribir: hay una solicitud enviada o una reserva activa. */
    public const OPEN_STATUSES = [
        BookingStatus::Pending,
        BookingStatus::Accepted,
        BookingStatus::Confirmed,
        BookingStatus::InProgress,
    ];

    public function __construct(private readonly UploadService $uploads) {}

    public function isOpen(Booking $booking): bool
    {
        return in_array($booking->status, self::OPEN_STATUSES, true);
    }

    public function send(Booking $booking, User $sender, ?string $body, ?UploadedFile $attachment): Message
    {
        abort_unless(
            $this->isOpen($booking),
            409,
            'El chat se cierra cuando la reserva termina, se rechaza o se cancela.',
        );

        $message = $booking->messages()->create([
            'sender_id' => $sender->id,
            'body' => $body !== null && trim($body) !== '' ? trim($body) : null,
            'attachment_path' => $attachment ? $this->uploads->storePrivate($attachment, "chat/{$booking->id}") : null,
        ]);
        $message->setRelation('sender', $sender);

        MessageSent::dispatch($message);

        $recipient = $sender->is($booking->client) ? $booking->professional : $booking->client;
        $recipient->notify(new MessageReceived($message));

        return $message;
    }

    /**
     * Marca como leídos los mensajes que la otra parte le envió a $reader.
     */
    public function markAsRead(Booking $booking, User $reader): int
    {
        return $booking->messages()
            ->where('sender_id', '!=', $reader->id)
            ->whereNull('read_at')
            ->update(['read_at' => Carbon::now()]);
    }
}
