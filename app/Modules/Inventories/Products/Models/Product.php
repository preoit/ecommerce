<?php

namespace App\Modules\Inventories\Products\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;
    protected $guarded = [];
    protected $casts = ['gallery'=>'array','regular_price'=>'decimal:2','sale_price'=>'decimal:2','weight'=>'decimal:3','length'=>'decimal:2','width'=>'decimal:2','height'=>'decimal:2','is_featured'=>'boolean','is_new_arrival'=>'boolean','is_best_seller'=>'boolean','cash_on_delivery'=>'boolean','published_at'=>'datetime'];
    protected $appends = ['current_price','discount_percentage','stock_status'];
    protected static function booted(): void { static::updating(function (Product $product) { if ($product->isDirty('slug') && $product->getOriginal('slug')) \Illuminate\Support\Facades\DB::table('product_url_redirects')->updateOrInsert(['old_slug'=>$product->getOriginal('slug')],['product_id'=>$product->id,'created_at'=>now(),'updated_at'=>now()]); }); }
    public function categories(): BelongsToMany { return $this->belongsToMany(\App\Modules\Inventories\Categories\Models\Category::class); }
    public function brand(): BelongsTo { return $this->belongsTo(\App\Modules\Inventories\Brands\Models\Brand::class); }
    public function unit(): BelongsTo { return $this->belongsTo(\App\Modules\Inventories\Units\Models\Unit::class); }
    public function images(): HasMany { return $this->hasMany(ProductImage::class)->orderBy('sort_order'); }
    public function specifications(): HasMany { return $this->hasMany(ProductSpecification::class)->orderBy('sort_order'); }
    public function reviews(): HasMany { return $this->hasMany(ProductReview::class); }
    public function approvedReviews(): HasMany { return $this->reviews()->where('status', 'approved'); }
    public function questions(): HasMany { return $this->hasMany(ProductQuestion::class); }
    public function faqs(): HasMany { return $this->hasMany(ProductFaq::class)->where('is_active', true)->orderBy('sort_order'); }
    public function bulkPrices(): HasMany { return $this->hasMany(ProductBulkPrice::class)->orderBy('min_quantity'); }
    public function variants(): HasMany { return $this->hasMany(ProductVariant::class)->orderBy('sort_order'); }
    protected function currentPrice(): Attribute { return Attribute::get(fn () => $this->sale_price !== null && $this->sale_price < $this->regular_price ? (float) $this->sale_price : (float) $this->regular_price); }
    protected function discountPercentage(): Attribute { return Attribute::get(fn () => $this->sale_price !== null && $this->regular_price > 0 && $this->sale_price < $this->regular_price ? (int) round((($this->regular_price - $this->sale_price) / $this->regular_price) * 100) : 0); }
    protected function stockStatus(): Attribute { return Attribute::get(fn () => $this->stock_quantity <= 0 ? 'Out of Stock' : ($this->stock_quantity <= $this->low_stock_threshold ? 'Low Stock' : 'In Stock')); }
}
