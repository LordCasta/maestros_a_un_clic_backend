<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteAccountRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\UploadAvatarRequest;
use App\Http\Resources\UserResource;
use App\Models\Booking;
use App\Services\UploadService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

/**
 * HU019, HU022, HU023, HU031, HU032: gestión de la cuenta, seguridad y ubicación del usuario autenticado.
 */
class AccountController extends Controller
{
    /**
     * HU022, HU031: actualizar datos del usuario autenticado (ubicación manual, nombre, teléfono).
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->fill($request->validated());
        $user->save();
        $user->load('commune');

        return $this->ok(new UserResource($user), 'Perfil y ubicación actualizados correctamente.');
    }

    /**
     * HU032: subir o cambiar la foto de perfil del usuario.
     */
    public function uploadAvatar(UploadAvatarRequest $request, UploadService $uploadService): JsonResponse
    {
        $user = $request->user();

        if ($user->avatar_path) {
            $uploadService->deletePublic($user->avatar_path);
        }

        $path = $uploadService->storePublic($request->file('avatar'), 'avatars');
        $user->avatar_path = $path;
        $user->save();
        $user->load('commune');

        return $this->ok(new UserResource($user), 'Foto de perfil actualizada correctamente.');
    }

    /**
     * HU019: cambiar contraseña con la contraseña actual.
     */
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->password = $request->validated('password');
        $user->save();

        return $this->ok(null, 'Contraseña actualizada correctamente.');
    }

    /**
     * HU023: eliminación lógica de cuenta con verificación de contraseña y sin reservas activas.
     */
    public function destroy(DeleteAccountRequest $request): JsonResponse
    {
        $user = $request->user();

        $hasActiveBookings = Booking::query()
            ->where(fn (Builder $q) => $q->where('client_id', $user->id)->orWhere('professional_id', $user->id))
            ->occupyingSchedule()
            ->exists();

        if ($hasActiveBookings) {
            abort(422, 'No puedes eliminar tu cuenta mientras tengas reservas activas o pendientes.');
        }

        $user->tokens()->delete();
        $user->delete();

        return $this->ok(null, 'Tu cuenta ha sido eliminada correctamente.');
    }
}
