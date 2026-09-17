<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Repositories\TransactionRepository;
use App\Services\DashboardService;
use Illuminate\Support\Facades\Cache;

function makeDashboardService(): DashboardService
{
    return new DashboardService(new TransactionRepository);
}

it('caches the summary payload under the documented cache key and tag format', function () {
    $user = User::factory()->create();
    $request = request();
    $request->setUserResolver(fn () => $user);

    $category = Category::factory()->for($user)->create(['type' => 'expense']);
    Transaction::factory()->for($user)->create([
        'category_id'   => $category->id,
        'type'          => 'expense',
        'amount'        => 100,
        'transacted_at' => '2026-06-10',
    ]);

    $service = makeDashboardService();
    $service->summary($request, 2026, 6);

    $cacheKey = "dashboard-summary:{$user->id}:2026-6";
    expect(Cache::tags(["dashboard-summary:{$user->id}"])->has($cacheKey))->toBeTrue();
});

it('computes chart totals for income and expense in the given month', function () {
    $user = User::factory()->create();
    $request = request();
    $request->setUserResolver(fn () => $user);

    $expenseCategory = Category::factory()->for($user)->create(['type' => 'expense']);
    $incomeCategory = Category::factory()->for($user)->create(['type' => 'income']);

    Transaction::factory()->for($user)->create(['category_id' => $expenseCategory->id, 'type' => 'expense', 'amount' => 100, 'transacted_at' => '2026-06-10']);
    Transaction::factory()->for($user)->create(['category_id' => $incomeCategory->id, 'type' => 'income', 'amount' => 500, 'transacted_at' => '2026-06-10']);

    $service = makeDashboardService();
    $chart = $service->chart($request, 2026, 6);

    expect($chart->income)->toBe(500.0);
    expect($chart->expense)->toBe(100.0);
});

it('groups expenses by main category rolling up subcategories and capping to top 10 plus Outros', function () {
    $user = User::factory()->create();
    $request = request();
    $request->setUserResolver(fn () => $user);

    $root = Category::factory()->for($user)->create(['type' => 'expense', 'name' => 'Moradia']);
    $child = Category::factory()->for($user)->create(['type' => 'expense', 'name' => 'Aluguel', 'parent_id' => $root->id]);

    Transaction::factory()->for($user)->create(['category_id' => $child->id, 'type' => 'expense', 'amount' => 100, 'transacted_at' => '2026-06-10']);
    Transaction::factory()->for($user)->create(['category_id' => $root->id, 'type' => 'expense', 'amount' => 50, 'transacted_at' => '2026-06-12']);

    $service = makeDashboardService();
    $result = $service->byCategory($request, 2026, 6);

    expect($result)->toHaveCount(1);
    expect($result[0]['name'])->toBe('Moradia');
    expect($result[0]['value'])->toBe(150.0);
});
