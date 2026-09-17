<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Input;

class CreateCategoryData
{
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly ?string $icon,
        public readonly ?int $parentId,
    ) {}

    public static function fromArray(array $validated): self
    {
        return new self(
            name: $validated['name'],
            type: $validated['type'],
            icon: $validated['icon'] ?? null,
            parentId: $validated['parent_id'] ?? null,
        );
    }

    public function toModelAttributes(): array
    {
        return [
            'name'      => $this->name,
            'type'      => $this->type,
            'icon'      => $this->icon,
            'parent_id' => $this->parentId,
        ];
    }
}
