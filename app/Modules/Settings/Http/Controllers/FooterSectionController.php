<?php

namespace App\Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Settings\Models\WebsiteSetting;
use App\Modules\Settings\Models\WebsiteMedia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FooterSectionController extends Controller
{
    public function edit(): Response
    {
        $settings = WebsiteSetting::firstOrCreate(['id' => 1]);

        return Inertia::render('app/modules/website-design/footer/pages/Edit', [
            'footer' => array_replace_recursive($this->defaults(), $settings->footer_config ?: []),
            'media' => WebsiteMedia::latest()->get()->map(fn (WebsiteMedia $file): array => [
                'id' => $file->id, 'name' => $file->name, 'path' => $file->path,
                'url' => '/storage/'.$file->path, 'altText' => $file->alt_text,
            ]),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'description' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:60'],
            'app_store_url' => ['nullable', 'string', 'max:500'],
            'google_play_url' => ['nullable', 'string', 'max:500'],
            'copyright_name' => ['nullable', 'string', 'max:120'],
            'link_groups' => ['nullable', 'array'],
            'link_groups.*.title' => ['nullable', 'string', 'max:80'],
            'link_groups.*.links' => ['nullable', 'array'],
            'link_groups.*.links.*' => ['nullable', 'string', 'max:100'],
            'link_groups.*.content' => ['nullable', 'string', 'max:5000'],
            'social_links' => ['nullable', 'array'],
            'payment_methods' => ['nullable', 'array'],
            'payment_images' => ['nullable', 'array', 'max:8'],
            'payment_images.*' => ['string', 'max:255'],
        ]);

        WebsiteSetting::updateOrCreate(['id' => 1], ['footer_config' => $data]);

        return back()->with('success', 'Footer section saved successfully.');
    }

    private function defaults(): array
    {
        return [
            'description' => 'Discover quality products, great value and dependable service—selected for everyday life.',
            'phone' => '+880 0000 000 000', 'app_store_url' => '#', 'google_play_url' => '#', 'copyright_name' => 'iTTiBA International',
            'link_groups' => [
                ['title' => 'Let Us Help You', 'links' => ['Account Info', 'Your Orders', 'Returns Policies', 'Shipping Rates']],
                ['title' => 'Make Money with Us', 'links' => ['Sell on our store', 'Sell Your Services', 'Become an Affiliate']],
                ['title' => 'Get to Know Us', 'links' => ['Careers', 'About Us', 'Customer Reviews']],
                ['title' => 'Our Stores', 'links' => ['New York', 'London', 'Los Angeles']],
            ],
            'social_links' => ['Facebook' => '#', 'X' => '#', 'Instagram' => '#', 'YouTube' => '#', 'TikTok' => '#', 'WhatsApp' => '#'],
            'payment_methods' => ['Visa', 'Mastercard', 'bKash', 'Nagad', 'NexusPay'],
            'payment_images' => [],
        ];
    }
}
