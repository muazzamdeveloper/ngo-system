<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\SiteMedia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PageController extends Controller
{
    public function index(): View
    {
        return view('admin.pages.index', ['pages' => Page::orderBy('title')->get()]);
    }

    public function create(): View
    {
        return view('admin.pages.edit', ['page' => new Page(['is_published' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $page = new Page($data);
        $page->slug = $data['slug'] ?? $this->uniqueSlug($data['title']);
        $page->is_published = $request->boolean('is_published');

        if ($request->hasFile('image')) {
            $page->image_path = SiteMedia::store($request->file('image'));
        }

        $page->save();

        return redirect()->route('pages.edit', $page)->with('success', 'Page created.');
    }

    public function edit(Page $page): View
    {
        return view('admin.pages.edit', ['page' => $page]);
    }

    public function update(Request $request, Page $page): RedirectResponse
    {
        $data = $this->validated($request, $page);

        $page->fill($data);
        $page->slug = $data['slug'] ?? $page->slug;
        $page->is_published = $request->boolean('is_published');

        $old = [];

        if ($request->hasFile('image')) {
            $old[] = $page->image_path;
            $page->image_path = SiteMedia::store($request->file('image'));
        } elseif ($request->boolean('remove_image')) {
            $old[] = $page->image_path;
            $page->image_path = null;
        }

        $page->save();
        SiteMedia::delete($old);

        return redirect()->route('pages.edit', $page)->with('success', 'Page saved.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        $path = $page->image_path;
        $page->delete();
        SiteMedia::delete([$path]);

        return redirect()->route('pages.index')->with('success', 'Page deleted.');
    }

    private function validated(Request $request, ?Page $page = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/', Rule::unique('pages', 'slug')->ignore($page?->id)],
            'summary' => ['nullable', 'string', 'max:300'],
            'body' => ['nullable', 'string', 'max:60000'],
            'image' => SiteMedia::RULES,
            'remove_image' => ['nullable', 'boolean'],
        ], [
            'slug.regex' => 'The address can only have small letters, numbers and dashes, like about-us.',
        ]);
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'page';
        $slug = $base;
        $i = 2;

        while (Page::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}
