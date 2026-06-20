<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Support\ApiResponse;
use App\Support\TransactionTypeMapper;
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
            ->whereIn('type', TransactionTypeMapper::incomeValues())
            ->sum('amount');
        $expense = (float) $transactions
            ->whereIn('type', TransactionTypeMapper::expenseValues())
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
            ->whereIn('type', TransactionTypeMapper::incomeValues())
            ->sum('amount');
        $expense = (float) $this->baseQuery($request, $year, $month)
            ->whereIn('type', TransactionTypeMapper::expenseValues())
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
}
