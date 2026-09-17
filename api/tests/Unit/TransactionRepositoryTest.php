<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Repositories\TransactionRepository;

it('paginates transactions for a user applying filters', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['type' => 'expense']);

    Transaction::factory()->for($user)->create([
        'category_id'    => $category->id,
        'type'           => 'expense',
        'payment_method' => 'pix',
        'transacted_at'  => '2026-06-15',
    ]);
    Transaction::factory()->for($user)->create([
        'category_id'    => $category->id,
        'type'           => 'expense',
        'payment_method' => 'cash',
        'transacted_at'  => '2026-05-15',
    ]);

    $repository = new TransactionRepository;
    $paginator = $repository->paginateForUser($user->id, ['month' => '2026-06'], 20);

    expect($paginator->total())->toBe(1);
    expect($paginator->items()[0]->relationLoaded('category'))->toBeTrue();
});

it('cascades updates to every transaction in an installment group', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['type' => 'expense']);

    $repository = new TransactionRepository;
    $groupId = $repository->newInstallmentGroupId();

    $repository->createForUser($user->id, [
        'category_id'          => $category->id,
        'type'                 => 'expense',
        'payment_method'       => 'pix',
        'amount'               => 100,
        'transacted_at'        => '2026-06-15',
        'installment_group_id' => $groupId,
        'installment_number'   => 1,
        'installment_total'    => 2,
    ]);
    $repository->createForUser($user->id, [
        'category_id'          => $category->id,
        'type'                 => 'expense',
        'payment_method'       => 'pix',
        'amount'               => 100,
        'transacted_at'        => '2026-07-15',
        'installment_group_id' => $groupId,
        'installment_number'   => 2,
        'installment_total'    => 2,
    ]);

    $repository->updateCascadeByInstallmentGroup($user->id, $groupId, ['notes' => 'updated']);

    expect(Transaction::query()->where('installment_group_id', $groupId)->pluck('notes')->unique()->all())
        ->toBe(['updated']);
});

it('deletes every transaction in an installment group', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['type' => 'expense']);

    $repository = new TransactionRepository;
    $groupId = $repository->newInstallmentGroupId();

    $repository->createForUser($user->id, [
        'category_id'          => $category->id, 'type' => 'expense', 'payment_method' => 'pix',
        'amount'               => 100, 'transacted_at' => '2026-06-15',
        'installment_group_id' => $groupId, 'installment_number' => 1, 'installment_total' => 2,
    ]);
    $repository->createForUser($user->id, [
        'category_id'          => $category->id, 'type' => 'expense', 'payment_method' => 'pix',
        'amount'               => 100, 'transacted_at' => '2026-07-15',
        'installment_group_id' => $groupId, 'installment_number' => 2, 'installment_total' => 2,
    ]);

    $repository->deleteByInstallmentGroup($user->id, $groupId);

    expect(Transaction::query()->where('installment_group_id', $groupId)->count())->toBe(0);
});
