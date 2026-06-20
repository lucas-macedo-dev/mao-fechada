<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateUserPreferencesRequest;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'locale' => ['sometimes', 'required', 'in:pt-BR,en'],
        ]);

        $user = User::query()->create($validated);
        $token = $user->createToken('web')->plainTextToken;

        return ApiResponse::data([
            'token' => $token,
            'user' => $user,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        return ApiResponse::data([
            'token' => $user->createToken('web')->plainTextToken,
            'user' => $user,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::data($request->user());
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return ApiResponse::data([
            'message' => __('messages.logged_out'),
        ]);
    }

    public function updatePreferences(UpdateUserPreferencesRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $request->user()->update($validated);

        return ApiResponse::data($request->user()->fresh());
    }
}
