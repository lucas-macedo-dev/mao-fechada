<?php

declare(strict_types=1);

namespace App\Services;

use App\DataTransferObjects\Input\CreateBudgetData;
use App\DataTransferObjects\Output\BudgetData;
use App\Repositories\BudgetRepository;
use App\Repositories\CategoryRepository;
use App\Services\Concerns\AuthorizesOwnership;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BudgetService
{
    use AuthorizesOwnership;

    public function __construct(
        private readonly BudgetRepository $budgets,
        private readonly CategoryRepository $categories,
    ) {}

    public function listForMonth(Request $request, int $year, int $month): array
    {
        $budgets = $this->budgets->listForUserAndMonth($request->user()->id, $year, $month);

        return BudgetData::collection($budgets);
    }

    public function upsert(Request $request, CreateBudgetData $data): BudgetData
    {
        $category = $this->categories->findOrFail($data->categoryId);
        $this->ensureOwnership($request, $category->user_id);

        if ($category->type !== 'expense') {
            throw ValidationException::withMessages([
                'category_id' => ['Budgets are only allowed for expense categories.'],
            ]);
        }

        $budget = $this->budgets->upsert(
            $request->user()->id,
            $data->categoryId,
            $data->year,
            $data->month,
            $data->amount,
        );

        return BudgetData::fromModel($budget);
    }
}
