<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteAccountRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\UploadAvatarRequest;
use App\Http\Resources\UserResource;
use App\Services\AccountService;
use Illuminate\Http\JsonResponse;

/**
 * HU019, HU022, HU023, HU031, HU032: gestión de la cuenta, seguridad y ubicación del usuario autenticado.
 */
class AccountController extends Controller
{
    public function __construct(private readonly AccountService $account) {}

    /**
     * HU022, HU031: actualizar datos del usuario autenticado (ubicación manual, nombre, teléfono).
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->account->update($request->user(), $request->validated());

        return $this->ok(new UserResource($user), 'Perfil y ubicación actualizados correctamente.');
    }

    /**
     * HU032: subir o cambiar la foto de perfil del usuario.
     */
    public function uploadAvatar(UploadAvatarRequest $request): JsonResponse
    {
        $user = $this->account->updateAvatar($request->user(), $request->file('avatar'));

        return $this->ok(new UserResource($user), 'Foto de perfil actualizada correctamente.');
    }

    /**
     * HU019: cambiar contraseña con la contraseña actual.
     */
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $this->account->updatePassword($request->user(), $request->validated('password'));

        return $this->ok(null, 'Contraseña actualizada correctamente.');
    }

    /**
     * HU023: eliminación de la cuenta con anonimización, verificando la contraseña y sin reservas activas.
     */
    public function destroy(DeleteAccountRequest $request): JsonResponse
    {
        $this->account->delete($request->user());

        return $this->ok(null, 'Tu cuenta ha sido eliminada correctamente.');
    }
}
