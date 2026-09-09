<?php
namespace App\Modules\Customers\Models;
use Illuminate\Database\Eloquent\Model;
class CustomerAddress extends Model
{
    protected $fillable = ['label', 'recipient_name', 'phone', 'delivery_zone', 'district', 'city', 'area', 'address', 'postal_code', 'landmark', 'is_default'];
    protected function casts(): array { return ['is_default' => 'boolean']; }
}