<?php

declare(strict_types=1);

use App\DataTransferObjects\Input\CreateBudgetData;
use App\Models\Category;
use App\Models\User;
use App\Repositories\BudgetRepository;
use App\Repositories\CategoryRepository;
use App\Services\BudgetService;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

function makeBudgetService(): BudgetService
{
    return new BudgetService(new BudgetRepository, new CategoryRepository);
}

it('creates a budget for an owned expense category', function () {
    $user = User::factory()->create();
    $request = request();
    $request->setUserResolver(fn () => $user);

    $category = Category::factory()->for($user)->create(['type' => 'expense']);

    $service = makeBudgetService();
    $data = new CreateBudgetData($category->id, 2026, 6, 500.0);

    $result = $service->upsert($request, $data);

    expect($result->categoryId)->toBe($category->id);
    expect($result->amount)->toBe('500.00');
});

it('rejects budgets for income categories', function () {
    $user = User::factory()->create();
    $request = request();
    $request->setUserResolver(fn () => $user);

    $category = Category::factory()->for($user)->create(['type' => 'income']);

    $service = makeBudgetService();
    $data = new CreateBudgetData($category->id, 2026, 6, 500.0);

    expect(fn () => $service->upsert($request, $data))->toThrow(ValidationException::class);
});

it('rejects budgets for another users category', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $request = request();
    $request->setUserResolver(fn () => $intruder);

    $category = Category::factory()->for($owner)->create(['type' => 'expense']);

    $service = makeBudgetService();
    $data = new CreateBudgetData($category->id, 2026, 6, 500.0);

    expect(fn () => $service->upsert($request, $data))->toThrow(HttpException::class);
});
