<?php

namespace App\Modules\Inventories\Categories\Http\Requests;

use App\Modules\Inventories\Categories\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $category = $this->route('category');

        return [
            'name' => ['required', 'string', 'max:150'],
            'parent_id' => ['nullable', 'integer', Rule::exists(Category::class, 'id')->whereNull('deleted_at'), Rule::notIn(array_filter([$category?->id]))],
            'slug' => ['required', 'string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::notIn(config('seo.reserved_slugs')), Rule::unique(Category::class, 'slug')->ignore($category)],
            'short_description' => ['nullable', 'string', 'max:5000'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'image_path' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex' => 'The permalink may only contain lowercase letters, numbers and hyphens.',
            'slug.unique' => 'This permalink is already being used.',
            'slug.not_in' => 'This permalink is reserved by the website. Choose another one.',
            'image.image' => 'Upload a valid image file.',
            'image.mimes' => 'The icon or image must be a JPG, PNG or WebP file.',
            'image.max' => 'The icon or image may not be larger than 2 MB.',
        ];
    }
}
