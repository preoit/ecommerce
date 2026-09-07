<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Settings\Models\WebsiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FooterSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_footer_editor_exposes_default_links_as_editable_rich_text(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('website-design.footer.edit'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('app/modules/website-design/footer/pages/Edit', false)
                ->where('footer.link_groups.0.title', 'Let Us Help You')
                ->where('footer.link_groups.0.content', fn ($content) => str_contains($content, 'Account Info')));
    }

    public function test_footer_settings_are_saved_and_unsafe_rich_text_is_removed(): void
    {
        $payload = [
            'description' => 'Updated footer description',
            'phone' => '+8801700000000',
            'copyright_name' => 'Updated Store',
            'app_store_url' => 'https://example.com/app',
            'google_play_url' => 'https://example.com/play',
            'link_groups' => [[
                'title' => 'Customer Care',
                'content' => '<ul><li><a href="/contact">Contact</a></li></ul><script>alert(1)</script>',
            ]],
            'social_links' => ['Facebook' => 'https://facebook.com/example'],
            'payment_methods' => ['Visa', 'bKash'],
            'payment_images' => [],
        ];

        $this->actingAs(User::factory()->create())
            ->patch(route('website-design.footer.update'), $payload)
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Footer section saved successfully.');

        $footer = WebsiteSetting::findOrFail(1)->footer_config;
        $this->assertSame('Updated footer description', $footer['description']);
        $this->assertSame('Customer Care', $footer['link_groups'][0]['title']);
        $this->assertStringContainsString('Contact', $footer['link_groups'][0]['content']);
        $this->assertStringNotContainsString('<script', $footer['link_groups'][0]['content']);
        $this->assertArrayNotHasKey('links', $footer['link_groups'][0]);
    }
}