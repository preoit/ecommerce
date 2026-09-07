<?php

namespace App\Modules\Inventories\Brands\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Brand extends Model
{
    use SoftDeletes;

    protected $fillable = ['parent_id', 'name', 'slug', 'short_description', 'description', 'image_path', 'is_active', 'sort_order', 'seo_title', 'meta_description', 'focus_keyword', 'canonical_url', 'meta_robots', 'og_title', 'og_description'];
    protected $appends = ['image_url'];
    protected function casts(): array { return ['is_active' => 'boolean']; }
    protected function imageUrl(): Attribute { return Attribute::get(fn (): ?string => $this->image_path ? asset('storage/'.$this->image_path) : null); }
    public function parent(): BelongsTo { return $this->belongsTo(self::class, 'parent_id'); }
    public function children(): HasMany { return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('name'); }
}
