<?php

namespace App\Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Settings\Models\WebsiteMedia;
use App\Modules\Settings\Models\WebsiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class WebsiteSettingController extends Controller
{
    public function index(): Response
    {
        $settings = WebsiteSetting::firstOrCreate(['id' => 1]);

        return Inertia::render('app/modules/settings/pages/Index', [
            'website' => [
                'name' => $settings->website_name,
                'logoPath' => $settings->logo_path,
                'logo' => $settings->logo_path ? '/image/'.rawurlencode(basename($settings->logo_path)) : null,
                'faviconPath' => $settings->favicon_path,
                'favicon' => $settings->favicon_path ? '/image/'.rawurlencode(basename($settings->favicon_path)) : null,
                'seoTitle' => $settings->seo_title,
                'seoDescription' => $settings->seo_description,
                'seoImagePath' => $settings->seo_image_path,
                'seoImage' => $settings->seo_image_path ? '/image/'.rawurlencode(basename($settings->seo_image_path)) : null,
                'allowOutOfStockOrders' => (bool) $settings->allow_out_of_stock_orders,
                'showStockToCustomers' => (bool) $settings->show_stock_to_customers,
                'deliveryEnabled' => (bool) ($settings->delivery_enabled ?? true),
                'deliveryInsideDhaka' => (float) ($settings->delivery_inside_dhaka ?? 80),
                'deliveryOutsideDhaka' => (float) ($settings->delivery_outside_dhaka ?? 150),
                'freeDeliveryEnabled' => (bool) $settings->free_delivery_enabled,
                'freeDeliveryThreshold' => $settings->free_delivery_threshold,
                'codSurchargeEnabled' => (bool) $settings->cod_surcharge_enabled,
                'codSurcharge' => (float) ($settings->cod_surcharge ?? 0),
                'productDeliveryOverrideEnabled' => (bool) ($settings->product_delivery_override_enabled ?? true),
                'heavyDeliveryEnabled' => (bool) $settings->heavy_delivery_enabled,
                'heavyWeightThreshold' => (float) ($settings->heavy_weight_threshold ?? 5),
                'heavyChargePerKg' => (float) ($settings->heavy_charge_per_kg ?? 0),
            ],
            'media' => WebsiteMedia::latest()->get()->filter(fn (WebsiteMedia $file): bool => Storage::disk('public')->exists($file->path))->map(fn (WebsiteMedia $file): array => [
                'id' => $file->id,
                'name' => $file->name,
                'title' => $file->title,
                'altText' => $file->alt_text,
                'caption' => $file->caption,
                'url' => $file->publicUrl(),
                'seoUrl' => $file->publicUrl(),
                'path' => $file->path,
                'mimeType' => $file->mime_type,
                'size' => $file->size,
            ]),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'website_name' => ['required', 'string', 'max:120'],
            'logo_path' => ['nullable', 'string', 'max:255', Rule::exists('website_media', 'path')],
            'favicon_path' => ['nullable', 'string', 'max:255', Rule::exists('website_media', 'path')],
            'seo_title' => ['nullable', 'string', 'max:160'],
            'seo_description' => ['nullable', 'string', 'max:320'],
            'seo_image_path' => ['nullable', 'string', 'max:255', Rule::exists('website_media', 'path')],
            'allow_out_of_stock_orders' => ['sometimes', 'boolean'],
            'show_stock_to_customers' => ['sometimes', 'boolean'],
            'delivery_enabled' => ['sometimes', 'boolean'],
            'delivery_inside_dhaka' => ['required_if:delivery_enabled,true', 'numeric', 'min:0'],
            'delivery_outside_dhaka' => ['required_if:delivery_enabled,true', 'numeric', 'min:0'],
            'free_delivery_enabled' => ['sometimes', 'boolean'],
            'free_delivery_threshold' => ['nullable', 'numeric', 'min:0'],
            'cod_surcharge_enabled' => ['sometimes', 'boolean'],
            'cod_surcharge' => ['nullable', 'numeric', 'min:0'],
            'product_delivery_override_enabled' => ['sometimes', 'boolean'],
            'heavy_delivery_enabled' => ['sometimes', 'boolean'],
            'heavy_weight_threshold' => ['nullable', 'numeric', 'min:0'],
            'heavy_charge_per_kg' => ['nullable', 'numeric', 'min:0'],
        ]);

        WebsiteSetting::updateOrCreate(['id' => 1], $data);

        return back()->with('success', 'Website settings saved successfully.');
    }

    public function upload(Request $request): JsonResponse
    {
        $request->validate(['file' => ['required', 'image', 'max:4096']]);
        $file = $request->file('file');
        $baseName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $extension = Str::lower($file->getClientOriginalExtension());
        $filename = $baseName.'.'.$extension;
        $duplicate = 1;
        while (Storage::disk('public')->exists('image/'.$filename)) {
            $filename = $baseName.'-'.$duplicate.'.'.$extension;
            $duplicate++;
        }
        $path = $file->storeAs('image', $filename, 'public');
        $media = WebsiteMedia::create([
            'name' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        return response()->json([
            'id' => $media->id,
            'name' => $media->name,
            'title' => $media->title,
            'altText' => $media->alt_text,
            'caption' => $media->caption,
            // Use the storage URL immediately after upload; it is guaranteed to be available
            // while the SEO-friendly /image URL remains available after a page refresh.
            'url' => $media->publicUrl(),
            'seoUrl' => $media->publicUrl(),
            'path' => $media->path,
            'mimeType' => $media->mime_type,
            'size' => $media->size,
        ]);
    }
}
