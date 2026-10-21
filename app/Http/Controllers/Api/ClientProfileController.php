<?php

namespace App\Http\Controllers\Api;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Resources\ClientProfileResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class ClientProfileController extends Controller
{
    /**
     * HU044: vista del perfil del cliente por parte del profesional.
     */
    public function show(int $id): JsonResponse
    {
        $client = User::where('role', Role::Client)
            ->with('commune')
            ->findOrFail($id);

        return $this->ok(new ClientProfileResource($client));
    }
}
