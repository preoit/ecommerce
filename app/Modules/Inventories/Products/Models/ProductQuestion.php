<?php
namespace App\Modules\Inventories\Products\Models;
use Illuminate\Database\Eloquent\Model;
class ProductQuestion extends Model { protected $guarded = []; protected $casts = ['answered_at'=>'datetime']; public function product() { return $this->belongsTo(Product::class); } }
