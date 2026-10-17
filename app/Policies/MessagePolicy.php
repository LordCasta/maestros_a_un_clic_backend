<?php

namespace App\Policies;

use App\Models\Message;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * HU043: solo el autor elimina su mensaje.
 */
class MessagePolicy
{
    public function delete(User $user, Message $message): Response
    {
        return $message->sender_id === $user->id
            ? Response::allow()
            : Response::deny('Solo puedes eliminar tus propios mensajes.');
    }
}
