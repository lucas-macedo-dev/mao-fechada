<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Output;

use App\Models\Transaction;

class TransactionData implements Arrayable
{
    public function __construct(
        public readonly int $id,
        public readonly int $categoryId,
        public readonly string $type,
        public readonly string $paymentMethod,
        public readonly string $amount,
        public readonly string $transactedAt,
        public readonly ?string $notes,
        public readonly ?string $installmentGroupId,
        public readonly ?int $installmentNumber,
        public readonly ?int $installmentTotal,
        public readonly ?int $recurringTransactionId,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly ?CategoryData $category = null,
    ) {}

    public static function fromModel(Transaction $transaction): self
    {
        return new self(
            id: $transaction->id,
            categoryId: $transaction->category_id,
            type: $transaction->type,
            paymentMethod: $transaction->payment_method,
            amount: (string) $transaction->amount,
            transactedAt: $transaction->transacted_at->toJSON(),
            notes: $transaction->notes,
            installmentGroupId: $transaction->installment_group_id,
            installmentNumber: $transaction->installment_number,
            installmentTotal: $transaction->installment_total,
            recurringTransactionId: $transaction->recurring_transaction_id,
            createdAt: $transaction->created_at->toJSON(),
            updatedAt: $transaction->updated_at->toJSON(),
            category: $transaction->relationLoaded('category') && $transaction->category !== null
                ? CategoryData::fromModel($transaction->category)
                : null,
        );
    }

    /**
     * @return array<int, array>
     */
    public static function collection(iterable $transactions): array
    {
        $result = [];

        foreach ($transactions as $transaction) {
            $result[] = self::fromModel($transaction)->toArray();
        }

        return $result;
    }

    public function toArray(): array
    {
        $data = [
            'id'                       => $this->id,
            'category_id'              => $this->categoryId,
            'type'                     => $this->type,
            'payment_method'           => $this->paymentMethod,
            'amount'                   => $this->amount,
            'transacted_at'            => $this->transactedAt,
            'notes'                    => $this->notes,
            'installment_group_id'     => $this->installmentGroupId,
            'installment_number'       => $this->installmentNumber,
            'installment_total'        => $this->installmentTotal,
            'recurring_transaction_id' => $this->recurringTransactionId,
            'created_at'               => $this->createdAt,
            'updated_at'               => $this->updatedAt,
        ];

        if ($this->category !== null) {
            $data['category'] = $this->category->toArray();
        }

        return $data;
    }
}
