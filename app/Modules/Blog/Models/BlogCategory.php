<?php
namespace App\Modules\Blog\Models;

use Illuminate\Database\Eloquent\Model;

class BlogCategory extends Model
{
    protected $guarded = ['id'];
    public function posts() { return $this->belongsToMany(BlogPost::class, 'blog_category_post'); }
}
