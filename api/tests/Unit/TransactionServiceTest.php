<?php

declare(strict_types=1);

use App\DataTransferObjects\Input\CreateTransactionData;
use App\DataTransferObjects\Input\UpdateTransactionData;
use App\Models\Category;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\User;
use App\Repositories\CategoryRepository;
use App\Repositories\RecurringTransactionRepository;
use App\Repositories\TransactionRepository;
use App\Services\TransactionService;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

function makeTransactionService(): TransactionService
{
    return new TransactionService(
        new TransactionRepository,
        new CategoryRepository,
        new RecurringTransactionRepository,
    );
}

function makeCreateTransactionData(array $overrides = []): CreateTransactionData
{
    return CreateTransactionData::fromArray(array_merge([
        'type'           => 'expense',
        'payment_method' => 'pix',
        'amount'         => 100,
        'transacted_at'  => '2026-06-15',
    ], $overrides));
}

it('rejects a transaction whose type does not match its category', function () {
    $user = User::factory()->create();
    $request = request();
    $request->setUserResolver(fn () => $user);

    $category = Category::factory()->for($user)->create(['type' => 'income']);

    $service = makeTransactionService();
    $data = makeCreateTransactionData(['category_id' => $category->id, 'type' => 'expense']);

    expect(fn () => $service->create($request, $data))->toThrow(ValidationException::class);
});

it('creates a recurring rule and its first transaction together', function () {
    $user = User::factory()->create();
    $request = request();
    $request->setUserResolver(fn () => $user);

    $category = Category::factory()->for($user)->create(['type' => 'expense']);

    $service = makeTransactionService();
    $data = makeCreateTransactionData(['category_id' => $category->id, 'recurring' => true]);

    $result = $service->create($request, $data);

    expect(RecurringTransaction::query()->count())->toBe(1);
    expect($result->recurringTransactionId)->not->toBeNull();
});

it('creates one transaction per installment with sequential months', function () {
    $user = User::factory()->create();
    $request = request();
    $request->setUserResolver(fn () => $user);

    $category = Category::factory()->for($user)->create(['type' => 'expense']);

    $service = makeTransactionService();
    $data = makeCreateTransactionData([
        'category_id'        => $category->id,
        'installment_number' => 1,
        'installment_total'  => 3,
    ]);

    $result = $service->create($request, $data);

    expect($result)->toHaveCount(3);
    expect(Transaction::query()->count())->toBe(3);
    expect(Transaction::query()->pluck('installment_group_id')->unique())->toHaveCount(1);
});

it('cascades an update to every transaction in an installment group', function () {
    $user = User::factory()->create();
    $request = request();
    $request->setUserResolver(fn () => $user);

    $category = Category::factory()->for($user)->create(['type' => 'expense']);
    $service = makeTransactionService();

    $created = $service->create($request, makeCreateTransactionData([
        'category_id'        => $category->id,
        'installment_number' => 1,
        'installment_total'  => 2,
    ]));

    $firstId = $created[0]['id'];
    $service->update($request, $firstId, UpdateTransactionData::fromArray(['amount' => 999]));

    expect(Transaction::query()->pluck('amount')->unique()->map(fn ($a) => (float) $a)->all())->toBe([999.0]);
});

it('rejects converting an already-recurring transaction', function () {
    $user = User::factory()->create();
    $request = request();
    $request->setUserResolver(fn () => $user);

    $category = Category::factory()->for($user)->create(['type' => 'expense']);
    $rule = RecurringTransaction::factory()->for($user)->create(['category_id' => $category->id]);
    $transaction = Transaction::factory()->for($user)->create([
        'category_id'              => $category->id,
        'type'                     => 'expense',
        'recurring_transaction_id' => $rule->id,
    ]);

    $service = makeTransactionService();

    expect(fn () => $service->convertToRecurring($request, $transaction->id))->toThrow(ValidationException::class);
});

it('rejects converting an installment-linked transaction', function () {
    $user = User::factory()->create();
    $request = request();
    $request->setUserResolver(fn () => $user);

    $category = Category::factory()->for($user)->create(['type' => 'expense']);
    $transaction = Transaction::factory()->for($user)->create([
        'category_id'          => $category->id,
        'type'                 => 'expense',
        'installment_group_id' => (string) Str::uuid(),
        'installment_number'   => 1,
        'installment_total'    => 2,
    ]);

    $service = makeTransactionService();

    expect(fn () => $service->convertToRecurring($request, $transaction->id))->toThrow(ValidationException::class);
});

it('rejects operating on a transaction owned by another user', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $request = request();
    $request->setUserResolver(fn () => $intruder);

    $category = Category::factory()->for($owner)->create(['type' => 'expense']);
    $transaction = Transaction::factory()->for($owner)->create([
        'category_id' => $category->id,
        'type'        => 'expense',
    ]);

    $service = makeTransactionService();

    expect(fn () => $service->find($request, $transaction->id))->toThrow(HttpException::class);
});
