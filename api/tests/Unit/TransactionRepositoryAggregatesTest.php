<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Repositories\TransactionRepository;

it('sums transactions by type for a given month', function () {
    $user = User::factory()->create();
    $expense = Category::factory()->for($user)->create(['type' => 'expense']);
    $income = Category::factory()->for($user)->create(['type' => 'income']);

    Transaction::factory()->for($user)->create(['category_id' => $expense->id, 'type' => 'expense', 'amount' => 100, 'transacted_at' => '2026-06-10']);
    Transaction::factory()->for($user)->create(['category_id' => $income->id, 'type' => 'income', 'amount' => 500, 'transacted_at' => '2026-06-10']);
    Transaction::factory()->for($user)->create(['category_id' => $expense->id, 'type' => 'expense', 'amount' => 999, 'transacted_at' => '2026-05-10']);

    $repository = new TransactionRepository;

    expect($repository->sumByTypeForMonth($user->id, 2026, 6, 'expense'))->toBe(100.0);
    expect($repository->sumByTypeForMonth($user->id, 2026, 6, 'income'))->toBe(500.0);
});

it('sums transactions through a cutoff day, optionally installments only', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['type' => 'expense']);

    Transaction::factory()->for($user)->create(['category_id' => $category->id, 'type' => 'expense', 'amount' => 40, 'transacted_at' => '2026-06-10', 'installment_group_id' => 'g1']);
    Transaction::factory()->for($user)->create(['category_id' => $category->id, 'type' => 'expense', 'amount' => 60, 'transacted_at' => '2026-06-12']);
    Transaction::factory()->for($user)->create(['category_id' => $category->id, 'type' => 'expense', 'amount' => 999, 'transacted_at' => '2026-06-20']);

    $repository = new TransactionRepository;

    expect($repository->sumByTypeThroughDay($user->id, 2026, 6, 15, 'expense'))->toBe(100.0);
    expect($repository->sumByTypeThroughDay($user->id, 2026, 6, 15, 'expense', true))->toBe(40.0);
});

it('finds transactions within an explicit date range for a user', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['type' => 'expense']);

    Transaction::factory()->for($user)->create(['category_id' => $category->id, 'type' => 'expense', 'transacted_at' => '2026-06-15']);
    Transaction::factory()->for($user)->create(['category_id' => $category->id, 'type' => 'expense', 'transacted_at' => '2026-06-25']);

    $repository = new TransactionRepository;
    $result = $repository->forDateRangeForUser($user->id, 'expense', '2026-06-14', '2026-06-20');

    expect($result)->toHaveCount(1);
});
