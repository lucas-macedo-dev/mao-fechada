<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Models\Category;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BudgetController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'between:1,12'],
        ]);

        $budgets = Budget::query()
            ->with('category')
            ->where('user_id', $request->user()->id)
            ->where('year', $validated['year'])
            ->where('month', $validated['month'])
            ->orderBy('category_id')
            ->get();

        return ApiResponse::data($budgets);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'between:1,12'],
            'amount' => ['required', 'numeric', 'gt:0'],
        ]);

        $category = Category::query()->findOrFail($validated['category_id']);
        $this->ensureOwnership($request, $category->user_id);

        if ($category->type !== 'expense') {
            throw ValidationException::withMessages([
                'category_id' => ['Budgets are only allowed for expense categories.'],
            ]);
        }

        $budget = Budget::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'category_id' => $validated['category_id'],
                'year' => $validated['year'],
                'month' => $validated['month'],
            ],
            [
                'amount' => $validated['amount'],
            ],
        );

        return ApiResponse::data($budget->load('category'));
    }

    private function ensureOwnership(Request $request, int $ownerUserId): void
    {
        if ((int) $request->user()->id !== $ownerUserId) {
            abort(403, 'You are not allowed to access this resource.');
        }
    }
}
