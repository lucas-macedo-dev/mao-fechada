<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService) {}

    public function summary(Request $request): JsonResponse
    {
        [$year, $month] = $this->resolveMonth($request);

        return ApiResponse::data($this->dashboardService->summary($request, $year, $month)->toArray());
    }

    public function chart(Request $request): JsonResponse
    {
        [$year, $month] = $this->resolveMonth($request);

        return ApiResponse::data($this->dashboardService->chart($request, $year, $month)->toArray());
    }

    public function recent(Request $request): JsonResponse
    {
        [$year, $month] = $this->resolveMonth($request);
        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        return ApiResponse::data($this->dashboardService->recent($request, $year, $month, (int) ($validated['limit'] ?? 5)));
    }

    public function byCategory(Request $request): JsonResponse
    {
        [$year, $month] = $this->resolveMonth($request);

        return ApiResponse::data($this->dashboardService->byCategory($request, $year, $month));
    }

    public function installmentsTotal(Request $request): JsonResponse
    {
        [$year, $month] = $this->resolveMonth($request);

        return ApiResponse::data($this->dashboardService->installmentsTotal($request, $year, $month));
    }

    public function byDay(Request $request): JsonResponse
    {
        [$year, $month] = $this->resolveMonth($request);

        return ApiResponse::data($this->dashboardService->byDay($request, $year, $month));
    }

    public function monthlyComparison(Request $request): JsonResponse
    {
        [$year, $month] = $this->resolveMonth($request);

        return ApiResponse::data($this->dashboardService->monthlyComparison($request, $year, $month)->toArray());
    }

    public function mtdComparison(Request $request): JsonResponse
    {
        [$year, $month] = $this->resolveMonth($request);

        return ApiResponse::data($this->dashboardService->mtdComparison($request, $year, $month)->toArray());
    }

    public function byPaymentMethod(Request $request): JsonResponse
    {
        [$year, $month] = $this->resolveMonth($request);

        return ApiResponse::data($this->dashboardService->byPaymentMethod($request, $year, $month));
    }

    public function weeklyExpenses(Request $request): JsonResponse
    {
        return ApiResponse::data($this->dashboardService->weeklyExpenses($request));
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
}
