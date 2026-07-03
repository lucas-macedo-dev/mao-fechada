<?php

use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('does not expose user_id on transaction endpoints', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $category = Category::factory()->for($user)->create(['type' => 'expense']);

    $storeResponse = $this->postJson('/api/v1/transactions', [
        'category_id' => $category->id,
        'type' => 'expense',
        'payment_method' => 'pix',
        'amount' => 100,
        'transacted_at' => '2026-06-20',
    ]);
    $storeResponse->assertCreated();
    expect($storeResponse->json('data'))->not()->toHaveKey('user_id');

    $transaction = Transaction::query()->latest('id')->first();

    $indexResponse = $this->getJson('/api/v1/transactions');
    $indexResponse->assertOk();
    expect($indexResponse->json('data.0'))->not()->toHaveKey('user_id');

    $showResponse = $this->getJson("/api/v1/transactions/{$transaction->id}");
    $showResponse->assertOk();
    expect($showResponse->json('data'))->not()->toHaveKey('user_id');

    $updateResponse = $this->patchJson("/api/v1/transactions/{$transaction->id}", [
        'amount' => 150,
    ]);
    $updateResponse->assertOk();
    expect($updateResponse->json('data'))->not()->toHaveKey('user_id');
});

it('does not expose user_id on category endpoints', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $storeResponse = $this->postJson('/api/v1/categories', [
        'name' => 'Groceries',
        'type' => 'expense',
    ]);
    $storeResponse->assertCreated();
    expect($storeResponse->json('data'))->not()->toHaveKey('user_id');

    $category = Category::query()->latest('id')->first();

    $indexResponse = $this->getJson('/api/v1/categories');
    $indexResponse->assertOk();
    expect($indexResponse->json('data.0'))->not()->toHaveKey('user_id');

    $showResponse = $this->getJson("/api/v1/categories/{$category->id}");
    $showResponse->assertOk();
    expect($showResponse->json('data'))->not()->toHaveKey('user_id');

    $updateResponse = $this->patchJson("/api/v1/categories/{$category->id}", [
        'name' => 'Groceries Updated',
    ]);
    $updateResponse->assertOk();
    expect($updateResponse->json('data'))->not()->toHaveKey('user_id');
});

it('does not expose user_id on budget endpoints', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $category = Category::factory()->for($user)->create(['type' => 'expense']);

    $storeResponse = $this->postJson('/api/v1/budgets', [
        'category_id' => $category->id,
        'year' => 2026,
        'month' => 6,
        'amount' => 500,
    ]);
    $storeResponse->assertOk();
    expect($storeResponse->json('data'))->not()->toHaveKey('user_id');

    $indexResponse = $this->getJson('/api/v1/budgets?year=2026&month=6');
    $indexResponse->assertOk();
    expect($indexResponse->json('data.0'))->not()->toHaveKey('user_id');
});

it('does not expose user_id on dashboard endpoints', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $category = Category::factory()->for($user)->create(['type' => 'expense']);
    Transaction::factory()->for($user)->create([
        'category_id' => $category->id,
        'type' => 'expense',
        'transacted_at' => now()->format('Y-m-d'),
    ]);

    $summaryResponse = $this->getJson('/api/v1/dashboard/summary');
    $summaryResponse->assertOk();
    expect($summaryResponse->json('data.recent_transactions.0'))->not()->toHaveKey('user_id');

    $recentResponse = $this->getJson('/api/v1/dashboard/recent');
    $recentResponse->assertOk();
    expect($recentResponse->json('data.0'))->not()->toHaveKey('user_id');
});
