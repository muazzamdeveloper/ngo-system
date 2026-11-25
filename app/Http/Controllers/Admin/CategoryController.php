<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.categories.index', ['categories' => Category::withCount('posts')->orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        Category::create([
            'name' => trim($data['name']),
            'slug' => $data['slug'] ?? $this->uniqueSlug($data['name']),
            'description' => $data['description'] ?? null,
        ]);

        return redirect()->route('categories.index')->with('success', 'Category added.');
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.edit', ['category' => $category]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $this->validated($request, $category);

        $category->name = trim($data['name']);
        $category->description = $data['description'] ?? null;

        if (! $category->isDefault()) {
            $category->slug = $data['slug'] ?? $category->slug;
        }

        $category->save();

        return redirect()->route('categories.index')->with('success', 'Category saved.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        abort_if($category->isDefault(), 403, 'The default category cannot be deleted.');

        DB::transaction(function () use ($category) {
            $default = Category::uncategorized();

            // Posts that would be left without a category move to "Uncategorized", as in WordPress.
            foreach ($category->posts()->with('categories:id')->get() as $post) {
                if ($post->categories->count() === 1) {
                    $post->categories()->attach($default->id);
                }
            }

            $category->delete();
        });

        return redirect()->route('categories.index')->with('success', 'Category deleted.');
    }

    private function validated(Request $request, ?Category $category = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'slug' => ['nullable', 'string', 'max:80', 'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/', Rule::unique('categories', 'slug')->ignore($category?->id)],
            'description' => ['nullable', 'string', 'max:255'],
        ], ['slug.regex' => 'The address can only have small letters, numbers and dashes.']);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        $i = 2;

        while (Category::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}
