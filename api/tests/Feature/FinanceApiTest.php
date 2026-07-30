<?php

use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

it('returns hierarchical categories when tree flag is enabled', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $root = Category::factory()->for($user)->create([
        'name' => 'Moradia',
        'type' => 'expense',
        'parent_id' => null,
    ]);

    $child = Category::factory()->for($user)->create([
        'name' => 'Aluguel',
        'type' => 'expense',
        'parent_id' => $root->id,
    ]);

    $response = $this->getJson('/api/v1/categories?tree=1');

    $response->assertOk()
        ->assertJsonPath('data.0.id', $root->id)
        ->assertJsonPath('data.0.children.0.id', $child->id);
});

it('returns paginated transactions with filters', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $expenseCategory = Category::factory()->for($user)->create([
        'type' => 'expense',
    ]);

    Transaction::factory()->for($user)->create([
        'category_id' => $expenseCategory->id,
        'type' => 'expense',
        'payment_method' => 'pix',
        'transacted_at' => '2026-06-15',
    ]);

    Transaction::factory()->for($user)->create([
        'category_id' => $expenseCategory->id,
        'type' => 'expense',
        'payment_method' => 'cash',
        'transacted_at' => '2026-05-15',
    ]);

    $response = $this->getJson('/api/v1/transactions?month=2026-06&payment_method=pix&per_page=1');

    $response->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('meta.per_page', 1)
        ->assertJsonPath('data.0.payment_method', 'pix');
});

it('rejects legacy portuguese transaction type and payment method values', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $category = Category::factory()->for($user)->create([
        'type' => 'expense',
    ]);

    $response = $this->postJson('/api/v1/transactions', [
        'category_id' => $category->id,
        'type' => 'saida',
        'payment_method' => 'dinheiro',
        'amount' => 150.50,
        'transacted_at' => '2026-06-20',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('error.type', 'validation_error')
        ->assertJsonPath('error.details.type.0', 'The selected type is invalid.')
        ->assertJsonPath('error.details.payment_method.0', 'The selected payment method is invalid.');
});

it('allows locale preference update for authenticated user', function () {
    $user = User::factory()->create(['locale' => 'pt-BR']);
    Sanctum::actingAs($user);

    $response = $this->patchJson('/api/v1/users/me/preferences', [
        'locale' => 'en',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.locale', 'en');
});

it('allows profile update for authenticated user', function () {
    $user = User::factory()->create([
        'name' => 'Old Name',
        'email' => 'old@example.com',
        'locale' => 'pt-BR',
    ]);
    Sanctum::actingAs($user);

    $response = $this->patchJson('/api/v1/users/me', [
        'name' => 'New Name',
        'email' => 'new@example.com',
        'locale' => 'en',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.name', 'New Name')
        ->assertJsonPath('data.email', 'new@example.com')
        ->assertJsonPath('data.locale', 'en');
});

it('allows profile photo upload for authenticated user', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->patch('/api/v1/users/me', [
        'profile_photo' => UploadedFile::fake()->image('avatar.png', 300, 300),
    ], [
        'Accept' => 'application/json',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.id', $user->uuid);

    $user->refresh();

    expect($user->profile_photo_path)->not()->toBeNull();
    Storage::disk('public')->assertExists($user->profile_photo_path);
});
