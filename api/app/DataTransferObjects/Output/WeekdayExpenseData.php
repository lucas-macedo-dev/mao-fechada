<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Output;

class WeekdayExpenseData implements Arrayable
{
    /**
     * @param  array<int, array{weekday: int, date: string, expense: float}>  $days
     */
    public function __construct(
        public readonly array $days,
    ) {}

    public function toArray(): array
    {
        return $this->days;
    }
}
