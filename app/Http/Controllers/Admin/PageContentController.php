<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageBlock;
use App\Services\SiteMedia;
use App\Support\HtmlCleaner;
use App\Support\PageContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/** Edits the text, rich text and images of the pages defined in config/site_pages.php. */
class PageContentController extends Controller
{
    public function index(): View
    {
        return view('admin.content.index', ['pages' => config('site_pages', [])]);
    }

    public function edit(string $page): View
    {
        abort_unless(PageContent::exists($page), 404);

        $saved = PageBlock::query()->where('page_key', $page)->get()->keyBy('block_key');

        $sections = [];
        foreach (PageContent::sections($page) as $section) {
            foreach ($section['blocks'] as $key => $def) {
                $row = $saved->get($key);
                $section['blocks'][$key] = $def + [
                    'value' => $row?->value ?? $def['default'],
                    'image_url' => SiteMedia::url($row?->image_path),
                ];
            }
            $sections[] = $section;
        }

        return view('admin.content.edit', [
            'page' => $page,
            'title' => PageContent::definition($page)['title'],
            'sections' => $sections,
        ]);
    }

    public function update(Request $request, string $page): RedirectResponse
    {
        abort_unless(PageContent::exists($page), 404);

        $definitions = PageContent::blocks($page);

        $request->validate([
            'blocks' => ['nullable', 'array'],
            'images' => ['nullable', 'array'],
            'images.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove' => ['nullable', 'array'],
        ]);

        foreach ($definitions as $key => $def) {
            $row = PageBlock::query()->firstOrNew(['page_key' => $page, 'block_key' => $key]);

            if (in_array($def['type'], ['text', 'richtext'], true)) {
                $value = (string) data_get($request->input('blocks', []), $key, '');
                $rules = $def['type'] === 'text' ? 'max:500' : 'max:20000';
                validator(['v' => $value], ['v' => [$rules]])->validate();
                $row->value = $def['type'] === 'richtext' ? HtmlCleaner::clean($value) : strip_tags($value);
            }

            if ($def['type'] === 'image') {
                $old = $row->image_path;

                if ($file = $request->file("images.{$key}")) {
                    $row->image_path = SiteMedia::store($file);
                } elseif ($request->boolean("remove.{$key}")) {
                    $row->image_path = null;
                }

                if ($old && $old !== $row->image_path) {
                    SiteMedia::delete([$old]);
                }
            }

            $row->page_key = $page;
            $row->block_key = $key;
            $row->save();
        }

        Log::info('Website page content saved', ['page' => $page, 'user_id' => $request->user()->id]);

        return redirect()->route('content.edit', $page)->with('success', 'Page content saved.');
    }
}
