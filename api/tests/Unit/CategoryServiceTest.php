<?php

declare(strict_types=1);

use App\DataTransferObjects\Input\CreateCategoryData;
use App\DataTransferObjects\Input\UpdateCategoryData;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Repositories\CategoryRepository;
use App\Services\CategoryService;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;

function makeCategoryService(): CategoryService
{
    return new CategoryService(new CategoryRepository);
}

it('creates a root category for the authenticated user', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $request = request();
    $request->setUserResolver(fn () => $user);

    $service = makeCategoryService();
    $data = CreateCategoryData::fromArray(['name' => 'Groceries', 'type' => 'expense']);

    $result = $service->create($request, $data);

    expect($result->name)->toBe('Groceries');
    expect(Category::query()->where('name', 'Groceries')->where('user_id', $user->id)->exists())->toBeTrue();
});

it('rejects a child category whose type does not match its parent', function () {
    $user = User::factory()->create();
    $request = request();
    $request->setUserResolver(fn () => $user);

    $parent = Category::factory()->for($user)->create(['type' => 'expense']);

    $service = makeCategoryService();
    $data = CreateCategoryData::fromArray(['name' => 'Bad', 'type' => 'income', 'parent_id' => $parent->id]);

    expect(fn () => $service->create($request, $data))->toThrow(ValidationException::class);
});

it('rejects a category becoming its own parent', function () {
    $user = User::factory()->create();
    $request = request();
    $request->setUserResolver(fn () => $user);

    $category = Category::factory()->for($user)->create(['type' => 'expense']);

    $service = makeCategoryService();
    $data = UpdateCategoryData::fromArray(['parent_id' => $category->id]);

    expect(fn () => $service->update($request, $category->id, $data))->toThrow(ValidationException::class);
});

it('rejects changing type when it would mismatch existing children', function () {
    $user = User::factory()->create();
    $request = request();
    $request->setUserResolver(fn () => $user);

    $parent = Category::factory()->for($user)->create(['type' => 'expense']);
    Category::factory()->for($user)->create(['type' => 'expense', 'parent_id' => $parent->id]);

    $service = makeCategoryService();
    $data = UpdateCategoryData::fromArray(['type' => 'income']);

    expect(fn () => $service->update($request, $parent->id, $data))->toThrow(ValidationException::class);
});

it('reassigns transactions to a fallback category before deleting', function () {
    $user = User::factory()->create();
    $request = request();
    $request->setUserResolver(fn () => $user);

    $category = Category::factory()->for($user)->create(['type' => 'expense']);
    $transaction = Transaction::factory()->for($user)->create([
        'category_id' => $category->id,
        'type'        => 'expense',
    ]);

    $service = makeCategoryService();
    $service->delete($request, $category->id);

    expect(Category::query()->find($category->id))->toBeNull();
    $transaction->refresh();
    expect($transaction->category_id)->not->toBe($category->id);
});
