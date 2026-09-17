<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Output;

class DashboardSummaryData implements Arrayable
{
    public function __construct(
        public readonly string $month,
        public readonly float $income,
        public readonly float $expense,
        public readonly array $recentTransactions,
    ) {}

    public function toArray(): array
    {
        return [
            'month'  => $this->month,
            'totals' => [
                'entradas' => $this->income,
                'saidas'   => $this->expense,
                'saldo'    => $this->income - $this->expense,
            ],
            'chart' => [
                'entradas' => $this->income,
                'saidas'   => $this->expense,
            ],
            'recent_transactions' => $this->recentTransactions,
        ];
    }
}
