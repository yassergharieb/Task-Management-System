<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Services\AuthServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthServiceInterface $authService,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->authService->register($request->validated());

        return $this->successResponse(
            message: 'Registration successful',
            data: $user,
            status: Response::HTTP_CREATED,
            token: $user->token(),
        );
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = $this->authService->login($request->validated());

        return $this->successResponse(
            message: 'Login successful',
            data: $user,
            token: $user->token(),
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->successResponse(
            'Logout successful',
        );
    }
}
