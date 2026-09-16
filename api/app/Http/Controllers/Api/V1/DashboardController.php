<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        [$year, $month] = $this->resolveMonth($request);
        $userId = $request->user()->id;

        // Cached in Redis: this aggregate is read on every dashboard load but only
        // changes when the user's transactions for the month change (see
        // Transaction::booted(), which flushes this tag on save/delete).
        $payload = Cache::tags(["dashboard-summary:{$userId}"])
            ->remember(
                "dashboard-summary:{$userId}:{$year}-{$month}",
                now()->addMinutes(10),
                function () use ($request, $year, $month): array {
                    $transactions = $this->baseQuery($request, $year, $month)->get();
                    $income = (float) $transactions
                        ->where('type', 'income')
                        ->sum('amount');
                    $expense = (float) $transactions
                        ->where('type', 'expense')
                        ->sum('amount');

                    return [
                        'month'  => sprintf('%04d-%02d', $year, $month),
                        'totals' => [
                            'entradas' => $income,
                            'saidas'   => $expense,
                            'saldo'    => $income - $expense,
                        ],
                        'chart' => [
                            'entradas' => $income,
                            'saidas'   => $expense,
                        ],
                        'recent_transactions' => $this->baseQuery($request, $year, $month)
                            ->with('category')
                            ->orderByDesc('transacted_at')
                            ->orderByDesc('id')
                            ->limit(5)
                            ->get(),
                    ];
                }
            );

        return ApiResponse::data($payload);
    }

    public function chart(Request $request): JsonResponse
    {
        [$year, $month] = $this->resolveMonth($request);

        $income = (float) $this->baseQuery($request, $year, $month)
            ->where('type', 'income')
            ->sum('amount');
        $expense = (float) $this->baseQuery($request, $year, $month)
            ->where('type', 'expense')
            ->sum('amount');

        return ApiResponse::data([
            'month'  => sprintf('%04d-%02d', $year, $month),
            'series' => [
                'entradas' => $income,
                'saidas'   => $expense,
            ],
        ]);
    }

    public function recent(Request $request): JsonResponse
    {
        [$year, $month] = $this->resolveMonth($request);
        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $recent = $this->baseQuery($request, $year, $month)
            ->with('category')
            ->orderByDesc('transacted_at')
            ->orderByDesc('id')
            ->limit((int) ($validated['limit'] ?? 5))
            ->get();

        return ApiResponse::data($recent);
    }

    public function byCategory(Request $request): JsonResponse
    {
        $this->ensureOwnership($request, $request->user()->id);

        [$year, $month] = $this->resolveMonth($request);

        $rows = $this->baseQuery($request, $year, $month)
            ->where('type', 'expense')
            ->with('category.parent')
            ->get()
            ->groupBy(fn ($transaction) => $transaction->category?->parent_id ?? $transaction->category_id)
            ->map(function ($transactions) {
                $category = $transactions->first()->category;
                $mainCategory = $category?->parent ?? $category;

                return [
                    'name'  => $mainCategory?->name ?? 'Sem categoria',
                    'value' => (float) number_format($transactions->sum('amount'), 2, '.', ''),
                ];
            })
            ->values()
            ->sortByDesc('value');

        $top = $rows->take(10);
        $other = $rows->skip(10);

        $result = $top->values()->toArray();

        if ($other->isNotEmpty()) {
            $result[] = [
                'name'  => 'Outros',
                'value' =>  (float) number_format(  $other->sum('value'), 2, '.', ''),
            ];
        }

        return ApiResponse::data($result);
    }

    public function installmentsTotal(Request $request): JsonResponse
    {
        [$year, $month] = $this->resolveMonth($request);

        $total = (float) $this->baseQuery($request, $year, $month)
            ->where('type', 'expense')
            ->whereNotNull('installment_group_id')
            ->orWhereNotNull('recurring_transaction_id')
            ->sum('amount');

        return ApiResponse::data([
            'month' => sprintf('%04d-%02d', $year, $month),
            'total' => $total,
        ]);
    }

    public function byDay(Request $request): JsonResponse
    {
        $this->ensureOwnership($request, $request->user()->id);

        [$year, $month] = $this->resolveMonth($request);

        $transactions = $this->baseQuery($request, $year, $month)->get();
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

        return ApiResponse::data(array_values($byDay));
    }

    public function monthlyComparison(Request $request): JsonResponse
    {
        $this->ensureOwnership($request, $request->user()->id);

        [$year, $month] = $this->resolveMonth($request);
        $anchor = Carbon::createFromDate($year, $month, 1);

        $months = [];
        for ($i = 2; $i >= 0; $i--) {
            $date = $anchor->copy()->subMonths($i);

            $income = (float) $this->baseQuery($request, $date->year, $date->month)
                ->where('type', 'income')
                ->sum('amount');
            $expense = (float) $this->baseQuery($request, $date->year, $date->month)
                ->where('type', 'expense')
                ->sum('amount');

            $months[] = [
                'month'   => $date->format('Y-m'),
                'income'  => $income,
                'expense' => $expense,
            ];
        }

        return ApiResponse::data(['months' => $months]);
    }

    public function mtdComparison(Request $request): JsonResponse
    {
        $this->ensureOwnership($request, $request->user()->id);

        [$year, $month] = $this->resolveMonth($request);
        $selected = Carbon::createFromDate($year, $month, 1);
        $now = Carbon::now();

        $isCurrentMonth = $selected->year === $now->year && $selected->month === $now->month;
        $throughDay = $isCurrentMonth
            ? min($now->day, $selected->daysInMonth)
            : $selected->daysInMonth;

        $previous = $selected->copy()->subMonth();
        $previousThroughDay = min($throughDay, $previous->daysInMonth);

        $sumThrough = function (int $year, int $month, int $throughDay, string $type, bool $installmentsOnly = false) use ($request) {
            $query = $this->baseQuery($request, $year, $month)
                ->where('type', $type)
                ->whereDay('transacted_at', '<=', $throughDay);

            if ($installmentsOnly) {
                $query->whereNotNull('installment_group_id');
            }

            return (float) $query->sum('amount');
        };

        $currentIncome = $sumThrough($selected->year, $selected->month, $throughDay, 'income');
        $previousIncome = $sumThrough($previous->year, $previous->month, $previousThroughDay, 'income');

        $currentExpense = $sumThrough($selected->year, $selected->month, $throughDay, 'expense');
        $previousExpense = $sumThrough($previous->year, $previous->month, $previousThroughDay, 'expense');

        $currentInstallments = $sumThrough($selected->year, $selected->month, $throughDay, 'expense', true);
        $previousInstallments = $sumThrough($previous->year, $previous->month, $previousThroughDay, 'expense', true);

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

        return ApiResponse::data([
            'income'       => $buildComparison($currentIncome, $previousIncome),
            'expense'      => $buildComparison($currentExpense, $previousExpense),
            'balance'      => $buildComparison($currentIncome - $currentExpense, $previousIncome - $previousExpense),
            'installments' => $buildComparison($currentInstallments, $previousInstallments),
        ]);
    }

    public function byPaymentMethod(Request $request): JsonResponse
    {
        $this->ensureOwnership($request, $request->user()->id);

        [$year, $month] = $this->resolveMonth($request);

        $result = $this->baseQuery($request, $year, $month)
            ->where('type', 'expense')
            ->get()
            ->groupBy('payment_method')
            ->map(fn ($transactions, $method) => [
                'name'  => $method,
                'value' => (float) number_format($transactions->sum('amount'), 2, '.', ''),
            ])
            ->sortByDesc('value')
            ->values();

        return ApiResponse::data($result);
    }

    public function weeklyExpenses(Request $request): JsonResponse
    {
        $this->ensureOwnership($request, $request->user()->id);

        $startOfWeek = Carbon::now()->startOfWeek(Carbon::MONDAY);

        $byWeekday = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $startOfWeek->copy()->addDays($i);
            $byWeekday[$date->dayOfWeekIso] = [
                'weekday' => $date->dayOfWeekIso,
                'date'    => $date->toDateString(),
                'expense' => 0.0,
            ];
        }

        $transactions = Transaction::query()
            ->where('user_id', $request->user()->id)
            ->where('type', 'expense')
            ->whereDate('transacted_at', '>=', $startOfWeek->toDateString())
            ->whereDate('transacted_at', '<=', $startOfWeek->copy()->addDays(6)->toDateString())
            ->get();

        foreach ($transactions as $tx) {
            $weekday = Carbon::parse($tx->transacted_at)->dayOfWeekIso;
            if (isset($byWeekday[$weekday])) {
                $byWeekday[$weekday]['expense'] += (float) $tx->amount;
            }
        }

        return ApiResponse::data(array_values($byWeekday));
    }

    private function resolveMonth(Request $request): array
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $month = $validated['month'] ?? now()->format('Y-m');
        $date = Carbon::createFromFormat('Y-m', $month);

        return [$date->year, $date->month];
    }

    private function baseQuery(Request $request, int $year, int $month)
    {
        return Transaction::query()
            ->where('user_id', $request->user()->id)
            ->whereYear('transacted_at', $year)
            ->whereMonth('transacted_at', $month);
    }

    private function ensureOwnership(Request $request, int $ownerUserId): void
    {
        if ((int) $request->user()->id !== $ownerUserId) {
            abort(403, __('messages.ownership_denied'));
        }
    }
}
