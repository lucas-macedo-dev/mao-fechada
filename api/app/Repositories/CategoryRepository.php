<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepository<Category>
 */
class CategoryRepository extends BaseRepository
{
    protected string $modelClass = Category::class;

    public function treeForUser(int $userId): Collection
    {
        return Category::query()
            ->where('user_id', $userId)
            ->rootsWithChildren()
            ->get();
    }

    public function listForUser(int $userId): Collection
    {
        return Category::query()
            ->where('user_id', $userId)
            ->with('parent')
            ->orderBy('name')
            ->get();
    }

    public function findWithRelations(int $id): Category
    {
        return Category::query()->with(['parent', 'children'])->findOrFail($id);
    }

    public function update(Category $category, array $attributes): Category
    {
        $category->update($attributes);

        return $category->fresh(['parent', 'children']);
    }

    public function delete(Category $category): void
    {
        $category->delete();
    }

    public function hasTransactions(Category $category): bool
    {
        return $category->transactions()->exists();
    }

    public function hasMismatchedChildren(Category $category, string $type): bool
    {
        return $category->children()->where('type', '!=', $type)->exists();
    }
}
