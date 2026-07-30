<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        [$year, $month] = $this->resolveMonth($request);

        $transactions = $this->baseQuery($request, $year, $month)->get();
        $income = (float) $transactions
            ->where('type', 'income')
            ->sum('amount');
        $expense = (float) $transactions
            ->where('type', 'expense')
            ->sum('amount');

        return ApiResponse::data([
            'month' => sprintf('%04d-%02d', $year, $month),
            'totals' => [
                'entradas' => $income,
                'saidas' => $expense,
                'saldo' => $income - $expense,
            ],
            'chart' => [
                'entradas' => $income,
                'saidas' => $expense,
            ],
            'recent_transactions' => $this->baseQuery($request, $year, $month)
                ->with('category')
                ->orderByDesc('transacted_at')
                ->orderByDesc('id')
                ->limit(5)
                ->get(),
        ]);
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
            'month' => sprintf('%04d-%02d', $year, $month),
            'series' => [
                'entradas' => $income,
                'saidas' => $expense,
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
                    'value' => (float) $transactions->sum('amount'),
                ];
            })
            ->values()
            ->sortByDesc('value');

        $top   = $rows->take(8);
        $other = $rows->skip(8);

        $result = $top->values()->toArray();

        if ($other->isNotEmpty()) {
            $result[] = [
                'name'  => 'Outros',
                'value' => (float) $other->sum('value'),
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
        $daysInMonth  = Carbon::createFromDate($year, $month, 1)->daysInMonth;

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
