<?php

namespace App\Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Settings\Models\WebsiteMedia;
use App\Modules\Settings\Models\WebsiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HeroSectionController extends Controller
{
    public function edit(): Response
    {
        $settings = WebsiteSetting::firstOrCreate(['id' => 1]);
        $primaryPaths = $settings->hero_primary_image_paths ?: array_filter([$settings->hero_primary_image_path]);

        return Inertia::render('app/modules/website-design/hero/pages/Edit', [
            'hero' => [
                'primaryImages' => array_values(array_filter(array_map(fn (string $path): ?array => $this->media($path), $primaryPaths))),
                'primaryLink' => $settings->hero_primary_link ?? '',
                'secondaryImage' => $this->media($settings->hero_secondary_image_path),
                'secondaryLink' => $settings->hero_secondary_link ?? '',
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'hero_primary_image_path' => ['nullable', 'string', 'max:255'],
            'hero_primary_image_paths' => ['nullable', 'array', 'max:8'],
            'hero_primary_image_paths.*' => ['string', 'max:255'],
            'hero_primary_link' => ['nullable', 'string', 'max:500'],
            'hero_secondary_image_path' => ['nullable', 'string', 'max:255'],
            'hero_secondary_link' => ['nullable', 'string', 'max:500'],
        ]);

        $data['hero_primary_image_path'] = $data['hero_primary_image_paths'][0] ?? null;
        WebsiteSetting::updateOrCreate(['id' => 1], $data);

        return back()->with('success', 'Hero section saved successfully.');
    }

    private function media(?string $path): ?array
    {
        if (! $path) return null;

        $media = WebsiteMedia::query()->where('path', $path)->first();

        return [
            'id' => $media?->id ?? $path,
            'name' => $media?->name ?? basename($path),
            'path' => $path,
            'url' => '/storage/'.$path,
        ];
    }
}
