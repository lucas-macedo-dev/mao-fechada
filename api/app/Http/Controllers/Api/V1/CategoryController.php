<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $categories = $request->user()
            ->categories()
            ->orderBy('name')
            ->get();

        return ApiResponse::data($categories);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(['income', 'expense'])],
        ]);

        $category = $request->user()
            ->categories()
            ->create($validated);

        return ApiResponse::data($category, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $category = Category::query()->findOrFail($id);
        $this->ensureOwnership($request, $category->user_id);

        return ApiResponse::data($category);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $category = Category::query()->findOrFail($id);
        $this->ensureOwnership($request, $category->user_id);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'type' => ['sometimes', 'required', Rule::in(['income', 'expense'])],
        ]);

        $category->update($validated);

        return ApiResponse::data($category->fresh());
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
            abort(403, 'You are not allowed to access this resource.');
        }
    }
}
