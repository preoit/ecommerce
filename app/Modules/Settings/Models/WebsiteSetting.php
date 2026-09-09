<?php

namespace App\Modules\Settings\Models;

use Illuminate\Database\Eloquent\Model;

class WebsiteSetting extends Model
{
    protected $fillable = [
        'website_name', 'logo_path', 'favicon_path', 'seo_title', 'seo_description', 'seo_image_path',
        'hero_primary_image_path', 'hero_primary_image_paths', 'hero_primary_link', 'hero_secondary_image_path', 'hero_secondary_link', 'footer_config',
        'allow_out_of_stock_orders', 'show_stock_to_customers',
        'delivery_enabled', 'delivery_inside_dhaka', 'delivery_outside_dhaka', 'free_delivery_enabled', 'free_delivery_threshold',
        'cod_surcharge_enabled', 'cod_surcharge', 'product_delivery_override_enabled', 'heavy_delivery_enabled', 'heavy_weight_threshold', 'heavy_charge_per_kg',
    ];

    protected function casts(): array
    {
        return ['hero_primary_image_paths' => 'array', 'footer_config' => 'array', 'allow_out_of_stock_orders' => 'boolean', 'show_stock_to_customers' => 'boolean', 'delivery_enabled' => 'boolean', 'free_delivery_enabled' => 'boolean', 'cod_surcharge_enabled' => 'boolean', 'product_delivery_override_enabled' => 'boolean', 'heavy_delivery_enabled' => 'boolean'];
    }
}
