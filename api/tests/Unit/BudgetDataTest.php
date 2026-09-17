<?php

declare(strict_types=1);

use App\DataTransferObjects\Output\BudgetData;
use App\Models\Budget;
use App\Models\Category;
use App\Models\User;

it('maps a budget model to an array without exposing user_id', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['type' => 'expense']);
    $budget = Budget::factory()->for($user)->create([
        'category_id' => $category->id,
        'amount'      => 500,
    ]);
    $budget->load('category');

    $array = BudgetData::fromModel($budget)->toArray();

    expect($array)->not->toHaveKey('user_id');
    expect($array['amount'])->toBe('500.00');
    expect($array['category'])->not->toHaveKey('user_id');
});

it('omits the category key when the relation is not loaded', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['type' => 'expense']);
    $budget = Budget::factory()->for($user)->create(['category_id' => $category->id]);

    $array = BudgetData::fromModel($budget)->toArray();

    expect($array)->not->toHaveKey('category');
});
