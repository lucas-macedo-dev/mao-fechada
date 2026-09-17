<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Output;

class MtdComparisonData implements Arrayable
{
    public function __construct(
        public readonly array $income,
        public readonly array $expense,
        public readonly array $balance,
        public readonly array $installments,
    ) {}

    public function toArray(): array
    {
        return [
            'income'       => $this->income,
            'expense'      => $this->expense,
            'balance'      => $this->balance,
            'installments' => $this->installments,
        ];
    }
}
