<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DataTransferObjects\Input\CreateCategoryData;
use App\DataTransferObjects\Input\UpdateCategoryData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCategoryRequest;
use App\Http\Requests\Api\V1\UpdateCategoryRequest;
use App\Services\CategoryService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $categoryService) {}

    public function index(Request $request): JsonResponse
    {
        if ($request->boolean('tree')) {
            return ApiResponse::data($this->categoryService->tree($request));
        }

        return ApiResponse::data($this->categoryService->list($request));
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $data = CreateCategoryData::fromArray($request->validated());
        $category = $this->categoryService->create($request, $data);

        return ApiResponse::data($category->toArray(), 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $category = $this->categoryService->find($request, $id);

        return ApiResponse::data($category->toArray());
    }

    public function update(UpdateCategoryRequest $request, int $id): JsonResponse
    {
        $data = UpdateCategoryData::fromArray($request->validated());
        $category = $this->categoryService->update($request, $id, $data);

        return ApiResponse::data($category->toArray());
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->categoryService->delete($request, $id);

        return response()->json([], 204);
    }
}
