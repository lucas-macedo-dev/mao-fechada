<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListTransactionsRequest;
use App\Http\Requests\Api\V1\StoreTransactionRequest;
use App\Http\Requests\Api\V1\UpdateTransactionRequest;
use App\Models\Category;
use App\Models\Transaction;
use App\Support\ApiResponse;
use App\Support\TransactionTypeMapper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    public function index(ListTransactionsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $query = $request->user()
            ->transactions()
            ->with('category')
            ->orderByDesc('transacted_at')
            ->orderByDesc('id');

        if (isset($validated['category_id'])) {
            $query->where('category_id', $validated['category_id']);
        }

        if (isset($validated['type'])) {
            $query->where('type', TransactionTypeMapper::toDatabase($validated['type']));
        }

        if (isset($validated['payment_method'])) {
            $query->where('payment_method', $validated['payment_method']);
        }

        if (isset($validated['month'])) {
            $monthDate = Carbon::createFromFormat('Y-m', $validated['month']);
            $query
                ->whereYear('transacted_at', $monthDate->year)
                ->whereMonth('transacted_at', $monthDate->month);
        }

        if (isset($validated['date_from'])) {
            $query->whereDate('transacted_at', '>=', $validated['date_from']);
        }

        if (isset($validated['date_to'])) {
            $query->whereDate('transacted_at', '<=', $validated['date_to']);
        }

        $perPage = (int) ($validated['per_page'] ?? 20);
        $paginator = $query->paginate($perPage)->appends($request->query());

        return ApiResponse::data($paginator->items(), meta: [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ]);
    }

    public function store(StoreTransactionRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['type'] = TransactionTypeMapper::toDatabase($validated['type']);

        $category = Category::query()->findOrFail($validated['category_id']);
        $this->ensureOwnership($request, $category->user_id);

        if ($category->type !== $validated['type']) {
            throw ValidationException::withMessages([
                'type' => [__('messages.transaction_type_must_match_category')],
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

    public function update(UpdateTransactionRequest $request, int $id): JsonResponse
    {
        $transaction = Transaction::query()->findOrFail($id);
        $this->ensureOwnership($request, $transaction->user_id);

        $validated = $request->validated();

        if (isset($validated['type'])) {
            $validated['type'] = TransactionTypeMapper::toDatabase($validated['type']);
        }

        if (isset($validated['category_id'])) {
            $category = Category::query()->findOrFail($validated['category_id']);
            $this->ensureOwnership($request, $category->user_id);

            $transactionType = $validated['type'] ?? $transaction->type;
            if ($category->type !== $transactionType) {
                throw ValidationException::withMessages([
                    'type' => [__('messages.transaction_type_must_match_category')],
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
            abort(403, __('messages.ownership_denied'));
        }
    }
}
