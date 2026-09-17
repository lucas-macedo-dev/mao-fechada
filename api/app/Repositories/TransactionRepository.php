<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Transaction;
use App\Support\TransactionFilterQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

/**
 * @extends BaseRepository<Transaction>
 */
class TransactionRepository extends BaseRepository
{
    protected string $modelClass = Transaction::class;

    public function paginateForUser(int $userId, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = Transaction::query()
            ->where('user_id', $userId)
            ->with('category')
            ->orderByDesc('transacted_at')
            ->orderByDesc('id');

        TransactionFilterQuery::apply($query, $filters);

        return $query->paginate($perPage);
    }

    public function findWithCategory(int $id): Transaction
    {
        return Transaction::query()->with('category')->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createForUser(int $userId, array $attributes): Transaction
    {
        $attributes['user_id'] = $userId;

        return Transaction::query()->create($attributes);
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function createManyForUser(int $userId, array $rows): Collection
    {
        /** @var Collection<int, Transaction> $created */
        $created = new Collection;

        foreach ($rows as $attributes) {
            $created->push($this->createForUser($userId, $attributes));
        }

        return $created;
    }

    public function update(Transaction $transaction, array $attributes): Transaction
    {
        $transaction->update($attributes);

        return $transaction->fresh('category');
    }

    public function updateCascadeByInstallmentGroup(int $userId, string $installmentGroupId, array $attributes): void
    {
        Transaction::query()
            ->where('user_id', $userId)
            ->where('installment_group_id', $installmentGroupId)
            ->update($attributes);
    }

    public function delete(Transaction $transaction): void
    {
        $transaction->delete();
    }

    public function deleteByInstallmentGroup(int $userId, string $installmentGroupId): void
    {
        Transaction::query()
            ->where('user_id', $userId)
            ->where('installment_group_id', $installmentGroupId)
            ->delete();
    }

    public function newInstallmentGroupId(): string
    {
        return Str::uuid()->toString();
    }

    /**
     * @return Builder<Transaction>
     */
    public function queryForMonth(int $userId, int $year, int $month): Builder
    {
        return Transaction::query()
            ->where('user_id', $userId)
            ->whereYear('transacted_at', $year)
            ->whereMonth('transacted_at', $month);
    }

    public function sumByTypeForMonth(int $userId, int $year, int $month, string $type): float
    {
        return (float) $this->queryForMonth($userId, $year, $month)
            ->where('type', $type)
            ->sum('amount');
    }

    public function sumByTypeThroughDay(int $userId, int $year, int $month, int $throughDay, string $type, bool $installmentsOnly = false): float
    {
        $query = $this->queryForMonth($userId, $year, $month)
            ->where('type', $type)
            ->whereDay('transacted_at', '<=', $throughDay);

        if ($installmentsOnly) {
            $query->whereNotNull('installment_group_id');
        }

        return (float) $query->sum('amount');
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function recentForMonth(int $userId, int $year, int $month, int $limit): Collection
    {
        return $this->queryForMonth($userId, $year, $month)
            ->with('category')
            ->orderByDesc('transacted_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function allForMonth(int $userId, int $year, int $month): Collection
    {
        return $this->queryForMonth($userId, $year, $month)->get();
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function expenseGroupedByMainCategoryForMonth(int $userId, int $year, int $month): Collection
    {
        return $this->queryForMonth($userId, $year, $month)
            ->where('type', 'expense')
            ->with('category.parent')
            ->get();
    }

    public function installmentsAndRecurringExpenseTotalForMonth(int $userId, int $year, int $month): float
    {
        return (float) $this->queryForMonth($userId, $year, $month)
            ->where('type', 'expense')
            ->whereNotNull('installment_group_id')
            ->orWhereNotNull('recurring_transaction_id')
            ->sum('amount');
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function expenseGroupedByPaymentMethodForMonth(int $userId, int $year, int $month): Collection
    {
        return $this->queryForMonth($userId, $year, $month)
            ->where('type', 'expense')
            ->get();
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function forDateRangeForUser(int $userId, string $type, string $fromDate, string $toDate): Collection
    {
        return Transaction::query()
            ->where('user_id', $userId)
            ->where('type', $type)
            ->whereDate('transacted_at', '>=', $fromDate)
            ->whereDate('transacted_at', '<=', $toDate)
            ->get();
    }
}
