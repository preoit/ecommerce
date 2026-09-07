<?php

namespace App\Modules\Inventories\Categories\DTOs;

use App\Modules\Inventories\Categories\Http\Requests\StoreCategoryRequest;

readonly class CategoryData
{
    public function __construct(
        public string $name,
        public string $slug,
        public ?int $parentId,
        public ?string $shortDescription,
        public ?string $description,
    ) {}

    public static function fromRequest(StoreCategoryRequest $request, ?string $shortDescription, ?string $description): self
    {
        $validated = $request->validated();

        return new self(
            name: $validated['name'],
            slug: $validated['slug'],
            parentId: isset($validated['parent_id']) ? (int) $validated['parent_id'] : null,
            shortDescription: $shortDescription,
            description: $description,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'parent_id' => $this->parentId,
            'short_description' => $this->shortDescription,
            'description' => $this->description,
        ];
    }
}
