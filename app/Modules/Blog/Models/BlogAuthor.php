<?php
namespace App\Modules\Blog\Models;

use Illuminate\Database\Eloquent\Model;

class BlogAuthor extends Model
{
    protected $guarded = ['id'];
    protected function casts(): array { return ['social_links' => 'array']; }
    public function posts() { return $this->hasMany(BlogPost::class, 'author_id'); }
}
