<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Output;

class CategoryBreakdownData implements Arrayable
{
    public function __construct(
        public readonly string $name,
        public readonly float $value,
    ) {}

    public function toArray(): array
    {
        return [
            'name'  => $this->name,
            'value' => $this->value,
        ];
    }

    /**
     * @param  array<int, self>  $rows
     * @return array<int, array>
     */
    public static function collection(array $rows): array
    {
        return array_map(fn (self $row) => $row->toArray(), $rows);
    }
}
