<?php

declare(strict_types=1);

use App\DataTransferObjects\Output\CategoryData;
use App\Models\Category;
use App\Models\User;

it('maps a category model to an array without exposing user_id', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['type' => 'expense']);

    $array = CategoryData::fromModel($category)->toArray();

    expect($array)->not->toHaveKey('user_id');
    expect($array['id'])->toBe($category->id);
    expect($array['name'])->toBe($category->name);
});

it('includes a null parent key only when the parent relation is loaded', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['type' => 'expense', 'parent_id' => null]);

    $withoutRelation = CategoryData::fromModel($category)->toArray();
    expect($withoutRelation)->not->toHaveKey('parent');

    $category->load('parent');
    $withRelation = CategoryData::fromModel($category)->toArray();
    expect($withRelation)->toHaveKey('parent');
    expect($withRelation['parent'])->toBeNull();
});

it('nests children as arrays when the children relation is loaded', function () {
    $user = User::factory()->create();
    $root = Category::factory()->for($user)->create(['type' => 'expense']);
    Category::factory()->for($user)->create(['type' => 'expense', 'parent_id' => $root->id]);

    $root->load('children');
    $array = CategoryData::fromModel($root)->toArray();

    expect($array['children'])->toHaveCount(1);
    expect($array['children'][0])->not->toHaveKey('user_id');
});
