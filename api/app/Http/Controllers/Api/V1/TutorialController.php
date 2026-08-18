<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TutorialController extends Controller
{
    private const KNOWN_STEPS = [
        'create-category',
        'record-transaction',
        'view-summary',
    ];

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reset'     => ['sometimes', 'boolean'],
            'step_id'   => ['sometimes', 'string', Rule::in(self::KNOWN_STEPS)],
            'completed' => ['sometimes', 'boolean'],
            'dismissed' => ['sometimes', 'boolean'],
        ]);

        $user = $request->user();

        if (! empty($validated['reset'])) {
            $user->update(['tutorial_progress' => ['completed_steps' => [], 'dismissed' => false]]);

            return ApiResponse::data(new UserResource($user->fresh()));
        }

        $progress = $user->tutorial_progress ?? [];

        if (isset($validated['step_id']) && isset($validated['completed'])) {
            $completedSteps = $progress['completed_steps'] ?? [];

            if ($validated['completed'] && ! in_array($validated['step_id'], $completedSteps)) {
                $completedSteps[] = $validated['step_id'];
            } elseif (! $validated['completed']) {
                $completedSteps = array_values(array_filter($completedSteps, fn ($s) => $s !== $validated['step_id']));
            }

            $progress['completed_steps'] = $completedSteps;
        }

        if (isset($validated['dismissed'])) {
            $progress['dismissed'] = $validated['dismissed'];
        }

        $user->update(['tutorial_progress' => $progress]);

        return ApiResponse::data(new UserResource($user->fresh()));
    }
}
