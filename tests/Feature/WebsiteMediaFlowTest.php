<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Settings\Models\WebsiteMedia;
use App\Modules\Settings\Models\WebsiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WebsiteMediaFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_select_save_render_and_delete_branding_media(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $upload = $this->actingAs($user)->postJson(route('settings.website.media.upload'), [
            'file' => UploadedFile::fake()->image('brand-logo.png', 240, 80),
        ])->assertOk()->json();

        Storage::disk('public')->assertExists($upload['path']);
        $this->assertStringStartsWith('/image/brand-logo.png?v=', $upload['url']);

        $this->actingAs($user)->patch(route('settings.website.update'), [
            'website_name' => 'Test Store',
            'logo_path' => $upload['path'],
            'favicon_path' => $upload['path'],
            'seo_title' => null,
            'seo_description' => null,
            'seo_image_path' => null,
        ])->assertSessionHasNoErrors();

        $settings = WebsiteSetting::findOrFail(1);
        $this->actingAs($user)->get(route('settings.website'))->assertInertia(fn ($page) => $page
            ->where('website.logoPath', $upload['path'])
            ->where('website.faviconPath', $upload['path'])
            ->where('media.0.path', $upload['path']));
        $this->assertSame($upload['path'], $settings->logo_path);
        $this->get('/')->assertInertia(fn ($page) => $page
            ->where('website.logo', fn ($url) => str_starts_with($url, '/image/brand-logo.png?v='))
            ->where('website.favicon', fn ($url) => str_starts_with($url, '/image/brand-logo.png?v=')));

        $media = WebsiteMedia::findOrFail($upload['id']);
        $this->actingAs($user)->delete(route('file-manager.destroy', $media))->assertOk();
        Storage::disk('public')->assertMissing($upload['path']);
        $this->assertNull($settings->fresh()->logo_path);
        $this->assertNull($settings->fresh()->favicon_path);
    }
}