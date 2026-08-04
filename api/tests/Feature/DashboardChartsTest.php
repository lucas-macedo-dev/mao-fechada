<?php

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;

afterEach(function () {
    Carbon::setTestNow();
});

it('returns monthly income vs expense comparison for the anchor month and 2 previous months', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $expenseCategory = Category::factory()->for($user)->create(['type' => 'expense']);
    $incomeCategory = Category::factory()->for($user)->create(['type' => 'income']);

    Transaction::factory()->for($user)->create([
        'category_id' => $incomeCategory->id, 'type' => 'income', 'amount' => 1000, 'transacted_at' => '2026-06-10',
    ]);
    Transaction::factory()->for($user)->create([
        'category_id' => $expenseCategory->id, 'type' => 'expense', 'amount' => 400, 'transacted_at' => '2026-06-12',
    ]);
    Transaction::factory()->for($user)->create([
        'category_id' => $expenseCategory->id, 'type' => 'expense', 'amount' => 200, 'transacted_at' => '2026-04-05',
    ]);
    // 2026-05 intentionally left with no transactions.

    $response = $this->getJson('/api/v1/dashboard/monthly-comparison?month=2026-06');

    $response->assertOk()
        ->assertJsonPath('data.months.0.month', '2026-04')
        ->assertJsonPath('data.months.0.income', 0)
        ->assertJsonPath('data.months.0.expense', 200)
        ->assertJsonPath('data.months.1.month', '2026-05')
        ->assertJsonPath('data.months.1.income', 0)
        ->assertJsonPath('data.months.1.expense', 0)
        ->assertJsonPath('data.months.2.month', '2026-06')
        ->assertJsonPath('data.months.2.income', 1000)
        ->assertJsonPath('data.months.2.expense', 400);
});

it('computes expenses-to-date vs previous month using todays day when viewing the current month', function () {
    Carbon::setTestNow(Carbon::create(2026, 6, 15));

    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $category = Category::factory()->for($user)->create(['type' => 'expense']);

    Transaction::factory()->for($user)->create(['category_id' => $category->id, 'type' => 'expense', 'amount' => 100, 'transacted_at' => '2026-06-10']);
    Transaction::factory()->for($user)->create(['category_id' => $category->id, 'type' => 'expense', 'amount' => 50, 'transacted_at' => '2026-06-20']);
    Transaction::factory()->for($user)->create(['category_id' => $category->id, 'type' => 'expense', 'amount' => 80, 'transacted_at' => '2026-05-10']);
    Transaction::factory()->for($user)->create(['category_id' => $category->id, 'type' => 'expense', 'amount' => 999, 'transacted_at' => '2026-05-20']);

    $response = $this->getJson('/api/v1/dashboard/expenses-mtd-comparison?month=2026-06');

    $response->assertOk()
        ->assertJsonPath('data.expense.current.month', '2026-06')
        ->assertJsonPath('data.expense.current.through_day', 15)
        ->assertJsonPath('data.expense.current.total', 100)
        ->assertJsonPath('data.expense.previous.month', '2026-05')
        ->assertJsonPath('data.expense.previous.through_day', 15)
        ->assertJsonPath('data.expense.previous.total', 80)
        ->assertJsonPath('data.expense.change_percent', 25);
});

it('compares full months when a past, fully-elapsed month is selected', function () {
    Carbon::setTestNow(Carbon::create(2026, 7, 10));

    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $category = Category::factory()->for($user)->create(['type' => 'expense']);

    Transaction::factory()->for($user)->create(['category_id' => $category->id, 'type' => 'expense', 'amount' => 500, 'transacted_at' => '2026-04-30']);
    Transaction::factory()->for($user)->create(['category_id' => $category->id, 'type' => 'expense', 'amount' => 250, 'transacted_at' => '2026-03-31']);

    $response = $this->getJson('/api/v1/dashboard/expenses-mtd-comparison?month=2026-04');

    $response->assertOk()
        ->assertJsonPath('data.expense.current.through_day', 30)
        ->assertJsonPath('data.expense.current.total', 500)
        ->assertJsonPath('data.expense.previous.month', '2026-03')
        ->assertJsonPath('data.expense.previous.through_day', 30)
        ->assertJsonPath('data.expense.previous.total', 0)
        ->assertJsonPath('data.expense.change_percent', null);
});

it('clamps the previous months cutoff day when it is shorter than the current cutoff day', function () {
    Carbon::setTestNow(Carbon::create(2026, 3, 31));

    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $category = Category::factory()->for($user)->create(['type' => 'expense']);

    Transaction::factory()->for($user)->create(['category_id' => $category->id, 'type' => 'expense', 'amount' => 300, 'transacted_at' => '2026-03-31']);
    Transaction::factory()->for($user)->create(['category_id' => $category->id, 'type' => 'expense', 'amount' => 150, 'transacted_at' => '2026-02-28']);

    $response = $this->getJson('/api/v1/dashboard/expenses-mtd-comparison?month=2026-03');

    $response->assertOk()
        ->assertJsonPath('data.expense.current.through_day', 31)
        ->assertJsonPath('data.expense.previous.month', '2026-02')
        ->assertJsonPath('data.expense.previous.through_day', 28)
        ->assertJsonPath('data.expense.previous.total', 150);
});

it('computes independent income, balance and installments mtd comparisons alongside expense', function () {
    Carbon::setTestNow(Carbon::create(2026, 6, 15));

    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $expenseCategory = Category::factory()->for($user)->create(['type' => 'expense']);
    $incomeCategory = Category::factory()->for($user)->create(['type' => 'income']);

    // Current month, through day 15: income 1000, expense 100 (of which 40 is an installment), balance 900.
    Transaction::factory()->for($user)->create(['category_id' => $incomeCategory->id, 'type' => 'income', 'amount' => 1000, 'transacted_at' => '2026-06-10']);
    Transaction::factory()->for($user)->create(['category_id' => $expenseCategory->id, 'type' => 'expense', 'amount' => 60, 'transacted_at' => '2026-06-10']);
    Transaction::factory()->for($user)->create(['category_id' => $expenseCategory->id, 'type' => 'expense', 'amount' => 40, 'transacted_at' => '2026-06-12', 'installment_group_id' => 'grp-1']);
    // Excluded: after the cutoff day.
    Transaction::factory()->for($user)->create(['category_id' => $expenseCategory->id, 'type' => 'expense', 'amount' => 999, 'transacted_at' => '2026-06-20']);

    // Previous month (May), through day 15: income 500, expense 50 (of which 10 is an installment), balance 450.
    Transaction::factory()->for($user)->create(['category_id' => $incomeCategory->id, 'type' => 'income', 'amount' => 500, 'transacted_at' => '2026-05-05']);
    Transaction::factory()->for($user)->create(['category_id' => $expenseCategory->id, 'type' => 'expense', 'amount' => 40, 'transacted_at' => '2026-05-05']);
    Transaction::factory()->for($user)->create(['category_id' => $expenseCategory->id, 'type' => 'expense', 'amount' => 10, 'transacted_at' => '2026-05-14', 'installment_group_id' => 'grp-0']);

    $response = $this->getJson('/api/v1/dashboard/expenses-mtd-comparison?month=2026-06');

    $response->assertOk()
        ->assertJsonPath('data.income.current.total', 1000)
        ->assertJsonPath('data.income.previous.total', 500)
        ->assertJsonPath('data.income.change_percent', 100)
        ->assertJsonPath('data.expense.current.total', 100)
        ->assertJsonPath('data.expense.previous.total', 50)
        ->assertJsonPath('data.expense.change_percent', 100)
        ->assertJsonPath('data.balance.current.total', 900)
        ->assertJsonPath('data.balance.previous.total', 450)
        ->assertJsonPath('data.balance.change_percent', 100)
        ->assertJsonPath('data.installments.current.total', 40)
        ->assertJsonPath('data.installments.previous.total', 10)
        ->assertJsonPath('data.installments.change_percent', 300);
});

it('returns expenses grouped by canonical payment method for the selected month', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $expenseCategory = Category::factory()->for($user)->create(['type' => 'expense']);
    $incomeCategory = Category::factory()->for($user)->create(['type' => 'income']);

    Transaction::factory()->for($user)->create(['category_id' => $expenseCategory->id, 'type' => 'expense', 'payment_method' => 'pix', 'amount' => 100, 'transacted_at' => '2026-06-05']);
    Transaction::factory()->for($user)->create(['category_id' => $expenseCategory->id, 'type' => 'expense', 'payment_method' => 'pix', 'amount' => 50, 'transacted_at' => '2026-06-06']);
    Transaction::factory()->for($user)->create(['category_id' => $expenseCategory->id, 'type' => 'expense', 'payment_method' => 'cash', 'amount' => 30, 'transacted_at' => '2026-06-07']);
    Transaction::factory()->for($user)->create(['category_id' => $incomeCategory->id, 'type' => 'income', 'payment_method' => 'pix', 'amount' => 1000, 'transacted_at' => '2026-06-08']);

    $response = $this->getJson('/api/v1/dashboard/by-payment-method?month=2026-06');

    $response->assertOk();

    $data = collect($response->json('data'))->keyBy('name');
    expect((float) $data->get('pix')['value'])->toBe(150.0);
    expect((float) $data->get('cash')['value'])->toBe(30.0);
    expect($data->has('bank_slip'))->toBeFalse();
});

it('returns an empty array from by-payment-method when the selected month has no expenses', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/dashboard/by-payment-method?month=2026-06');

    $response->assertOk()->assertJsonPath('data', []);
});

it('returns weekly expenses for the current calendar week, Monday through Sunday', function () {
    Carbon::setTestNow(Carbon::create(2026, 6, 17));

    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $category = Category::factory()->for($user)->create(['type' => 'expense']);

    Transaction::factory()->for($user)->create(['category_id' => $category->id, 'type' => 'expense', 'amount' => 100, 'transacted_at' => '2026-06-15']);
    Transaction::factory()->for($user)->create(['category_id' => $category->id, 'type' => 'expense', 'amount' => 40, 'transacted_at' => '2026-06-21']);
    Transaction::factory()->for($user)->create(['category_id' => $category->id, 'type' => 'expense', 'amount' => 999, 'transacted_at' => '2026-06-14']);

    $response = $this->getJson('/api/v1/dashboard/weekly-expenses');

    $response->assertOk()
        ->assertJsonPath('data.0.weekday', 1)
        ->assertJsonPath('data.0.date', '2026-06-15')
        ->assertJsonPath('data.0.expense', 100)
        ->assertJsonPath('data.1.expense', 0)
        ->assertJsonPath('data.6.weekday', 7)
        ->assertJsonPath('data.6.date', '2026-06-21')
        ->assertJsonPath('data.6.expense', 40);
});

it('scopes weekly expenses to the authenticated user', function () {
    Carbon::setTestNow(Carbon::create(2026, 6, 17));

    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $category = Category::factory()->for($owner)->create(['type' => 'expense']);

    Transaction::factory()->for($owner)->create(['category_id' => $category->id, 'type' => 'expense', 'amount' => 500, 'transacted_at' => '2026-06-15']);

    Sanctum::actingAs($intruder);

    $response = $this->getJson('/api/v1/dashboard/weekly-expenses');

    $response->assertOk()->assertJsonPath('data.0.expense', 0);
});
