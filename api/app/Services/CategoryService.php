<?php

declare(strict_types=1);

namespace App\Services;

use App\DataTransferObjects\Input\CreateCategoryData;
use App\DataTransferObjects\Input\UpdateCategoryData;
use App\DataTransferObjects\Output\CategoryData;
use App\Repositories\CategoryRepository;
use App\Services\Concerns\AuthorizesOwnership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CategoryService
{
    use AuthorizesOwnership;

    public function __construct(
        private readonly CategoryRepository $categories,
    ) {}

    public function tree(Request $request): array
    {
        return CategoryData::collection($this->categories->treeForUser($request->user()->id));
    }

    public function list(Request $request): array
    {
        return CategoryData::collection($this->categories->listForUser($request->user()->id));
    }

    public function create(Request $request, CreateCategoryData $data): CategoryData
    {
        $this->validateParent($request, $data->parentId, $data->type);

        $category = $request->user()->categories()->create($data->toModelAttributes());

        return CategoryData::fromModel($category);
    }

    public function find(Request $request, int $id): CategoryData
    {
        $category = $this->categories->findWithRelations($id);
        $this->ensureOwnership($request, $category->user_id);

        return CategoryData::fromModel($category);
    }

    public function update(Request $request, int $id, UpdateCategoryData $data): CategoryData
    {
        $category = $this->categories->findOrFail($id);
        $this->ensureOwnership($request, $category->user_id);

        $effectiveType = $data->type ?? $category->type;
        $targetParentId = $data->hasParentId ? $data->parentId : $category->parent_id;

        if ($targetParentId !== null && (int) $targetParentId === (int) $category->id) {
            throw ValidationException::withMessages([
                'parent_id' => [__('messages.category_parent_self')],
            ]);
        }

        $this->validateParent($request, $targetParentId, $effectiveType);

        if ($data->type !== null && $this->categories->hasMismatchedChildren($category, $data->type)) {
            throw ValidationException::withMessages([
                'type' => [__('messages.category_type_must_match_children')],
            ]);
        }

        $category = $this->categories->update($category, $data->toModelAttributes());

        return CategoryData::fromModel($category);
    }

    public function delete(Request $request, int $id): void
    {
        $category = $this->categories->findOrFail($id);
        $this->ensureOwnership($request, $category->user_id);

        DB::transaction(function () use ($request, $category): void {
            if ($this->categories->hasTransactions($category)) {
                $fallback = FallbackCategoryResolver::findOrCreate($request->user(), $category->type);
                $category->transactions()->update(['category_id' => $fallback->id]);
            }
            $this->categories->delete($category);
        });
    }

    private function validateParent(Request $request, ?int $parentId, string $type): void
    {
        if ($parentId === null) {
            return;
        }

        $parent = $this->categories->findOrFail($parentId);
        $this->ensureOwnership($request, $parent->user_id);

        if ($parent->parent_id !== null) {
            throw ValidationException::withMessages([
                'parent_id' => [__('messages.category_parent_must_be_root')],
            ]);
        }

        if ($parent->type !== $type) {
            throw ValidationException::withMessages([
                'type' => [__('messages.category_type_must_match_parent')],
            ]);
        }
    }
}
