<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function verify(Request $request, string $id, string $hash): JsonResponse
    {
        $user = \App\Models\User::findOrFail($id);

        if (!hash_equals($hash, sha1($user->getEmailForVerification()))) {
            return response()->json(['error' => ['type' => 'invalid_verification_link', 'message' => 'Invalid verification link.']], 400);
        }

        if (!$request->hasValidSignature()) {
            return response()->json(['error' => ['type' => 'verification_link_expired', 'message' => 'Verification link has expired.']], 400);
        }

        if ($user->hasVerifiedEmail()) {
            return ApiResponse::data(['message' => 'Email already verified.']);
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        return ApiResponse::data(['message' => 'Email verified successfully.']);
    }

    public function resend(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['error' => ['type' => 'already_verified', 'message' => 'Email is already verified.']], 422);
        }

        $user->sendEmailVerificationNotification();

        return ApiResponse::data(['message' => 'Verification email sent.']);
    }
}
