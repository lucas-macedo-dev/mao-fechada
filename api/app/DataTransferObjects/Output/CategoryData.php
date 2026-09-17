<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Output;

use App\Models\Category;

class CategoryData implements Arrayable
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $type,
        public readonly ?string $icon,
        public readonly ?int $parentId,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly bool $parentLoaded = false,
        public readonly ?self $parent = null,
        public readonly bool $childrenLoaded = false,
        public readonly ?array $children = null,
    ) {}

    public static function fromModel(Category $category): self
    {
        $parentLoaded = $category->relationLoaded('parent');
        $childrenLoaded = $category->relationLoaded('children');

        return new self(
            id: $category->id,
            name: $category->name,
            type: $category->type,
            icon: $category->icon,
            parentId: $category->parent_id,
            createdAt: $category->created_at->toJSON(),
            updatedAt: $category->updated_at->toJSON(),
            parentLoaded: $parentLoaded,
            parent: $parentLoaded && $category->parent !== null
                ? self::fromModel($category->parent)
                : null,
            childrenLoaded: $childrenLoaded,
            children: $childrenLoaded
                ? self::collection($category->children)
                : null,
        );
    }

    /**
     * @return array<int, array>
     */
    public static function collection(iterable $categories): array
    {
        $result = [];

        foreach ($categories as $category) {
            $result[] = self::fromModel($category)->toArray();
        }

        return $result;
    }

    public function toArray(): array
    {
        $data = [
            'id'         => $this->id,
            'name'       => $this->name,
            'type'       => $this->type,
            'icon'       => $this->icon,
            'parent_id'  => $this->parentId,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];

        if ($this->parentLoaded) {
            $data['parent'] = $this->parent?->toArray();
        }

        if ($this->childrenLoaded) {
            $data['children'] = $this->children;
        }

        return $data;
    }
}
