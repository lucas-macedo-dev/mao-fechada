<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Output;

class MonthlyComparisonData implements Arrayable
{
    /**
     * @param  array<int, array{month: string, income: float, expense: float}>  $months
     */
    public function __construct(
        public readonly array $months,
    ) {}

    public function toArray(): array
    {
        return ['months' => $this->months];
    }
}
