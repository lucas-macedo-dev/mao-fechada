<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\RecurringTransaction;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepository<RecurringTransaction>
 */
class RecurringTransactionRepository extends BaseRepository
{
    protected string $modelClass = RecurringTransaction::class;

    public function listForUser(int $userId): Collection
    {
        return RecurringTransaction::query()
            ->where('user_id', $userId)
            ->with('category')
            ->orderBy('status')
            ->orderByDesc('id')
            ->get();
    }

    public function cancel(RecurringTransaction $rule): RecurringTransaction
    {
        if ($rule->status === 'active') {
            $rule->update(['status' => 'cancelled']);
        }

        return $rule->fresh('category');
    }
}
