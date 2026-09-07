<?php

namespace App\Modules\Settings\Models;

use Illuminate\Database\Eloquent\Model;

class WebsiteMedia extends Model
{
    protected $fillable = ['name', 'title', 'alt_text', 'caption', 'path', 'mime_type', 'size'];

    public function publicUrl(): string
    {
        // A relative URL always follows the domain currently serving the app.
        $version = $this->updated_at?->getTimestamp() ?? time();
        return '/image/'.rawurlencode(basename(str_replace('\\', '/', $this->path))).'?v='.$version;
    }
}
