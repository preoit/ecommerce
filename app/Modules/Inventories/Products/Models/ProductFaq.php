<?php
namespace App\Modules\Inventories\Products\Models;
use Illuminate\Database\Eloquent\Model;
class ProductFaq extends Model { protected $guarded = []; protected $casts = ['is_active'=>'boolean']; }
