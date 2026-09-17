<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Output;

use App\Models\RecurringTransaction;

class RecurringTransactionData implements Arrayable
{
    public function __construct(
        public readonly int $id,
        public readonly int $categoryId,
        public readonly string $type,
        public readonly string $paymentMethod,
        public readonly string $amount,
        public readonly ?string $notes,
        public readonly int $dayOfMonth,
        public readonly string $status,
        public readonly ?string $lastGeneratedAt,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly ?CategoryData $category = null,
    ) {}

    public static function fromModel(RecurringTransaction $rule): self
    {
        return new self(
            id: $rule->id,
            categoryId: $rule->category_id,
            type: $rule->type,
            paymentMethod: $rule->payment_method,
            amount: (string) $rule->amount,
            notes: $rule->notes,
            dayOfMonth: $rule->day_of_month,
            status: $rule->status,
            lastGeneratedAt: $rule->last_generated_at?->toJSON(),
            createdAt: $rule->created_at->toJSON(),
            updatedAt: $rule->updated_at->toJSON(),
            category: $rule->relationLoaded('category') && $rule->category !== null
                ? CategoryData::fromModel($rule->category)
                : null,
        );
    }

    /**
     * @return array<int, array>
     */
    public static function collection(iterable $rules): array
    {
        $result = [];

        foreach ($rules as $rule) {
            $result[] = self::fromModel($rule)->toArray();
        }

        return $result;
    }

    public function toArray(): array
    {
        $data = [
            'id'                => $this->id,
            'category_id'       => $this->categoryId,
            'type'              => $this->type,
            'payment_method'    => $this->paymentMethod,
            'amount'            => $this->amount,
            'notes'             => $this->notes,
            'day_of_month'      => $this->dayOfMonth,
            'status'            => $this->status,
            'last_generated_at' => $this->lastGeneratedAt,
            'created_at'        => $this->createdAt,
            'updated_at'        => $this->updatedAt,
        ];

        if ($this->category !== null) {
            $data['category'] = $this->category->toArray();
        }

        return $data;
    }
}
