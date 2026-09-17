<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DataTransferObjects\Input\CreateBudgetData;
use App\Http\Controllers\Controller;
use App\Services\BudgetService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function __construct(private readonly BudgetService $budgetService) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year'  => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'between:1,12'],
        ]);

        $budgets = $this->budgetService->listForMonth($request, (int) $validated['year'], (int) $validated['month']);

        return ApiResponse::data($budgets);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'year'        => ['required', 'integer', 'min:2000', 'max:2100'],
            'month'       => ['required', 'integer', 'between:1,12'],
            'amount'      => ['required', 'numeric', 'gt:0'],
        ]);

        $data = CreateBudgetData::fromArray($validated);
        $budget = $this->budgetService->upsert($request, $data);

        return ApiResponse::data($budget->toArray());
    }
}
