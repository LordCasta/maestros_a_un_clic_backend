<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterClientRequest;
use App\Http\Requests\RegisterProfessionalRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Models\User;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function registerClient(RegisterClientRequest $request)
    {
        $data = $request->validated();
        // include uploaded files
        $data['selfie'] = $request->file('selfie');
        $data['document'] = $request->file('document');

        $user = $this->authService->registerClient($data);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Usuario registrado',
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
            ],
        ], 201);
    }

    public function registerProfessional(RegisterProfessionalRequest $request)
    {
        $data = $request->validated();
        $data['profile_photo'] = $request->file('profile_photo');
        $data['certificates'] = $request->file('certificates') ?? [];
        $data['portfolio_images'] = $request->file('portfolio_images') ?? [];

        $user = $this->authService->registerProfessional($data);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Profesional registrado',
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
            ],
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        if (!auth()->attempt($credentials)) {
            return response()->json(['success' => false, 'message' => 'Credenciales incorrectas'], 401);
        }

        /** @var User $user */
        $user = auth()->user();
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Autenticado',
            'data' => [
                'token' => $token,
                'user' => new UserResource($user),
                'role' => $user->role,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $user->currentAccessToken()?->delete();
        }

        return response()->json(['success' => true, 'message' => 'Sesión cerrada']);
    }

    public function me(Request $request)
    {
        return response()->json(['success' => true, 'data' => new UserResource($request->user())]);
    }
}

