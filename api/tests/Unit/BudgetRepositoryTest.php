<?php

declare(strict_types=1);

use App\Models\Budget;
use App\Models\Category;
use App\Models\User;
use App\Repositories\BudgetRepository;

it('lists budgets for a user scoped to year and month with category loaded', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['type' => 'expense']);

    Budget::factory()->for($user)->create([
        'category_id' => $category->id,
        'year'        => 2026,
        'month'       => 6,
    ]);
    Budget::factory()->for($user)->create([
        'category_id' => $category->id,
        'year'        => 2026,
        'month'       => 7,
    ]);

    $repository = new BudgetRepository;
    $budgets = $repository->listForUserAndMonth($user->id, 2026, 6);

    expect($budgets)->toHaveCount(1);
    expect($budgets->first()->relationLoaded('category'))->toBeTrue();
});

it('upserts a budget for the same user/category/year/month combination', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['type' => 'expense']);

    $repository = new BudgetRepository;
    $first = $repository->upsert($user->id, $category->id, 2026, 6, 500.0);
    $second = $repository->upsert($user->id, $category->id, 2026, 6, 750.0);

    expect($first->id)->toBe($second->id);
    expect((float) $second->amount)->toBe(750.0);
    expect(Budget::query()->count())->toBe(1);
});
