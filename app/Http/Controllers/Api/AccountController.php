<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePasswordRequest;
use Illuminate\Http\JsonResponse;

/**
 * HU019: gestión de la cuenta y seguridad del usuario autenticado.
 */
class AccountController extends Controller
{
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
