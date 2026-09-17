<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Repositories\CategoryRepository;

it('returns root categories with their children for a user', function () {
    $user = User::factory()->create();
    $root = Category::factory()->for($user)->create(['type' => 'expense', 'parent_id' => null]);
    Category::factory()->for($user)->create(['type' => 'expense', 'parent_id' => $root->id]);

    $repository = new CategoryRepository;
    $tree = $repository->treeForUser($user->id);

    expect($tree)->toHaveCount(1);
    expect($tree->first()->children)->toHaveCount(1);
});

it('lists all categories for a user ordered by name with parent loaded', function () {
    $user = User::factory()->create();
    Category::factory()->for($user)->create(['type' => 'expense', 'name' => 'Zebra']);
    Category::factory()->for($user)->create(['type' => 'expense', 'name' => 'Apple']);

    $repository = new CategoryRepository;
    $categories = $repository->listForUser($user->id);

    expect($categories->pluck('name')->all())->toBe(['Apple', 'Zebra']);
    expect($categories->first()->relationLoaded('parent'))->toBeTrue();
});

it('detects when a category has transactions', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['type' => 'expense']);

    $repository = new CategoryRepository;

    expect($repository->hasTransactions($category))->toBeFalse();

    Transaction::factory()->for($user)->create([
        'category_id' => $category->id,
        'type'        => 'expense',
    ]);

    expect($repository->hasTransactions($category))->toBeTrue();
});

it('detects mismatched children types', function () {
    $user = User::factory()->create();
    $parent = Category::factory()->for($user)->create(['type' => 'expense']);
    Category::factory()->for($user)->create(['type' => 'expense', 'parent_id' => $parent->id]);

    $repository = new CategoryRepository;

    expect($repository->hasMismatchedChildren($parent, 'expense'))->toBeFalse();
    expect($repository->hasMismatchedChildren($parent, 'income'))->toBeTrue();
});
