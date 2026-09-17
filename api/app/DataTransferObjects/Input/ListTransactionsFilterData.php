<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Input;

class ListTransactionsFilterData
{
    public function __construct(
        public readonly array $filters,
        public readonly int $perPage,
    ) {}

    public static function fromArray(array $validated): self
    {
        return new self(
            filters: $validated,
            perPage: (int) ($validated['per_page'] ?? 20),
        );
    }
}
