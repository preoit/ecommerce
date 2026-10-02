<?php

namespace App\Modules\Settings\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class WebsiteMedia extends Model
{
    protected static function booted(): void
    {
        static::saved(function (self $media): void {
            Cache::forget('storefront.website_identity.v1');
            Cache::forget('media.image.'.hash('sha256', basename(str_replace('\\', '/', $media->path))));
        });
        static::deleted(function (self $media): void {
            Cache::forget('storefront.website_identity.v1');
            Cache::forget('media.image.'.hash('sha256', basename(str_replace('\\', '/', $media->path))));
        });
    }

    protected $fillable = ['name', 'title', 'alt_text', 'caption', 'path', 'mime_type', 'size'];

    public function publicUrl(): string
    {
        // A relative URL always follows the domain currently serving the app.
        $version = $this->updated_at?->getTimestamp() ?? time();
        return '/image/'.rawurlencode(basename(str_replace('\\', '/', $this->path))).'?v='.$version;
    }
}
