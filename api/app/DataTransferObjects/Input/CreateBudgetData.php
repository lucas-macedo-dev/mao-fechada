<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Input;

class CreateBudgetData
{
    public function __construct(
        public readonly int $categoryId,
        public readonly int $year,
        public readonly int $month,
        public readonly float $amount,
    ) {}

    public static function fromArray(array $validated): self
    {
        return new self(
            categoryId: (int) $validated['category_id'],
            year: (int) $validated['year'],
            month: (int) $validated['month'],
            amount: (float) $validated['amount'],
        );
    }
}
