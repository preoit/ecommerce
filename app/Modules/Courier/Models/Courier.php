<?php
namespace App\Modules\Courier\Models;
use Illuminate\Database\Eloquent\Model;
class Courier extends Model {
    protected $guarded = [];
    protected $hidden = ['credentials'];
    protected $casts = ['credentials'=>'encrypted:array','active'=>'boolean','sandbox_mode'=>'boolean','status_mapping'=>'array','tested_at'=>'datetime'];
}
