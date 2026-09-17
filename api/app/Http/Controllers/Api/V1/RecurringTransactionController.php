<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\RecurringTransactionService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecurringTransactionController extends Controller
{
    public function __construct(private readonly RecurringTransactionService $recurringTransactionService) {}

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::data($this->recurringTransactionService->list($request));
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $rule = $this->recurringTransactionService->cancel($request, $id);

        return ApiResponse::data($rule->toArray());
    }
}
