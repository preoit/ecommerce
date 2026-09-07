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
        public ?string $seoTitle,
        public ?string $metaDescription,
        public ?string $focusKeyword,
        public ?string $canonicalUrl,
        public string $metaRobots,
        public ?string $ogTitle,
        public ?string $ogDescription,
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
            seoTitle: $validated['seo_title'] ?? null,
            metaDescription: $validated['meta_description'] ?? null,
            focusKeyword: $validated['focus_keyword'] ?? null,
            canonicalUrl: $validated['canonical_url'] ?? null,
            metaRobots: $validated['meta_robots'] ?? 'index,follow',
            ogTitle: $validated['og_title'] ?? null,
            ogDescription: $validated['og_description'] ?? null,
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
            'seo_title' => $this->seoTitle,
            'meta_description' => $this->metaDescription,
            'focus_keyword' => $this->focusKeyword,
            'canonical_url' => $this->canonicalUrl,
            'meta_robots' => $this->metaRobots,
            'og_title' => $this->ogTitle,
            'og_description' => $this->ogDescription,
        ];
    }
}
