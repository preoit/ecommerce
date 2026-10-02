<?php

namespace App\Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Settings\Models\WebsiteMedia;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaImageController extends Controller
{
    public function __invoke(string $filename): BinaryFileResponse
    {
        $media = Cache::remember('media.image.'.hash('sha256', $filename), now()->addMinutes(10), fn (): ?array =>
            WebsiteMedia::query()->where('path', 'like', '%/'.$filename)->first(['path', 'mime_type'])?->only(['path', 'mime_type'])
        );
        abort_unless($media && Storage::disk('public')->exists($media['path']), 404);

        return response()->file(Storage::disk('public')->path($media['path']), [
            'Content-Type' => $media['mime_type'],
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
