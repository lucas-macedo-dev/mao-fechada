<?php

use App\Models\Category;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;

it('lists the authenticated user\'s recurring rules with active ones first', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['type' => 'expense']);
    $cancelled = RecurringTransaction::factory()->for($user)->create([
        'category_id' => $category->id,
        'type'        => 'expense',
        'status'      => 'cancelled',
    ]);
    $active = RecurringTransaction::factory()->for($user)->create([
        'category_id' => $category->id,
        'type'        => 'expense',
        'status'      => 'active',
    ]);
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/recurring-transactions');

    $response->assertStatus(200);
    $ids = collect($response->json('data'))->pluck('id');

    expect($ids->first())->toBe($active->id);
    expect($ids->get(1))->toBe($cancelled->id);
});

it('cancels an owned active recurring rule', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['type' => 'expense']);
    $rule = RecurringTransaction::factory()->for($user)->create([
        'category_id' => $category->id,
        'type'        => 'expense',
        'status'      => 'active',
    ]);
    Sanctum::actingAs($user);

    $response = $this->postJson("/api/v1/recurring-transactions/{$rule->id}/cancel");

    $response->assertStatus(200)
        ->assertJsonPath('data.status', 'cancelled');

    expect($rule->fresh()->status)->toBe('cancelled');
});

it('rejects cancelling another user\'s recurring rule', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $category = Category::factory()->for($owner)->create(['type' => 'expense']);
    $rule = RecurringTransaction::factory()->for($owner)->create([
        'category_id' => $category->id,
        'type'        => 'expense',
        'status'      => 'active',
    ]);
    Sanctum::actingAs($intruder);

    $response = $this->postJson("/api/v1/recurring-transactions/{$rule->id}/cancel");

    $response->assertStatus(403);
    expect($rule->fresh()->status)->toBe('active');
});

it('no-ops when cancelling an already-cancelled rule', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['type' => 'expense']);
    $rule = RecurringTransaction::factory()->for($user)->create([
        'category_id' => $category->id,
        'type'        => 'expense',
        'status'      => 'cancelled',
    ]);
    Sanctum::actingAs($user);

    $response = $this->postJson("/api/v1/recurring-transactions/{$rule->id}/cancel");

    $response->assertStatus(200)
        ->assertJsonPath('data.status', 'cancelled');
});

it('generates exactly one transaction per due active rule and skips cancelled ones', function () {
    Carbon::setTestNow('2026-09-15');

    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['type' => 'expense']);

    $due = RecurringTransaction::factory()->for($user)->create([
        'category_id'       => $category->id,
        'type'              => 'expense',
        'status'            => 'active',
        'day_of_month'      => 10,
        'last_generated_at' => '2026-08-10',
    ]);
    $notYetDue = RecurringTransaction::factory()->for($user)->create([
        'category_id'       => $category->id,
        'type'              => 'expense',
        'status'            => 'active',
        'day_of_month'      => 20,
        'last_generated_at' => '2026-08-20',
    ]);
    $cancelled = RecurringTransaction::factory()->for($user)->create([
        'category_id'       => $category->id,
        'type'              => 'expense',
        'status'            => 'cancelled',
        'day_of_month'      => 5,
        'last_generated_at' => '2026-08-05',
    ]);

    $this->artisan('transactions:relaunch-recurring');

    expect($due->fresh()->last_generated_at->format('Y-m-d'))->toBe('2026-09-10');
    expect(Transaction::query()->where('recurring_transaction_id', $due->id)->count())->toBe(1);
    expect(Transaction::query()->where('recurring_transaction_id', $notYetDue->id)->count())->toBe(0);
    expect(Transaction::query()->where('recurring_transaction_id', $cancelled->id)->count())->toBe(0);

    Carbon::setTestNow();
});

it('is idempotent per cycle when run more than once', function () {
    Carbon::setTestNow('2026-09-15');

    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['type' => 'expense']);
    $rule = RecurringTransaction::factory()->for($user)->create([
        'category_id'       => $category->id,
        'type'              => 'expense',
        'status'            => 'active',
        'day_of_month'      => 10,
        'last_generated_at' => '2026-08-10',
    ]);

    $this->artisan('transactions:relaunch-recurring');
    $this->artisan('transactions:relaunch-recurring');

    expect(Transaction::query()->where('recurring_transaction_id', $rule->id)->count())->toBe(1);

    Carbon::setTestNow();
});

it('clamps generation to the last day of a shorter month', function () {
    Carbon::setTestNow('2026-09-30');

    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['type' => 'expense']);
    $rule = RecurringTransaction::factory()->for($user)->create([
        'category_id'       => $category->id,
        'type'              => 'expense',
        'status'            => 'active',
        'day_of_month'      => 31,
        'last_generated_at' => '2026-08-31',
    ]);

    $this->artisan('transactions:relaunch-recurring');

    $transaction = Transaction::query()->where('recurring_transaction_id', $rule->id)->firstOrFail();
    expect($transaction->transacted_at->format('Y-m-d'))->toBe('2026-09-30');

    Carbon::setTestNow();
});

it('flushes the dashboard summary cache tag when relaunching', function () {
    Carbon::setTestNow('2026-09-15');

    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['type' => 'expense']);
    RecurringTransaction::factory()->for($user)->create([
        'category_id'       => $category->id,
        'type'              => 'expense',
        'status'            => 'active',
        'day_of_month'      => 10,
        'last_generated_at' => '2026-08-10',
    ]);

    Cache::tags(["dashboard-summary:{$user->id}"])->put('summary', ['cached' => true], 60);

    $this->artisan('transactions:relaunch-recurring');

    expect(Cache::tags(["dashboard-summary:{$user->id}"])->get('summary'))->toBeNull();

    Carbon::setTestNow();
});

it('excludes a cancelled rule from the next relaunch run', function () {
    Carbon::setTestNow('2026-09-15');

    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['type' => 'expense']);
    $rule = RecurringTransaction::factory()->for($user)->create([
        'category_id'       => $category->id,
        'type'              => 'expense',
        'status'            => 'active',
        'day_of_month'      => 10,
        'last_generated_at' => '2026-08-10',
    ]);
    Sanctum::actingAs($user);

    $this->postJson("/api/v1/recurring-transactions/{$rule->id}/cancel")->assertStatus(200);

    $this->artisan('transactions:relaunch-recurring');

    expect(Transaction::query()->where('recurring_transaction_id', $rule->id)->count())->toBe(0);

    Carbon::setTestNow();
});
