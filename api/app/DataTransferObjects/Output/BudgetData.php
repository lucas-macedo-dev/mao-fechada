<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Output;

use App\Models\Budget;

class BudgetData implements Arrayable
{
    public function __construct(
        public readonly int $id,
        public readonly int $categoryId,
        public readonly int $year,
        public readonly int $month,
        public readonly string $amount,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly ?CategoryData $category = null,
    ) {}

    public static function fromModel(Budget $budget): self
    {
        return new self(
            id: $budget->id,
            categoryId: $budget->category_id,
            year: $budget->year,
            month: $budget->month,
            amount: (string) $budget->amount,
            createdAt: $budget->created_at->toJSON(),
            updatedAt: $budget->updated_at->toJSON(),
            category: $budget->relationLoaded('category') && $budget->category !== null
                ? CategoryData::fromModel($budget->category)
                : null,
        );
    }

    /**
     * @return array<int, array>
     */
    public static function collection(iterable $budgets): array
    {
        $result = [];

        foreach ($budgets as $budget) {
            $result[] = self::fromModel($budget)->toArray();
        }

        return $result;
    }

    public function toArray(): array
    {
        $data = [
            'id'          => $this->id,
            'category_id' => $this->categoryId,
            'year'        => $this->year,
            'month'       => $this->month,
            'amount'      => $this->amount,
            'created_at'  => $this->createdAt,
            'updated_at'  => $this->updatedAt,
        ];

        if ($this->category !== null) {
            $data['category'] = $this->category->toArray();
        }

        return $data;
    }
}
