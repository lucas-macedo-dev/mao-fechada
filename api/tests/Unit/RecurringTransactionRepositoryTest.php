<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\RecurringTransaction;
use App\Models\User;
use App\Repositories\RecurringTransactionRepository;

it('lists rules for a user with active first and category loaded', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['type' => 'expense']);

    $cancelled = RecurringTransaction::factory()->for($user)->create([
        'category_id' => $category->id,
        'status'      => 'cancelled',
    ]);
    $active = RecurringTransaction::factory()->for($user)->create([
        'category_id' => $category->id,
        'status'      => 'active',
    ]);

    $repository = new RecurringTransactionRepository;
    $rules = $repository->listForUser($user->id);

    expect($rules->first()->id)->toBe($active->id);
    expect($rules->get(1)->id)->toBe($cancelled->id);
    expect($rules->first()->relationLoaded('category'))->toBeTrue();
});

it('cancels an active rule but no-ops on an already-cancelled rule', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['type' => 'expense']);
    $rule = RecurringTransaction::factory()->for($user)->create([
        'category_id' => $category->id,
        'status'      => 'active',
    ]);

    $repository = new RecurringTransactionRepository;
    $cancelled = $repository->cancel($rule);
    expect($cancelled->status)->toBe('cancelled');

    $noop = $repository->cancel($cancelled);
    expect($noop->status)->toBe('cancelled');
});
