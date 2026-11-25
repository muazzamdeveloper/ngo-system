<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Services\SiteMedia;
use App\Support\HtmlCleaner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $manageAll = $user->can('posts.manage_all');

        // Writers see their own posts; people who manage all posts see everyone's.
        $base = Post::query()->when(! $manageAll, fn ($q) => $q->where('user_id', $user->id));

        $status = in_array($request->query('status'), [Post::STATUS_DRAFT, Post::STATUS_PUBLISHED], true) ? $request->query('status') : null;
        $search = mb_substr(trim((string) $request->query('q', '')), 0, 100);
        $categoryId = $request->integer('category') ?: null;

        $posts = (clone $base)
            ->with(['author:id,name', 'categories:id,name'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($categoryId, fn ($q) => $q->whereHas('categories', fn ($c) => $c->where('categories.id', $categoryId)))
            ->when($search !== '', fn ($q) => $q->where('title', 'like', '%' . addcslashes($search, '\\%_') . '%'))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.posts.index', [
            'posts' => $posts,
            'status' => $status,
            'search' => $search,
            'categoryId' => $categoryId,
            'categories' => Category::orderBy('name')->get(),
            'counts' => [
                'all' => (clone $base)->count(),
                'published' => (clone $base)->where('status', Post::STATUS_PUBLISHED)->count(),
                'draft' => (clone $base)->where('status', Post::STATUS_DRAFT)->count(),
            ],
            'manageAll' => $manageAll,
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.posts.edit', $this->formData(new Post(['status' => Post::STATUS_DRAFT]), $request->user()));
    }

    public function store(Request $request): RedirectResponse
    {
        $post = new Post();
        $post->user_id = $request->user()->id;

        $this->save($request, $post);

        return redirect()->route('posts.edit', $post)->with('success', $post->isPublished() ? 'Post published.' : 'Draft saved.');
    }

    public function edit(Request $request, Post $post): View
    {
        $this->authorizePost($request->user(), $post);

        return view('admin.posts.edit', $this->formData($post, $request->user()));
    }

    public function update(Request $request, Post $post): RedirectResponse
    {
        $this->authorizePost($request->user(), $post);

        $this->save($request, $post);

        return redirect()->route('posts.edit', $post)->with('success', $post->isPublished() ? 'Post updated.' : 'Draft saved.');
    }

    public function destroy(Request $request, Post $post): RedirectResponse
    {
        $this->authorizePost($request->user(), $post);

        $image = $post->featured_image_path;
        $post->delete();
        SiteMedia::delete([$image]);

        return redirect()->route('posts.index')->with('success', 'Post deleted.');
    }

    private function formData(Post $post, User $user): array
    {
        return [
            'post' => $post->loadMissing('categories'),
            'categories' => Category::orderBy('name')->get(),
            'selected' => $post->exists ? $post->categories->pluck('id')->all() : [],
            'canAddCategories' => $user->can('posts.manage_all'),
        ];
    }

    /** Your own posts, or any post if you manage all posts. */
    private function authorizePost(User $user, Post $post): void
    {
        abort_unless($user->can('posts.manage_all') || $post->user_id === $user->id, 403);
    }

    private function save(Request $request, Post $post): void
    {
        $user = $request->user();

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:200', 'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/', Rule::unique('posts', 'slug')->ignore($post->id)],
            'content' => ['nullable', 'string', 'max:300000'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::in([Post::STATUS_DRAFT, Post::STATUS_PUBLISHED])],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['integer', 'exists:categories,id'],
            'new_categories' => ['nullable', 'string', 'max:400'],
            'featured_image' => SiteMedia::RULES,
            'remove_featured_image' => ['nullable', 'boolean'],
        ], [
            'slug.regex' => 'The address can only have small letters, numbers and dashes.',
        ]);

        $post->title = trim($data['title']);
        $post->excerpt = filled($data['excerpt'] ?? null) ? trim($data['excerpt']) : null;
        $post->content = HtmlCleaner::clean($data['content'] ?? '');
        $post->slug = $data['slug'] ?? ($post->slug ?: $this->uniqueSlug($post->title));

        $wasPublished = $post->isPublished();
        $post->status = $data['status'];

        if ($post->isPublished() && ! $post->published_at) {
            $post->published_at = now();
        }

        $old = [];

        if ($request->hasFile('featured_image')) {
            $old[] = $post->featured_image_path;
            $post->featured_image_path = SiteMedia::store($request->file('featured_image'));
        } elseif ($request->boolean('remove_featured_image')) {
            $old[] = $post->featured_image_path;
            $post->featured_image_path = null;
        }

        DB::transaction(function () use ($post, $data, $user) {
            $post->save();

            $ids = collect($data['categories'] ?? [])->map(fn ($id) => (int) $id);

            // New categories can only be typed in by people who manage all posts (like WordPress editors).
            if ($user->can('posts.manage_all') && filled($data['new_categories'] ?? null)) {
                $names = collect(explode(',', $data['new_categories']))
                    ->map(fn ($name) => trim($name))->filter()->unique()->take(10);

                foreach ($names as $name) {
                    $ids->push(Category::findOrCreateByName($name)->id);
                }
            }

            $ids = $ids->unique()->values();

            if ($ids->isEmpty()) {
                $ids->push(Category::uncategorized()->id);
            }

            $post->categories()->sync($ids->all());
        });

        SiteMedia::delete($old);
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::limit(Str::slug($title) ?: 'post', 180, '');
        $slug = $base;
        $i = 2;

        while (Post::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}
