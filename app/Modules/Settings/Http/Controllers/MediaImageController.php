<?php

namespace App\Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Settings\Models\WebsiteMedia;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaImageController extends Controller
{
    public function __invoke(string $filename): BinaryFileResponse
    {
        $media = WebsiteMedia::query()->where('path', 'like', '%/'.$filename)->firstOrFail();
        abort_unless(Storage::disk('public')->exists($media->path), 404);

        return response()->file(Storage::disk('public')->path($media->path), [
            'Content-Type' => $media->mime_type,
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
