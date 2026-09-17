<?php

declare(strict_types=1);

namespace App\Services;

use App\DataTransferObjects\Output\CategoryBreakdownData;
use App\DataTransferObjects\Output\DashboardChartData;
use App\DataTransferObjects\Output\DashboardSummaryData;
use App\DataTransferObjects\Output\MonthlyComparisonData;
use App\DataTransferObjects\Output\MtdComparisonData;
use App\DataTransferObjects\Output\TransactionData;
use App\DataTransferObjects\Output\WeekdayExpenseData;
use App\Repositories\TransactionRepository;
use App\Services\Concerns\AuthorizesOwnership;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    use AuthorizesOwnership;

    public function __construct(
        private readonly TransactionRepository $transactions,
    ) {}

    public function summary(Request $request, int $year, int $month): DashboardSummaryData
    {
        $userId = $request->user()->id;

        // Cached in Redis: this aggregate is read on every dashboard load but only
        // changes when the user's transactions for the month change (see
        // Transaction::booted(), which flushes this tag on save/delete).
        $payload = Cache::tags(["dashboard-summary:{$userId}"])
            ->remember(
                "dashboard-summary:{$userId}:{$year}-{$month}",
                now()->addMinutes(10),
                function () use ($userId, $year, $month): array {
                    $transactionsThisMonth = $this->transactions->allForMonth($userId, $year, $month);
                    $income = (float) $transactionsThisMonth->where('type', 'income')->sum('amount');
                    $expense = (float) $transactionsThisMonth->where('type', 'expense')->sum('amount');

                    $recent = $this->transactions->recentForMonth($userId, $year, $month, 5);

                    return (new DashboardSummaryData(
                        month: sprintf('%04d-%02d', $year, $month),
                        income: $income,
                        expense: $expense,
                        recentTransactions: TransactionData::collection($recent),
                    ))->toArray();
                }
            );

        return new DashboardSummaryData(
            month: $payload['month'],
            income: $payload['totals']['entradas'],
            expense: $payload['totals']['saidas'],
            recentTransactions: $payload['recent_transactions'],
        );
    }

    public function chart(Request $request, int $year, int $month): DashboardChartData
    {
        $userId = $request->user()->id;

        $income = $this->transactions->sumByTypeForMonth($userId, $year, $month, 'income');
        $expense = $this->transactions->sumByTypeForMonth($userId, $year, $month, 'expense');

        return new DashboardChartData(sprintf('%04d-%02d', $year, $month), $income, $expense);
    }

    public function recent(Request $request, int $year, int $month, int $limit): array
    {
        $userId = $request->user()->id;

        $recent = $this->transactions->recentForMonth($userId, $year, $month, $limit);

        return TransactionData::collection($recent);
    }

    public function byCategory(Request $request, int $year, int $month): array
    {
        $userId = $request->user()->id;
        $this->ensureOwnership($request, $userId);

        $transactions = $this->transactions->expenseGroupedByMainCategoryForMonth($userId, $year, $month);

        $rows = $transactions
            ->groupBy(fn ($transaction) => $transaction->category?->parent_id ?? $transaction->category_id)
            ->map(function ($grouped) {
                $category = $grouped->first()->category;
                $mainCategory = $category?->parent ?? $category;

                return new CategoryBreakdownData(
                    name: $mainCategory?->name ?? 'Sem categoria',
                    value: (float) number_format($grouped->sum('amount'), 2, '.', ''),
                );
            })
            ->values()
            ->sortByDesc('value');

        $top = $rows->take(10);
        $other = $rows->skip(10);

        $result = CategoryBreakdownData::collection($top->values()->all());

        if ($other->isNotEmpty()) {
            $otherTotal = $other->sum(fn (CategoryBreakdownData $row) => $row->value);

            $result[] = (new CategoryBreakdownData(
                name: 'Outros',
                value: (float) number_format($otherTotal, 2, '.', ''),
            ))->toArray();
        }

        return $result;
    }

    public function installmentsTotal(Request $request, int $year, int $month): array
    {
        $userId = $request->user()->id;

        $total = $this->transactions->installmentsAndRecurringExpenseTotalForMonth($userId, $year, $month);

        return [
            'month' => sprintf('%04d-%02d', $year, $month),
            'total' => $total,
        ];
    }

    public function byDay(Request $request, int $year, int $month): array
    {
        $userId = $request->user()->id;
        $this->ensureOwnership($request, $userId);

        $transactions = $this->transactions->allForMonth($userId, $year, $month);
        $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;

        $byDay = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $byDay[$day] = ['day' => $day, 'income' => 0.0, 'expense' => 0.0];
        }

        foreach ($transactions as $tx) {
            $day = (int) Carbon::parse($tx->transacted_at)->format('d');
            if ($tx->type === 'income') {
                $byDay[$day]['income'] += (float) $tx->amount;
            } else {
                $byDay[$day]['expense'] += (float) $tx->amount;
            }
        }

        return array_values($byDay);
    }

    public function monthlyComparison(Request $request, int $year, int $month): MonthlyComparisonData
    {
        $userId = $request->user()->id;
        $this->ensureOwnership($request, $userId);

        $anchor = Carbon::createFromDate($year, $month, 1);

        $months = [];
        for ($i = 2; $i >= 0; $i--) {
            $date = $anchor->copy()->subMonths($i);

            $income = $this->transactions->sumByTypeForMonth($userId, $date->year, $date->month, 'income');
            $expense = $this->transactions->sumByTypeForMonth($userId, $date->year, $date->month, 'expense');

            $months[] = [
                'month'   => $date->format('Y-m'),
                'income'  => $income,
                'expense' => $expense,
            ];
        }

        return new MonthlyComparisonData($months);
    }

    public function mtdComparison(Request $request, int $year, int $month): MtdComparisonData
    {
        $userId = $request->user()->id;
        $this->ensureOwnership($request, $userId);

        $selected = Carbon::createFromDate($year, $month, 1);
        $now = Carbon::now();

        $isCurrentMonth = $selected->year === $now->year && $selected->month === $now->month;
        $throughDay = $isCurrentMonth
            ? min($now->day, $selected->daysInMonth)
            : $selected->daysInMonth;

        $previous = $selected->copy()->subMonth();
        $previousThroughDay = min($throughDay, $previous->daysInMonth);

        $currentIncome = $this->transactions->sumByTypeThroughDay($userId, $selected->year, $selected->month, $throughDay, 'income');
        $previousIncome = $this->transactions->sumByTypeThroughDay($userId, $previous->year, $previous->month, $previousThroughDay, 'income');

        $currentExpense = $this->transactions->sumByTypeThroughDay($userId, $selected->year, $selected->month, $throughDay, 'expense');
        $previousExpense = $this->transactions->sumByTypeThroughDay($userId, $previous->year, $previous->month, $previousThroughDay, 'expense');

        $currentInstallments = $this->transactions->sumByTypeThroughDay($userId, $selected->year, $selected->month, $throughDay, 'expense', true);
        $previousInstallments = $this->transactions->sumByTypeThroughDay($userId, $previous->year, $previous->month, $previousThroughDay, 'expense', true);

        $buildComparison = function (float $currentTotal, float $previousTotal) use ($selected, $previous, $throughDay, $previousThroughDay) {
            return [
                'current' => [
                    'month'       => $selected->format('Y-m'),
                    'through_day' => $throughDay,
                    'total'       => $currentTotal,
                ],
                'previous' => [
                    'month'       => $previous->format('Y-m'),
                    'through_day' => $previousThroughDay,
                    'total'       => $previousTotal,
                ],
                'change_percent' => $previousTotal != 0
                    ? round((($currentTotal - $previousTotal) / $previousTotal) * 100, 2)
                    : null,
            ];
        };

        return new MtdComparisonData(
            income: $buildComparison($currentIncome, $previousIncome),
            expense: $buildComparison($currentExpense, $previousExpense),
            balance: $buildComparison($currentIncome - $currentExpense, $previousIncome - $previousExpense),
            installments: $buildComparison($currentInstallments, $previousInstallments),
        );
    }

    public function byPaymentMethod(Request $request, int $year, int $month): array
    {
        $userId = $request->user()->id;
        $this->ensureOwnership($request, $userId);

        $transactions = $this->transactions->expenseGroupedByPaymentMethodForMonth($userId, $year, $month);

        $result = $transactions
            ->groupBy('payment_method')
            ->map(fn ($grouped, $method) => new CategoryBreakdownData(
                name: $method,
                value: (float) number_format($grouped->sum('amount'), 2, '.', ''),
            ))
            ->sortByDesc(fn (CategoryBreakdownData $row) => $row->value)
            ->values()
            ->all();

        return CategoryBreakdownData::collection($result);
    }

    public function weeklyExpenses(Request $request): array
    {
        $userId = $request->user()->id;
        $this->ensureOwnership($request, $userId);

        $startOfWeek = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $endOfWeek = $startOfWeek->copy()->addDays(6);

        $byWeekday = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $startOfWeek->copy()->addDays($i);
            $byWeekday[$date->dayOfWeekIso] = [
                'weekday' => $date->dayOfWeekIso,
                'date'    => $date->toDateString(),
                'expense' => 0.0,
            ];
        }

        $transactions = $this->transactions->forDateRangeForUser(
            $userId,
            'expense',
            $startOfWeek->toDateString(),
            $endOfWeek->toDateString(),
        );

        foreach ($transactions as $tx) {
            $weekday = Carbon::parse($tx->transacted_at)->dayOfWeekIso;
            if (isset($byWeekday[$weekday])) {
                $byWeekday[$weekday]['expense'] += (float) $tx->amount;
            }
        }

        return (new WeekdayExpenseData(array_values($byWeekday)))->toArray();
    }
}
