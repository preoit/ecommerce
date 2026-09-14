<?php
namespace App\Modules\Courier\Models;
use Illuminate\Database\Eloquent\Model;
class CourierOrder extends Model {
    protected $guarded = [];
    protected $casts = ['request_data'=>'encrypted:array','response_data'=>'encrypted:array','sandbox_mode'=>'boolean','booked_at'=>'datetime','delivered_at'=>'datetime','synced_at'=>'datetime'];
    public function courier() { return $this->belongsTo(Courier::class); }
}
