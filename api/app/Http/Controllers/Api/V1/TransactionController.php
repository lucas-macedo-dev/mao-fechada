<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListTransactionsRequest;
use App\Http\Requests\Api\V1\StoreTransactionRequest;
use App\Http\Requests\Api\V1\UpdateTransactionRequest;
use App\Models\Category;
use App\Models\Transaction;
use App\Support\ApiResponse;
use App\Support\TransactionFilterQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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

        TransactionFilterQuery::apply($query, $validated);

        $perPage = (int) ($validated['per_page'] ?? 20);
        $paginator = $query->paginate($perPage)->appends($request->query());

        return ApiResponse::data($paginator->items(), meta: [
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
            'per_page'     => $paginator->perPage(),
            'total'        => $paginator->total(),
        ]);
    }

    public function store(StoreTransactionRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $category = Category::query()->findOrFail($validated['category_id']);
        $this->ensureOwnership($request, $category->user_id);

        if ($category->type !== $validated['type']) {
            throw ValidationException::withMessages([
                'type' => [__('messages.transaction_type_must_match_category')],
            ]);
        }

        $installmentNumber = $validated['installment_number'] ?? null;
        $installmentTotal = $validated['installment_total'] ?? null;

        if ($installmentNumber !== null && $installmentTotal !== null) {
            $transactions = DB::transaction(function () use ($request, $validated, $installmentNumber, $installmentTotal): array {
                $groupId = Str::uuid()->toString();
                $baseDate = Carbon::parse($validated['transacted_at']);
                $records = [];

                for ($i = 1; $i <= $installmentTotal; $i++) {
                    $offset = $i - $installmentNumber;
                    $date = $baseDate->copy()->addMonths($offset)->format('Y-m-d');

                    $records[] = $request->user()->transactions()->create(array_merge(
                        $validated,
                        [
                            'transacted_at'        => $date,
                            'installment_group_id' => $groupId,
                            'installment_number'   => $i,
                            'installment_total'    => $installmentTotal,
                        ]
                    ));
                }

                return $records;
            });

            $loaded = collect($transactions)->map(fn ($t) => $t->load('category'));

            return ApiResponse::data($loaded, 201);
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

        if ($transaction->installment_group_id) {
            $cascadeFields = array_diff_key($validated, array_flip(['transacted_at', 'installment_number', 'installment_total', 'installment_group_id']));

            DB::transaction(function () use ($transaction, $cascadeFields): void {
                Transaction::query()
                    ->where('user_id', $transaction->user_id)
                    ->where('installment_group_id', $transaction->installment_group_id)
                    ->update($cascadeFields);
            });

            return ApiResponse::data($transaction->fresh()->load('category'));
        }

        $transaction->update($validated);

        return ApiResponse::data($transaction->fresh()->load('category'));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $transaction = Transaction::query()->findOrFail($id);
        $this->ensureOwnership($request, $transaction->user_id);

        if ($transaction->installment_group_id) {
            DB::transaction(function () use ($transaction): void {
                Transaction::query()
                    ->where('user_id', $transaction->user_id)
                    ->where('installment_group_id', $transaction->installment_group_id)
                    ->delete();
            });

            return response()->json([], 204);
        }

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
