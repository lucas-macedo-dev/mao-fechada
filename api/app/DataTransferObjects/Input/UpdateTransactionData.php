<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Input;

class UpdateTransactionData
{
    public function __construct(
        public readonly ?int $categoryId,
        public readonly ?string $type,
        public readonly ?string $paymentMethod,
        public readonly ?float $amount,
        public readonly ?string $transactedAt,
        public readonly ?string $notes,
        public readonly bool $hasCategoryId,
        public readonly bool $hasType,
        public readonly bool $hasNotes,
    ) {}

    public static function fromArray(array $validated): self
    {
        return new self(
            categoryId: isset($validated['category_id']) ? (int) $validated['category_id'] : null,
            type: $validated['type'] ?? null,
            paymentMethod: $validated['payment_method'] ?? null,
            amount: isset($validated['amount']) ? (float) $validated['amount'] : null,
            transactedAt: $validated['transacted_at'] ?? null,
            notes: $validated['notes'] ?? null,
            hasCategoryId: array_key_exists('category_id', $validated),
            hasType: array_key_exists('type', $validated),
            hasNotes: array_key_exists('notes', $validated),
        );
    }

    public function toModelAttributes(): array
    {
        $attributes = [];

        if ($this->hasCategoryId) {
            $attributes['category_id'] = $this->categoryId;
        }

        if ($this->hasType) {
            $attributes['type'] = $this->type;
        }

        if ($this->paymentMethod !== null) {
            $attributes['payment_method'] = $this->paymentMethod;
        }

        if ($this->amount !== null) {
            $attributes['amount'] = $this->amount;
        }

        if ($this->transactedAt !== null) {
            $attributes['transacted_at'] = $this->transactedAt;
        }

        if ($this->hasNotes) {
            $attributes['notes'] = $this->notes;
        }

        return $attributes;
    }
}
