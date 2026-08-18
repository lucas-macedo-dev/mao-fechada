<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Models\Transaction;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class MonthlySummaryController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year'  => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'between:1,12'],
        ]);

        $transactions = Transaction::query()
            ->with('category')
            ->where('user_id', $request->user()->id)
            ->whereYear('transacted_at', $validated['year'])
            ->whereMonth('transacted_at', $validated['month'])
            ->get();

        $incomeTotal = $transactions
            ->where('type', 'income')
            ->sum('amount');
        $expenseTotal = $transactions
            ->where('type', 'expense')
            ->sum('amount');

        $budgets = Budget::query()
            ->with('category')
            ->where('user_id', $request->user()->id)
            ->where('year', $validated['year'])
            ->where('month', $validated['month'])
            ->get()
            ->keyBy('category_id');

        $actualByCategory = $transactions
            ->where('type', 'expense')
            ->groupBy('category_id')
            ->map(fn (Collection $group): float => (float) $group->sum('amount'));
        $categoryNames = $transactions
            ->mapWithKeys(fn (Transaction $transaction): array => [
                $transaction->category_id => (string) ($transaction->category?->name ?? ''),
            ]);

        $allCategoryIds = $budgets->keys()->merge($actualByCategory->keys())->unique()->values();
        $variance = $allCategoryIds->map(function (int|string $categoryId) use ($budgets, $actualByCategory, $categoryNames): array {
            $budget = $budgets->get($categoryId);
            $actual = (float) ($actualByCategory->get($categoryId, 0));
            $budgetAmount = (float) ($budget?->amount ?? 0);

            return [
                'category_id'   => (int) $categoryId,
                'category_name' => $budget?->category?->name ?: ($categoryNames->get($categoryId) ?: null),
                'budget'        => $budgetAmount,
                'actual'        => $actual,
                'variance'      => $budgetAmount - $actual,
            ];
        })->values();

        return ApiResponse::data([
            'year'              => $validated['year'],
            'month'             => $validated['month'],
            'income_total'      => (float) $incomeTotal,
            'expense_total'     => (float) $expenseTotal,
            'net_balance'       => (float) $incomeTotal - (float) $expenseTotal,
            'category_variance' => $variance,
        ]);
    }
}
