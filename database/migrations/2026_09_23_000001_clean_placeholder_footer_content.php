<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('website_settings')) {
            return;
        }

        $raw = DB::table('website_settings')->where('id', 1)->value('footer_config');
        $footer = is_array($raw) ? $raw : json_decode((string) $raw, true);

        if (! is_array($footer)) {
            return;
        }

        $description = strtolower((string) ($footer['description'] ?? ''));
        if (str_contains($description, 'varity') || str_contains($description, 'ragnge') || str_contains($description, 'group of company')) {
            $footer['description'] = 'iTTiBA International is a group of companies in Bangladesh. We import and produce a wide range of quality products for customers across Bangladesh.';
        }

        $groups = json_encode($footer['link_groups'] ?? []);
        if (str_contains($groups, 'Unimart') || str_contains($groups, 'New York') || str_contains($groups, 'Inverstor')) {
            $footer['link_groups'] = [
                ['title' => 'Customer Care', 'content' => '<ul><li><a href="/account">My account</a></li><li><a href="/account/orders">My orders</a></li><li><a href="/cart">Shopping cart</a></li></ul>'],
                ['title' => 'Shop', 'content' => '<ul><li><a href="/products">All products</a></li><li><a href="/wishlist">Wishlist</a></li><li><a href="/blog">Stories &amp; Guides</a></li></ul>'],
                ['title' => 'Company', 'content' => '<ul><li><a href="/">Home</a></li><li><a href="/blog">Stories &amp; Guides</a></li></ul>'],
            ];
        }

        foreach (['app_store_url', 'google_play_url'] as $key) {
            if ($this->isPlaceholderUrl($footer[$key] ?? null)) {
                $footer[$key] = '';
            }
        }

        foreach (($footer['social_links'] ?? []) as $network => $url) {
            if ($this->isPlaceholderUrl($url)) {
                $footer['social_links'][$network] = '';
            }
        }

        if (preg_match('/^\+?880\s*0+(?:\s*0+)*$/', (string) ($footer['phone'] ?? ''))) {
            $footer['phone'] = '';
        }

        DB::table('website_settings')->where('id', 1)->update([
            'footer_config' => json_encode($footer, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function down(): void
    {
        // Placeholder content is intentionally not restored.
    }

    private function isPlaceholderUrl(mixed $value): bool
    {
        $url = trim((string) $value);

        return $url === '' || $url === '#' || str_ends_with($url, '/#');
    }
};
