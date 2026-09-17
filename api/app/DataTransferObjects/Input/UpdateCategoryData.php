<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Input;

class UpdateCategoryData
{
    public function __construct(
        public readonly ?string $name,
        public readonly ?string $type,
        public readonly ?string $icon,
        public readonly ?int $parentId,
        public readonly bool $hasParentId,
    ) {}

    public static function fromArray(array $validated): self
    {
        return new self(
            name: $validated['name'] ?? null,
            type: $validated['type'] ?? null,
            icon: $validated['icon'] ?? null,
            parentId: $validated['parent_id'] ?? null,
            hasParentId: array_key_exists('parent_id', $validated),
        );
    }

    public function toModelAttributes(): array
    {
        $attributes = [];

        if ($this->name !== null) {
            $attributes['name'] = $this->name;
        }

        if ($this->type !== null) {
            $attributes['type'] = $this->type;
        }

        if ($this->icon !== null) {
            $attributes['icon'] = $this->icon;
        }

        if ($this->hasParentId) {
            $attributes['parent_id'] = $this->parentId;
        }

        return $attributes;
    }
}
