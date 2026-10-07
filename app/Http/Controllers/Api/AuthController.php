<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterClientRequest;
use App\Http\Requests\RegisterProfessionalRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $auth) {}

    public function registerClient(RegisterClientRequest $request): JsonResponse
    {
        $user = $this->auth->registerClient($request->validated());

        return $this->created($this->session($user), 'Cuenta creada.');
    }

    public function registerProfessional(RegisterProfessionalRequest $request): JsonResponse
    {
        $user = $this->auth->registerProfessional($request->validated());

        return $this->created($this->session($user), 'Cuenta creada.');
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $session = $this->auth->login($request->validated());

        abort_if($session === null, 401, 'Credenciales incorrectas.');

        return $this->ok([
            'user' => new UserResource($session['user']->load('commune')),
            'token' => $session['token'],
        ], 'Sesión iniciada.');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->ok(message: 'Sesión cerrada.');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->ok(new UserResource($request->user()->load('commune')));
    }

    /**
     * @return array{user: UserResource, token: string}
     */
    private function session(User $user): array
    {
        return [
            'user' => new UserResource($user->load('commune')),
            'token' => $user->createToken('api-token')->plainTextToken,
        ];
    }
}
