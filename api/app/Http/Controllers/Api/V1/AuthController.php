<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateProfileRequest;
use App\Http\Requests\Api\V1\UpdateUserPreferencesRequest;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'regex:/[0-9\W]/'],
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

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        return $this->updateAuthenticatedUser($request->validated(), $request);
    }

    public function updatePreferences(UpdateUserPreferencesRequest $request): JsonResponse
    {
        return $this->updateAuthenticatedUser($request->validated(), $request);
    }

    private function updateAuthenticatedUser(array $validated, Request $request): JsonResponse
    {
        $user = $request->user();

        if ($request->boolean('remove_profile_photo') && $user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
            $validated['profile_photo_path'] = null;
        }

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }

            $validated['profile_photo_path'] = $request->file('profile_photo')->store('profile-photos', 'public');
        }

        unset($validated['remove_profile_photo']);

        $user->update($validated);

        return ApiResponse::data($user->fresh());
    }
}
