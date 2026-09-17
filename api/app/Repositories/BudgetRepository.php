<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Budget;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepository<Budget>
 */
class BudgetRepository extends BaseRepository
{
    protected string $modelClass = Budget::class;

    public function listForUserAndMonth(int $userId, int $year, int $month): Collection
    {
        return Budget::query()
            ->with('category')
            ->where('user_id', $userId)
            ->where('year', $year)
            ->where('month', $month)
            ->orderBy('category_id')
            ->get();
    }

    public function upsert(int $userId, int $categoryId, int $year, int $month, float $amount): Budget
    {
        $budget = Budget::query()->updateOrCreate(
            [
                'user_id'     => $userId,
                'category_id' => $categoryId,
                'year'        => $year,
                'month'       => $month,
            ],
            [
                'amount' => $amount,
            ],
        );

        return $budget->load('category');
    }
}
