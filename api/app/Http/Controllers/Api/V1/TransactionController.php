<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Transaction;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $transactions = $request->user()
            ->transactions()
            ->with('category')
            ->orderByDesc('transacted_at')
            ->orderByDesc('id')
            ->get();

        return ApiResponse::data($transactions);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'type' => ['required', Rule::in(['income', 'expense'])],
            'amount' => ['required', 'numeric', 'gt:0'],
            'transacted_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $category = Category::query()->findOrFail($validated['category_id']);
        $this->ensureOwnership($request, $category->user_id);

        if ($category->type !== $validated['type']) {
            throw ValidationException::withMessages([
                'type' => ['Transaction type must match category type.'],
            ]);
        }

        $transaction = $request->user()
            ->transactions()
            ->create($validated);

        return ApiResponse::data($transaction->load('category'), 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $transaction = Transaction::query()->with('category')->findOrFail($id);
        $this->ensureOwnership($request, $transaction->user_id);

        return ApiResponse::data($transaction);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $transaction = Transaction::query()->findOrFail($id);
        $this->ensureOwnership($request, $transaction->user_id);

        $validated = $request->validate([
            'category_id' => ['sometimes', 'required', 'integer', 'exists:categories,id'],
            'type' => ['sometimes', 'required', Rule::in(['income', 'expense'])],
            'amount' => ['sometimes', 'required', 'numeric', 'gt:0'],
            'transacted_at' => ['sometimes', 'required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if (isset($validated['category_id'])) {
            $category = Category::query()->findOrFail($validated['category_id']);
            $this->ensureOwnership($request, $category->user_id);

            $transactionType = $validated['type'] ?? $transaction->type;
            if ($category->type !== $transactionType) {
                throw ValidationException::withMessages([
                    'type' => ['Transaction type must match category type.'],
                ]);
            }
        }

        $transaction->update($validated);

        return ApiResponse::data($transaction->fresh()->load('category'));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $transaction = Transaction::query()->findOrFail($id);
        $this->ensureOwnership($request, $transaction->user_id);

        $transaction->delete();

        return response()->json([], 204);
    }

    private function ensureOwnership(Request $request, int $ownerUserId): void
    {
        if ((int) $request->user()->id !== $ownerUserId) {
            abort(403, 'You are not allowed to access this resource.');
        }
    }
}
