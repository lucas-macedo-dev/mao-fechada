<?php

use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('denies unauthenticated access to finance endpoints', function () {
    $response = $this->getJson('/api/v1/transactions');

    $response->assertStatus(401)
        ->assertJsonPath('error.type', 'authentication_error');
});

it('denies cross-user resource access', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();

    $category = Category::factory()->for($owner)->create([
        'type' => 'expense',
    ]);
    $transaction = Transaction::factory()->for($owner)->create([
        'category_id' => $category->id,
        'type' => 'expense',
    ]);

    Sanctum::actingAs($intruder);

    $response = $this->getJson("/api/v1/transactions/{$transaction->id}");

    $response->assertStatus(403)
        ->assertJsonPath('error.type', 'authorization_error');
});

it('validates transaction payload', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/transactions', [
        'amount' => 0,
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('error.type', 'validation_error')
        ->assertJsonStructure([
            'error' => ['type', 'message', 'details'],
        ]);
});

it('returns monthly summary totals and variance', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $expenseCategory = Category::factory()->for($user)->create([
        'name' => 'Food',
        'type' => 'expense',
    ]);
    $incomeCategory = Category::factory()->for($user)->create([
        'name' => 'Salary',
        'type' => 'income',
    ]);

    Budget::factory()->for($user)->create([
        'category_id' => $expenseCategory->id,
        'year' => 2026,
        'month' => 6,
        'amount' => 500,
    ]);

    Transaction::factory()->for($user)->create([
        'category_id' => $expenseCategory->id,
        'type' => 'expense',
        'amount' => 300,
        'transacted_at' => '2026-06-10',
    ]);

    Transaction::factory()->for($user)->create([
        'category_id' => $incomeCategory->id,
        'type' => 'income',
        'amount' => 2000,
        'transacted_at' => '2026-06-05',
    ]);

    $response = $this->getJson('/api/v1/summaries/monthly?year=2026&month=6');

    $response->assertOk()
        ->assertJsonPath('data.income_total', 2000)
        ->assertJsonPath('data.expense_total', 300)
        ->assertJsonPath('data.net_balance', 1700);
});
