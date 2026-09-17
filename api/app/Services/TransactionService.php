<?php

declare(strict_types=1);

namespace App\Services;

use App\DataTransferObjects\Input\CreateTransactionData;
use App\DataTransferObjects\Input\ListTransactionsFilterData;
use App\DataTransferObjects\Input\UpdateTransactionData;
use App\DataTransferObjects\Output\TransactionData;
use App\Models\Transaction;
use App\Repositories\CategoryRepository;
use App\Repositories\RecurringTransactionRepository;
use App\Repositories\TransactionRepository;
use App\Services\Concerns\AuthorizesOwnership;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransactionService
{
    use AuthorizesOwnership;

    public function __construct(
        private readonly TransactionRepository $transactions,
        private readonly CategoryRepository $categories,
        private readonly RecurringTransactionRepository $recurringTransactions,
    ) {}

    public function paginate(Request $request, ListTransactionsFilterData $data): LengthAwarePaginator
    {
        return $this->transactions->paginateForUser($request->user()->id, $data->filters, $data->perPage);
    }

    public function create(Request $request, CreateTransactionData $data): TransactionData|array
    {
        $category = $this->categories->findOrFail($data->categoryId);
        $this->ensureOwnership($request, $category->user_id);

        if ($category->type !== $data->type) {
            throw ValidationException::withMessages([
                'type' => [__('messages.transaction_type_must_match_category')],
            ]);
        }

        $userId = $request->user()->id;

        if ($data->recurring) {
            $transaction = DB::transaction(function () use ($userId, $data): Transaction {
                $transactedAt = Carbon::parse($data->transactedAt);

                $rule = $this->recurringTransactions->create([
                    'user_id'           => $userId,
                    'category_id'       => $data->categoryId,
                    'type'              => $data->type,
                    'payment_method'    => $data->paymentMethod,
                    'amount'            => $data->amount,
                    'notes'             => $data->notes,
                    'day_of_month'      => $transactedAt->day,
                    'status'            => 'active',
                    'last_generated_at' => $transactedAt->format('Y-m-d'),
                ]);

                return $this->transactions->createForUser($userId, [
                    ...$data->toModelAttributes(),
                    'recurring_transaction_id' => $rule->id,
                ]);
            });

            return TransactionData::fromModel($transaction->load('category'));
        }

        if ($data->installmentNumber !== null && $data->installmentTotal !== null) {
            $transactions = DB::transaction(function () use ($userId, $data): array {
                $groupId = $this->transactions->newInstallmentGroupId();
                $baseDate = Carbon::parse($data->transactedAt);
                $records = [];

                for ($i = 1; $i <= $data->installmentTotal; $i++) {
                    $offset = $i - $data->installmentNumber;
                    $date = $baseDate->copy()->addMonths($offset)->format('Y-m-d');

                    $records[] = $this->transactions->createForUser($userId, [
                        ...$data->toModelAttributes(),
                        'transacted_at'        => $date,
                        'installment_group_id' => $groupId,
                        'installment_number'   => $i,
                        'installment_total'    => $data->installmentTotal,
                    ]);
                }

                return $records;
            });

            return array_map(
                fn (Transaction $t) => TransactionData::fromModel($t->load('category'))->toArray(),
                $transactions,
            );
        }

        $transaction = $this->transactions->createForUser($userId, $data->toModelAttributes());

        return TransactionData::fromModel($transaction->load('category'));
    }

    public function find(Request $request, int $id): TransactionData
    {
        $transaction = $this->transactions->findWithCategory($id);
        $this->ensureOwnership($request, $transaction->user_id);

        return TransactionData::fromModel($transaction);
    }

    public function update(Request $request, int $id, UpdateTransactionData $data): TransactionData
    {
        $transaction = $this->transactions->findOrFail($id);
        $this->ensureOwnership($request, $transaction->user_id);

        if ($data->hasCategoryId) {
            $category = $this->categories->findOrFail($data->categoryId);
            $this->ensureOwnership($request, $category->user_id);

            $transactionType = $data->type ?? $transaction->type;
            if ($category->type !== $transactionType) {
                throw ValidationException::withMessages([
                    'type' => [__('messages.transaction_type_must_match_category')],
                ]);
            }
        }

        $attributes = $data->toModelAttributes();

        if ($transaction->installment_group_id) {
            $cascadeAttributes = array_diff_key(
                $attributes,
                array_flip(['transacted_at', 'installment_number', 'installment_total', 'installment_group_id']),
            );

            DB::transaction(function () use ($transaction, $cascadeAttributes): void {
                $this->transactions->updateCascadeByInstallmentGroup(
                    $transaction->user_id,
                    $transaction->installment_group_id,
                    $cascadeAttributes,
                );
            });

            return TransactionData::fromModel($transaction->fresh('category'));
        }

        $transaction = $this->transactions->update($transaction, $attributes);

        return TransactionData::fromModel($transaction);
    }

    public function convertToRecurring(Request $request, int $id): TransactionData
    {
        $transaction = $this->transactions->findOrFail($id);
        $this->ensureOwnership($request, $transaction->user_id);

        if ($transaction->installment_group_id !== null) {
            throw ValidationException::withMessages([
                'transaction' => [__('messages.transaction_already_installment')],
            ]);
        }

        if ($transaction->recurring_transaction_id !== null) {
            throw ValidationException::withMessages([
                'transaction' => [__('messages.transaction_already_recurring')],
            ]);
        }

        $transaction = DB::transaction(function () use ($request, $transaction): Transaction {
            $transactedAt = Carbon::parse($transaction->transacted_at);

            $rule = $this->recurringTransactions->create([
                'user_id'           => $request->user()->id,
                'category_id'       => $transaction->category_id,
                'type'              => $transaction->type,
                'payment_method'    => $transaction->payment_method,
                'amount'            => $transaction->amount,
                'notes'             => $transaction->notes,
                'day_of_month'      => $transactedAt->day,
                'status'            => 'active',
                'last_generated_at' => $transactedAt->format('Y-m-d'),
            ]);

            $transaction->update(['recurring_transaction_id' => $rule->id]);

            return $transaction;
        });

        return TransactionData::fromModel($transaction->fresh('category'));
    }

    public function delete(Request $request, int $id): void
    {
        $transaction = $this->transactions->findOrFail($id);
        $this->ensureOwnership($request, $transaction->user_id);

        if ($transaction->installment_group_id) {
            DB::transaction(function () use ($transaction): void {
                $this->transactions->deleteByInstallmentGroup($transaction->user_id, $transaction->installment_group_id);
            });

            return;
        }

        $this->transactions->delete($transaction);
    }
}
