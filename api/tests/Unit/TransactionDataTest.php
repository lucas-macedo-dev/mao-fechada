<?php

declare(strict_types=1);

use App\DataTransferObjects\Output\TransactionData;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;

it('maps a transaction model to an array without exposing user_id', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['type' => 'expense']);
    $transaction = Transaction::factory()->for($user)->create([
        'category_id' => $category->id,
        'type'        => 'expense',
        'amount'      => 150,
    ]);
    $transaction->load('category');

    $array = TransactionData::fromModel($transaction)->toArray();

    expect($array)->not->toHaveKey('user_id');
    expect($array['amount'])->toBe('150.00');
    expect($array['category'])->not->toHaveKey('user_id');
});
