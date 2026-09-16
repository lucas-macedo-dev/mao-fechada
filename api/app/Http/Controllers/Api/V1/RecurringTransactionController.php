<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\RecurringTransaction;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecurringTransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $rules = $request->user()
            ->recurringTransactions()
            ->with('category')
            ->orderBy('status')
            ->orderByDesc('id')
            ->get();

        return ApiResponse::data($rules);
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $rule = RecurringTransaction::query()->findOrFail($id);
        $this->ensureOwnership($request, $rule->user_id);

        if ($rule->status === 'active') {
            $rule->update(['status' => 'cancelled']);
        }

        return ApiResponse::data($rule->fresh()->load('category'));
    }

    private function ensureOwnership(Request $request, int $ownerUserId): void
    {
        if ((int) $request->user()->id !== $ownerUserId) {
            abort(403, __('messages.ownership_denied'));
        }
    }
}
