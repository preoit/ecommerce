<?php

namespace App\Modules\Inventories\Products\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use SoftDeletes;

    protected $guarded = [];
    protected $casts = ['attributes' => 'array', 'regular_price' => 'decimal:2', 'sale_price' => 'decimal:2', 'stock_quantity' => 'integer', 'is_active' => 'boolean'];
    protected $appends = ['current_price', 'discount_percentage'];

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    protected function currentPrice(): Attribute { return Attribute::get(fn () => $this->sale_price !== null && $this->sale_price < $this->regular_price ? (float) $this->sale_price : (float) $this->regular_price); }
    protected function discountPercentage(): Attribute { return Attribute::get(fn () => $this->sale_price !== null && $this->regular_price > 0 && $this->sale_price < $this->regular_price ? (int) round((($this->regular_price - $this->sale_price) / $this->regular_price) * 100) : 0); }
}