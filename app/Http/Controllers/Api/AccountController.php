<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;

/**
 * HU019, HU022, HU031: gestión de la cuenta, seguridad y ubicación del usuario autenticado.
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
     * HU019: cambiar contraseña con la contraseña actual.
     */
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->password = $request->validated('password');
        $user->save();

        return $this->ok(null, 'Contraseña actualizada correctamente.');
    }
}
