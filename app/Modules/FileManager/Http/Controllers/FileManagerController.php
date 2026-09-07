<?php

namespace App\Modules\FileManager\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Settings\Models\WebsiteMedia;
use App\Modules\Settings\Models\WebsiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class FileManagerController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('app/modules/file-manager/pages/Index', [
            'files' => WebsiteMedia::latest()->get()->map(fn (WebsiteMedia $file): array => [
                'id' => $file->id,
                'name' => $file->name,
                'path' => $file->path,
                'url' => $file->publicUrl(),
                'seoUrl' => $file->publicUrl(),
                'type' => str_starts_with($file->mime_type, 'image/') ? 'image' : 'document',
                'size' => number_format($file->size / 1024 / 1024, 1).' MB',
                'updated' => $file->created_at->diffForHumans(),
                'title' => $file->title,
                'altText' => $file->alt_text,
                'caption' => $file->caption,
            ]),
        ]);
    }

    public function media(): JsonResponse
    {
        return response()->json(WebsiteMedia::latest()->get()->map(fn (WebsiteMedia $file): array => [
            'id' => $file->id,
            'name' => $file->name,
            'title' => $file->title,
            'url' => $file->publicUrl(),
            'seoUrl' => $file->publicUrl(),
            'path' => $file->path,
            'altText' => $file->alt_text,
            'caption' => $file->caption,
            'mimeType' => $file->mime_type,
            'size' => $file->size,
        ]));
    }

    public function update(Request $request, WebsiteMedia $media): JsonResponse
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:150'],
            'alt_text' => ['nullable', 'string', 'max:180'],
            'caption' => ['nullable', 'string', 'max:1000'],
        ]);

        $media->update($data);

        return response()->json([
            'id' => $media->id,
            'title' => $media->title,
            'altText' => $media->alt_text,
            'caption' => $media->caption,
        ]);
    }

    public function destroy(WebsiteMedia $media): JsonResponse
    {
        $settings = WebsiteSetting::find(1);
        if ($settings) {
            foreach (['logo_path', 'favicon_path', 'seo_image_path', 'hero_primary_image_path', 'hero_secondary_image_path'] as $field) {
                if ($settings->{$field} === $media->path) $settings->{$field} = null;
            }
            $settings->hero_primary_image_paths = collect($settings->hero_primary_image_paths ?? [])->reject(fn ($path) => $path === $media->path)->values()->all();
            $settings->save();
        }
        Storage::disk('public')->delete($media->path);
        $media->delete();

        return response()->json(['deleted' => true]);
    }
}
