<?php

declare(strict_types=1);

use App\DataTransferObjects\Output\RecurringTransactionData;
use App\Models\Category;
use App\Models\RecurringTransaction;
use App\Models\User;

it('maps a rule to an array without exposing user_id, handling a null last_generated_at', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['type' => 'expense']);
    $rule = RecurringTransaction::factory()->for($user)->create([
        'category_id'       => $category->id,
        'last_generated_at' => null,
    ]);
    $rule->load('category');

    $array = RecurringTransactionData::fromModel($rule)->toArray();

    expect($array)->not->toHaveKey('user_id');
    expect($array['last_generated_at'])->toBeNull();
    expect($array['category'])->not->toHaveKey('user_id');
});
