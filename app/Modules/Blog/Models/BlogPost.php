<?php
namespace App\Modules\Blog\Models;

use Illuminate\Database\Eloquent\Model;

class BlogPost extends Model
{
    protected $guarded = ['id'];
    protected function casts(): array { return ['gallery' => 'array', 'seo' => 'array', 'published_at' => 'datetime']; }
    public function author() { return $this->belongsTo(BlogAuthor::class, 'author_id'); }
    public function categories() { return $this->belongsToMany(BlogCategory::class, 'blog_category_post'); }
    public function scopePublished($query) { return $query->where('status', 'Published')->whereNotNull('published_at')->where('published_at', '<=', now()); }
}
