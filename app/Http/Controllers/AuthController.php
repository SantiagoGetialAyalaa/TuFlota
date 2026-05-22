<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\UseCases\LoginUserUseCase;
use App\Application\UseCases\RegisterUserUseCase;
use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\RegisterUserRequest;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(
        private readonly RegisterUserUseCase $registerUserUseCase,
        private readonly LoginUserUseCase $loginUserUseCase,
    ) {
    }

    public function register(RegisterUserRequest $request): JsonResponse
    {
        return response()->json(
            $this->registerUserUseCase->execute($request->validated()),
            201,
        );
    }

    public function login(LoginUserRequest $request): JsonResponse
    {
        $payload = $request->validated();

        return response()->json(
            $this->loginUserUseCase->execute($payload['email'], $payload['password']),
        );
    }
}
