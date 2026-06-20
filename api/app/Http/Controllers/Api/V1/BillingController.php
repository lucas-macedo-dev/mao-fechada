<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PlanLimit;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function plans(): JsonResponse
    {
        $plans = config('plans', []);

        $limits = PlanLimit::query()
            ->orderBy('plan_code')
            ->orderBy('feature_key')
            ->get();

        foreach ($limits as $limit) {
            $planCode = (string) $limit->plan_code;

            if (! isset($plans[$planCode])) {
                continue;
            }

            $plans[$planCode]['features'][(string) $limit->feature_key] = [
                'limit' => $limit->limit_value,
                'is_enforced' => (bool) $limit->is_enforced,
            ];
        }

        return ApiResponse::data(array_values($plans));
    }

    public function subscription(Request $request): JsonResponse
    {
        $user = $request->user();
        $subscription = $user->subscriptions()->latest('id')->first();

        $planCode = (string) ($subscription?->plan_code ?? $user->plan ?? 'free');
        $status = (string) ($subscription?->status ?? $user->subscription_status ?? 'inactive');
        $plan = config("plans.{$planCode}", config('plans.free'));

        return ApiResponse::data([
            'plan_code' => $planCode,
            'status' => $status,
            'provider' => $subscription?->provider,
            'trial_ends_at' => $subscription?->trial_ends_at,
            'current_period_ends_at' => $subscription?->current_period_ends_at,
            'canceled_at' => $subscription?->canceled_at,
            'entitlements' => $plan['features'] ?? [],
        ]);
    }
}
