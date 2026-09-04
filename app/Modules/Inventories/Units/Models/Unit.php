<?php

namespace App\Modules\Inventories\Units\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'slug', 'short_description', 'description', 'image_path', 'is_active', 'sort_order'];
    protected $appends = ['image_url'];
    protected function casts(): array { return ['is_active' => 'boolean']; }
    protected function imageUrl(): Attribute { return Attribute::get(fn (): ?string => $this->image_path ? asset('storage/'.$this->image_path) : null); }
}
