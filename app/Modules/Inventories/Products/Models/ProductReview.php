<?php
namespace App\Modules\Inventories\Products\Models;
use Illuminate\Database\Eloquent\Model;
class ProductReview extends Model { protected $guarded = []; protected $casts = ['verified_purchase'=>'boolean']; public function images() { return $this->hasMany(ProductReviewImage::class, 'review_id'); } public function product() { return $this->belongsTo(Product::class); } }
