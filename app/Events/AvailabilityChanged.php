<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * HU003, HU025: cambió la agenda de un profesional (horario, bloqueos o una reserva que
 * ocupa o libera tiempo). Los clientes que miran sus horas libres las vuelven a pedir.
 * Canal público: no lleva datos personales, solo el id del profesional.
 */
class AvailabilityChanged implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public int $professionalId) {}

    public function broadcastOn(): Channel
    {
        return new Channel("professional.{$this->professionalId}.availability");
    }

    public function broadcastAs(): string
    {
        return 'availability.changed';
    }

    /**
     * @return array{professional_id: int}
     */
    public function broadcastWith(): array
    {
        return ['professional_id' => $this->professionalId];
    }
}
