<?php
namespace Tests\Feature;

use App\Models\User;
use App\Modules\Blog\Models\{BlogPost, BlogCategory, BlogAuthor};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogManagementTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $extra = []): array
    {
        $author = BlogAuthor::firstOrCreate(['slug' => 'editor'], ['name' => 'Editor']);
        return array_replace([
            'title' => 'Water Filter Guide', 'slug' => 'water-filter-guide', 'author_id' => $author->id,
            'category_ids' => [], 'content' => '<h2>Guide</h2><p>Helpful content.</p>', 'excerpt' => 'A helpful guide.',
            'gallery' => [], 'status' => 'Published', 'seo' => ['robots' => 'index,follow', 'title' => 'Filter guide SEO'],
        ], $extra);
    }

    public function test_admin_can_create_edit_publish_and_redirect_a_blog(): void
    {
        $this->actingAs(User::factory()->create());
        $category = BlogCategory::create(['name' => 'Guides', 'slug' => 'guides']);
        $payload = $this->payload(['category_ids' => [$category->id]]);
        $this->post(route('blog.store'), $payload)->assertSessionHasNoErrors()->assertRedirect(route('blog.index'));
        $post = BlogPost::firstOrFail();
        $this->assertEquals([$category->id], $post->categories->modelKeys());
        $this->assertEquals('Filter guide SEO', $post->seo['title']);
        $this->get(route('blog.edit', $post))->assertOk()->assertInertia(fn ($page) => $page->component('app/modules/blog/pages/Form', false)->where('post.id', $post->id)->has('authors', 1));
        $payload['slug'] = 'updated-guide';
        $payload['category_ids'] = [];
        $this->patch(route('blog.update', $post), $payload)->assertSessionHasNoErrors();
        $this->assertCount(0, $post->fresh()->categories);
        $this->get('/blog/water-filter-guide')->assertRedirect('/blog/updated-guide')->assertStatus(301);
        $this->get('/blog/updated-guide')->assertOk();
        $this->get('/sitemap-blog.xml')->assertOk()->assertSee('updated-guide');
    }

    public function test_drafts_future_posts_and_noindex_are_not_exposed_in_sitemap(): void
    {
        $author = BlogAuthor::create(['name' => 'Writer', 'slug' => 'writer']);
        foreach ([['draft', 'Draft', now()], ['scheduled', 'Published', now()->addDay()], ['live', 'Published', now()->subDay()]] as [$slug, $status, $time]) {
            BlogPost::create(['author_id' => $author->id, 'title' => $slug, 'slug' => $slug, 'status' => $status, 'published_at' => $time, 'seo' => ['robots' => 'noindex,follow']]);
        }
        $this->get('/blog')->assertOk()->assertInertia(fn ($page) => $page->component('app/modules/blog/pages/PublicIndex', false)->has('posts.data', 1)->where('posts.data.0.slug', 'live'));
        $this->get('/blog/draft')->assertNotFound();
        $this->get('/blog/scheduled')->assertNotFound();
        $this->get('/sitemap-blog.xml')->assertOk()->assertDontSee('/blog/live');
        $this->get('/blog/author/writer')->assertOk();
    }

    public function test_category_cycles_and_linked_category_deletion_are_rejected(): void
    {
        $this->actingAs(User::factory()->create());
        $parent = BlogCategory::create(['name' => 'Parent', 'slug' => 'parent']);
        $child = BlogCategory::create(['name' => 'Child', 'slug' => 'child', 'parent_id' => $parent->id]);
        $this->patch(route('blog.categories.update', $parent), ['name' => 'Parent', 'slug' => 'parent', 'parent_id' => $child->id])->assertSessionHasErrors('parent_id');
        $this->delete(route('blog.categories.destroy', $parent))->assertSessionHasErrors('category');
        $this->post(route('blog.store'), $this->payload(['category_ids' => [$child->id]]))->assertSessionHasNoErrors();
        $this->delete(route('blog.categories.destroy', $child))->assertSessionHasErrors('category');
        $this->assertDatabaseHas('blog_categories', ['id' => $child->id]);
    }

    public function test_author_profile_save_and_non_admin_access(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post(route('blog.authors.store'), ['name' => 'New Author', 'slug' => 'new-author', 'bio' => 'Our editor', 'social_links' => ['website' => 'https://example.com']])->assertSessionHasNoErrors();
        $author = BlogAuthor::firstOrFail();
        $this->patch(route('blog.authors.update', $author), ['name' => 'Updated Author', 'slug' => 'new-author', 'social_links' => []])->assertSessionHasNoErrors();
        $this->assertEquals('Updated Author', $author->fresh()->name);
        $this->actingAs(User::factory()->create(['is_admin' => false]));
        $this->get(route('blog.index'))->assertRedirect();
        $this->post(route('blog.store'), $this->payload())->assertRedirect();
        $this->assertDatabaseCount('blog_posts', 0);
    }

    public function test_duplicate_permalink_and_unsafe_author_links_are_rejected(): void
    {
        $this->actingAs(User::factory()->create());
        $payload = $this->payload();
        $this->post(route('blog.store'), $payload)->assertSessionHasNoErrors();
        $this->post(route('blog.store'), $payload)->assertSessionHasErrors('slug');
        $this->post(route('blog.authors.store'), ['name' => 'Unsafe', 'slug' => 'unsafe', 'social_links' => ['website' => 'javascript:alert(1)']])->assertSessionHasErrors('social_links.website');
    }
}
