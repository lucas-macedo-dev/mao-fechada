<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Input;

class CreateTransactionData
{
    public function __construct(
        public readonly int $categoryId,
        public readonly string $type,
        public readonly string $paymentMethod,
        public readonly float $amount,
        public readonly string $transactedAt,
        public readonly ?string $notes,
        public readonly ?int $installmentNumber,
        public readonly ?int $installmentTotal,
        public readonly bool $recurring,
    ) {}

    public static function fromArray(array $validated): self
    {
        return new self(
            categoryId: (int) $validated['category_id'],
            type: $validated['type'],
            paymentMethod: $validated['payment_method'],
            amount: (float) $validated['amount'],
            transactedAt: $validated['transacted_at'],
            notes: $validated['notes'] ?? null,
            installmentNumber: isset($validated['installment_number']) ? (int) $validated['installment_number'] : null,
            installmentTotal: isset($validated['installment_total']) ? (int) $validated['installment_total'] : null,
            recurring: (bool) ($validated['recurring'] ?? false),
        );
    }

    public function toModelAttributes(): array
    {
        return [
            'category_id'    => $this->categoryId,
            'type'           => $this->type,
            'payment_method' => $this->paymentMethod,
            'amount'         => $this->amount,
            'transacted_at'  => $this->transactedAt,
            'notes'          => $this->notes,
        ];
    }
}
