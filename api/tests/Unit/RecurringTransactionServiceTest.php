<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\RecurringTransaction;
use App\Models\User;
use App\Repositories\RecurringTransactionRepository;
use App\Services\RecurringTransactionService;
use Symfony\Component\HttpKernel\Exception\HttpException;

function makeRecurringTransactionService(): RecurringTransactionService
{
    return new RecurringTransactionService(new RecurringTransactionRepository);
}

it('cancels an owned rule', function () {
    $user = User::factory()->create();
    $request = request();
    $request->setUserResolver(fn () => $user);

    $category = Category::factory()->for($user)->create(['type' => 'expense']);
    $rule = RecurringTransaction::factory()->for($user)->create([
        'category_id' => $category->id,
        'status'      => 'active',
    ]);

    $service = makeRecurringTransactionService();
    $result = $service->cancel($request, $rule->id);

    expect($result->status)->toBe('cancelled');
});

it('rejects cancelling a rule owned by another user', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $request = request();
    $request->setUserResolver(fn () => $intruder);

    $category = Category::factory()->for($owner)->create(['type' => 'expense']);
    $rule = RecurringTransaction::factory()->for($owner)->create([
        'category_id' => $category->id,
        'status'      => 'active',
    ]);

    $service = makeRecurringTransactionService();

    expect(fn () => $service->cancel($request, $rule->id))->toThrow(HttpException::class);
});
