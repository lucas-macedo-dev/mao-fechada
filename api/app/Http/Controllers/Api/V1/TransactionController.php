<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DataTransferObjects\Input\CreateTransactionData;
use App\DataTransferObjects\Input\ListTransactionsFilterData;
use App\DataTransferObjects\Input\UpdateTransactionData;
use App\DataTransferObjects\Output\TransactionData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListTransactionsRequest;
use App\Http\Requests\Api\V1\StoreTransactionRequest;
use App\Http\Requests\Api\V1\UpdateTransactionRequest;
use App\Services\TransactionService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(private readonly TransactionService $transactionService) {}

    public function index(ListTransactionsRequest $request): JsonResponse
    {
        $data = ListTransactionsFilterData::fromArray($request->validated());
        $paginator = $this->transactionService->paginate($request, $data)->appends($request->query());

        return ApiResponse::data(TransactionData::collection($paginator->items()), meta: [
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
            'per_page'     => $paginator->perPage(),
            'total'        => $paginator->total(),
        ]);
    }

    public function store(StoreTransactionRequest $request): JsonResponse
    {
        $data = CreateTransactionData::fromArray($request->validated());
        $result = $this->transactionService->create($request, $data);

        $payload = is_array($result) ? $result : $result->toArray();

        return ApiResponse::data($payload, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $transaction = $this->transactionService->find($request, $id);

        return ApiResponse::data($transaction->toArray());
    }

    public function update(UpdateTransactionRequest $request, int $id): JsonResponse
    {
        $data = UpdateTransactionData::fromArray($request->validated());
        $transaction = $this->transactionService->update($request, $id, $data);

        return ApiResponse::data($transaction->toArray());
    }

    public function convertToRecurring(Request $request, int $id): JsonResponse
    {
        $transaction = $this->transactionService->convertToRecurring($request, $id);

        return ApiResponse::data($transaction->toArray());
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->transactionService->delete($request, $id);

        return response()->json([], 204);
    }
}
