<?php
namespace App\Modules\Inventories\Products\Models;
use Illuminate\Database\Eloquent\Model;
class ProductBulkPrice extends Model { protected $guarded = []; protected $casts = ['unit_price'=>'decimal:2']; }
