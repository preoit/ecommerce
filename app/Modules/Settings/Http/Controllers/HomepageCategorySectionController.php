<?php

namespace App\Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventories\Categories\Models\Category;
use App\Modules\Settings\Models\WebsiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class HomepageCategorySectionController extends Controller
{
    public function edit(): Response
    {
        $settings = WebsiteSetting::firstOrCreate(['id' => 1]);

        return Inertia::render('app/modules/website-design/homepage-categories/pages/Edit', [
            'sections' => collect($settings->homepage_category_sections ?? [])->values(),
            'categories' => Category::query()
                ->whereNull('parent_id')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'slug'])
                ->map(fn (Category $category): array => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                ]),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'sections' => ['present', 'array', 'max:12'],
            'sections.*.category_id' => ['required', 'integer', 'distinct', Rule::exists('categories', 'id')],
            'sections.*.enabled' => ['required', 'boolean'],
            'sections.*.limit' => ['required', 'integer', Rule::in([4, 8, 12, 16])],
            'sections.*.sort' => ['required', Rule::in(['latest', 'featured', 'best_seller'])],
        ]);

        $ids = collect($data['sections'])->pluck('category_id')->map(fn ($id): int => (int) $id);
        $validIds = Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id);
        if ($ids->diff($validIds)->isNotEmpty()) {
            throw ValidationException::withMessages(['sections' => 'Only active main categories can be added to the homepage.']);
        }

        $sections = collect($data['sections'])->map(fn (array $section): array => [
            'category_id' => (int) $section['category_id'],
            'enabled' => (bool) $section['enabled'],
            'limit' => (int) $section['limit'],
            'sort' => $section['sort'],
        ])->values()->all();
        WebsiteSetting::updateOrCreate(['id' => 1], ['homepage_category_sections' => $sections]);

        return back()->with('success', 'Homepage category sections saved successfully.');
    }
}
