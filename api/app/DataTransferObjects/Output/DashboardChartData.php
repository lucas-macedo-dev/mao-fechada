<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Output;

class DashboardChartData implements Arrayable
{
    public function __construct(
        public readonly string $month,
        public readonly float $income,
        public readonly float $expense,
    ) {}

    public function toArray(): array
    {
        return [
            'month'  => $this->month,
            'series' => [
                'entradas' => $this->income,
                'saidas'   => $this->expense,
            ],
        ];
    }
}
