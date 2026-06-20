<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCategoryRequest;
use App\Http\Requests\Api\V1\UpdateCategoryRequest;
use App\Models\Category;
use App\Support\ApiResponse;
use App\Support\TransactionTypeMapper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if ($request->boolean('tree')) {
            $categories = $request->user()
                ->categories()
                ->rootsWithChildren()
                ->get();

            return ApiResponse::data($categories);
        }

        $categories = $request->user()
            ->categories()
            ->with('parent')
            ->orderBy('name')
            ->get();

        return ApiResponse::data($categories);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['type'] = TransactionTypeMapper::toDatabase($validated['type']);

        $this->validateParent($request, $validated['parent_id'] ?? null, $validated['type']);

        $category = $request->user()
            ->categories()
            ->create($validated);

        return ApiResponse::data($category, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $category = Category::query()->with(['parent', 'children'])->findOrFail($id);
        $this->ensureOwnership($request, $category->user_id);

        return ApiResponse::data($category);
    }

    public function update(UpdateCategoryRequest $request, int $id): JsonResponse
    {
        $category = Category::query()->findOrFail($id);
        $this->ensureOwnership($request, $category->user_id);

        $validated = $request->validated();

        if (isset($validated['type'])) {
            $validated['type'] = TransactionTypeMapper::toDatabase($validated['type']);
        }

        $effectiveType = $validated['type'] ?? $category->type;
        $targetParentId = array_key_exists('parent_id', $validated)
            ? $validated['parent_id']
            : $category->parent_id;

        if ($targetParentId !== null && (int) $targetParentId === (int) $category->id) {
            throw ValidationException::withMessages([
                'parent_id' => [__('messages.category_parent_self')],
            ]);
        }

        $this->validateParent($request, $targetParentId, $effectiveType);

        if (array_key_exists('type', $validated) && $category->children()->exists()) {
            $hasMismatchedChildren = $category
                ->children()
                ->where('type', '!=', $validated['type'])
                ->exists();

            if ($hasMismatchedChildren) {
                throw ValidationException::withMessages([
                    'type' => [__('messages.category_type_must_match_children')],
                ]);
            }
        }

        $category->update($validated);

        return ApiResponse::data($category->fresh()->load(['parent', 'children']));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $category = Category::query()->findOrFail($id);
        $this->ensureOwnership($request, $category->user_id);

        $category->delete();

        return response()->json([], 204);
    }

    private function ensureOwnership(Request $request, int $ownerUserId): void
    {
        if ((int) $request->user()->id !== $ownerUserId) {
            abort(403, __('messages.ownership_denied'));
        }
    }

    private function validateParent(Request $request, int|null $parentId, string $type): void
    {
        if ($parentId === null) {
            return;
        }

        $parent = Category::query()->findOrFail($parentId);
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
