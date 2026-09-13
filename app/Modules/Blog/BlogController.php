<?php
namespace App\Modules\Blog;

use App\Http\Controllers\Controller;
use App\Modules\Blog\Models\{BlogPost, BlogCategory, BlogAuthor};
use App\Support\Content\RichTextSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only('search', 'category', 'status');
        return Inertia::render('app/modules/blog/pages/Index', [
            'posts' => BlogPost::with(['author:id,name', 'categories:id,name'])
                ->when($request->filled('search'), fn ($q) => $q->where('title', 'like', '%'.$request->string('search').'%'))
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
                ->when($request->filled('category'), fn ($q) => $q->whereHas('categories', fn ($c) => $c->whereKey($request->integer('category'))))
                ->latest()->paginate(20)->withQueryString(),
            'categories' => BlogCategory::orderBy('name')->get(), 'filters' => $filters,
        ]);
    }

    public function form(?BlogPost $post = null)
    {
        return Inertia::render('app/modules/blog/pages/Form', [
            'post' => $post?->load('categories'),
            'categories' => BlogCategory::orderBy('name')->get(),
            'authors' => BlogAuthor::orderBy('name')->get(),
        ]);
    }

    public function save(Request $request, ?BlogPost $post = null)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('blog_posts')->ignore($post?->id)],
            'author_id' => ['required', 'exists:blog_authors,id'],
            'category_ids' => ['array'], 'category_ids.*' => ['integer', 'distinct', 'exists:blog_categories,id'],
            'excerpt' => ['nullable', 'string', 'max:1000'], 'content' => ['nullable', 'string', 'max:200000'],
            'image_path' => ['nullable', 'string', 'max:2048'], 'gallery' => ['array', 'max:20'], 'gallery.*' => ['string', 'max:2048'],
            'tags' => ['nullable', 'string', 'max:1000'], 'status' => ['required', Rule::in(['Draft', 'Published'])],
            'published_at' => ['nullable', 'date'], 'seo' => ['array'],
            'seo.title' => ['nullable', 'string', 'max:160'], 'seo.description' => ['nullable', 'string', 'max:320'],
            'seo.focus_keyword' => ['nullable', 'string', 'max:255'], 'seo.canonical' => ['nullable', 'url:http,https', 'max:2048'],
            'seo.robots' => ['required', Rule::in(['index,follow', 'noindex,follow', 'noindex,nofollow'])],
            'seo.og_title' => ['nullable', 'string', 'max:160'], 'seo.og_description' => ['nullable', 'string', 'max:320'],
            'seo.og_image' => ['nullable', 'string', 'max:2048'], 'seo.twitter_image' => ['nullable', 'string', 'max:2048'],
        ]);
        if (DB::table('blog_redirects')->where('slug', $data['slug'])->when($post, fn ($q) => $q->where('blog_post_id', '!=', $post->id))->exists()) {
            throw ValidationException::withMessages(['slug' => 'This permalink is reserved by an existing blog redirect.']);
        }
        DB::transaction(function () use ($data, &$post) {
            $categoryIds = $data['category_ids'] ?? [];
            unset($data['category_ids']);
            $data['content'] = app(RichTextSanitizer::class)->sanitize($data['content'] ?? null);
            $data['published_at'] = $data['published_at'] ?? ($data['status'] === 'Published' ? now() : null);
            if ($post && $post->slug !== $data['slug']) {
                DB::table('blog_redirects')->where('slug', $data['slug'])->where('blog_post_id', $post->id)->delete();
                DB::table('blog_redirects')->updateOrInsert(['slug' => $post->slug], ['blog_post_id' => $post->id]);
            }
            if ($post) $post->update($data); else $post = BlogPost::create($data);
            $post->categories()->sync($categoryIds);
        });
        return to_route('blog.index')->with('success', 'Blog saved successfully.');
    }

    public function destroy(BlogPost $post)
    {
        $post->delete();
        return back()->with('success', 'Blog deleted successfully.');
    }

    public function categories()
    {
        return Inertia::render('app/modules/blog/pages/Categories', ['categories' => BlogCategory::withCount('posts')->orderBy('name')->get()]);
    }

    public function saveCategory(Request $request, ?BlogCategory $category = null)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('blog_categories')->ignore($category?->id)],
            'parent_id' => ['nullable', 'integer', 'exists:blog_categories,id'],
            'description' => ['nullable', 'string', 'max:10000'], 'image_path' => ['nullable', 'string', 'max:2048'],
        ]);
        $parent = $data['parent_id'] ?? null;
        $seen = [];
        while ($parent) {
            if ($parent == $category?->id || in_array($parent, $seen)) throw ValidationException::withMessages(['parent_id' => 'Choose a parent outside this category’s descendants.']);
            $seen[] = $parent;
            $parent = BlogCategory::find($parent)?->parent_id;
        }
        $data['description'] = app(RichTextSanitizer::class)->sanitize($data['description'] ?? null);
        if ($category) $category->update($data); else BlogCategory::create($data);
        return back()->with('success', 'Blog category saved.');
    }

    public function deleteCategory(BlogCategory $category)
    {
        if ($category->posts()->exists() || BlogCategory::where('parent_id', $category->id)->exists()) {
            throw ValidationException::withMessages(['category' => 'Move linked blogs and child categories before deleting this category.']);
        }
        $category->delete();
        return back()->with('success', 'Category deleted.');
    }

    public function saveAuthor(Request $request, ?BlogAuthor $author = null)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('blog_authors')->ignore($author?->id)],
            'designation' => ['nullable', 'string', 'max:150'], 'bio' => ['nullable', 'string', 'max:5000'],
            'image_path' => ['nullable', 'string', 'max:2048'], 'social_links' => ['array'],
            'social_links.*' => ['nullable', 'url:http,https', 'max:2048'],
        ]);
        if ($author) $author->update($data); else BlogAuthor::create($data);
        return back()->with('success', 'Author profile saved.');
    }

    public function listing(Request $request, ?string $category = null, ?string $author = null)
    {
        $categoryModel = $category ? BlogCategory::where('slug', $category)->firstOrFail() : null;
        $authorModel = $author ? BlogAuthor::where('slug', $author)->firstOrFail() : null;
        return Inertia::render('app/modules/blog/pages/PublicIndex', [
            'posts' => BlogPost::published()->with(['author:id,name,slug', 'categories:id,name,slug'])
                ->select(['id', 'title', 'slug', 'excerpt', 'image_path', 'author_id', 'published_at'])
                ->when($categoryModel, fn ($q) => $q->whereHas('categories', fn ($c) => $c->whereKey($categoryModel->id)))
                ->when($authorModel, fn ($q) => $q->where('author_id', $authorModel->id))
                ->when($request->filled('search'), fn ($q) => $q->where('title', 'like', '%'.$request->string('search').'%'))
                ->latest('published_at')->paginate(12)->withQueryString(),
            'categories' => BlogCategory::orderBy('name')->get(['id', 'name', 'slug']),
            'category' => $categoryModel, 'author' => $authorModel, 'search' => $request->input('search', ''),
        ]);
    }

    public function show(string $slug)
    {
        $post = BlogPost::published()->where('slug', $slug)->with(['author', 'categories'])->first();
        if (!$post) {
            $id = DB::table('blog_redirects')->where('slug', $slug)->value('blog_post_id');
            $redirect = $id ? BlogPost::published()->find($id) : null;
            if ($redirect) return redirect()->route('blog.public.show', $redirect->slug, 301);
            abort(404);
        }
        return Inertia::render('app/modules/blog/pages/PublicShow', [
            'post' => $post,
            'related' => BlogPost::published()->whereKeyNot($post->id)
                ->whereHas('categories', fn ($q) => $q->whereIn('blog_categories.id', $post->categories->modelKeys()))
                ->latest('published_at')->limit(4)->get(['id', 'title', 'slug', 'image_path']),
        ]);
    }

    public function sitemap()
    {
        $posts = BlogPost::published()->where('seo->robots', 'index,follow')->get(['slug', 'updated_at']);
        return response()->view('sitemaps.blog', compact('posts'))->header('Content-Type', 'application/xml');
    }
}
