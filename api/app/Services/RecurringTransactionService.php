<?php

declare(strict_types=1);

namespace App\Services;

use App\DataTransferObjects\Output\RecurringTransactionData;
use App\Repositories\RecurringTransactionRepository;
use App\Services\Concerns\AuthorizesOwnership;
use Illuminate\Http\Request;

class RecurringTransactionService
{
    use AuthorizesOwnership;

    public function __construct(
        private readonly RecurringTransactionRepository $recurringTransactions,
    ) {}

    public function list(Request $request): array
    {
        $rules = $this->recurringTransactions->listForUser($request->user()->id);

        return RecurringTransactionData::collection($rules);
    }

    public function cancel(Request $request, int $id): RecurringTransactionData
    {
        $rule = $this->recurringTransactions->findOrFail($id);
        $this->ensureOwnership($request, $rule->user_id);

        $rule = $this->recurringTransactions->cancel($rule);

        return RecurringTransactionData::fromModel($rule);
    }
}
