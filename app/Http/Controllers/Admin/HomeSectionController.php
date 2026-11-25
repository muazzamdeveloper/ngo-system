<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomeSection;
use App\Rules\SiteUrl;
use App\Services\SiteMedia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HomeSectionController extends Controller
{
    public function index(): View
    {
        return view('admin.home.index', [
            'sections' => HomeSection::withCount('items')->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(HomeSection::TYPES))],
            'title' => ['nullable', 'string', 'max:160'],
        ]);

        $section = new HomeSection(['type' => $data['type'], 'title' => $data['title'] ?? HomeSection::TYPES[$data['type']]['label']]);
        $section->is_active = true;
        $section->sort_order = (int) HomeSection::max('sort_order') + 1;
        $section->save();

        return redirect()->route('home-sections.edit', $section)->with('success', 'Section added. Fill it in below.');
    }

    public function edit(HomeSection $section): View
    {
        return view('admin.home.edit', ['section' => $section->load('items')]);
    }

    public function update(Request $request, HomeSection $section): RedirectResponse
    {
        $rules = [
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'remove_image' => ['nullable', 'boolean'],
            'remove_image2' => ['nullable', 'boolean'],
        ];

        $fieldRules = [
            'title' => ['nullable', 'string', 'max:160'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:6000'],
            'image' => SiteMedia::RULES,
            'image2' => SiteMedia::RULES,
            'count' => ['nullable', 'integer', 'min:1', 'max:12'],
            'map' => ['nullable', 'string', 'max:700', 'regex:#^https://(www\.)?google\.[a-z.]+/maps/embed\?#i'],
        ];

        foreach ($section->definition()['section'] as $field) {
            if ($field === 'link') {
                $rules['link_label'] = ['nullable', 'string', 'max:60'];
                $rules['link_url'] = ['nullable', new SiteUrl()];
            } elseif ($field === 'count') {
                $rules['item_limit'] = $fieldRules['count'];
            } else {
                $rules[$field] = $fieldRules[$field];
            }
        }

        $data = $request->validate($rules, [
            'map.regex' => 'Paste the "Embed a map" address from Google Maps (it starts with https://www.google.com/maps/embed).',
        ]);

        $old = [];

        foreach (['image' => 'image_path', 'image2' => 'image2_path'] as $field => $column) {
            if (! $section->hasField($field)) {
                continue;
            }

            if ($request->hasFile($field)) {
                $old[] = $section->{$column};
                $section->{$column} = SiteMedia::store($request->file($field));
            } elseif ($request->boolean('remove_' . $field)) {
                $old[] = $section->{$column};
                $section->{$column} = null;
            }
        }

        foreach (['title', 'subtitle', 'body'] as $field) {
            if ($section->hasField($field)) {
                $section->{$field} = $data[$field] ?? null;
            }
        }

        if ($section->hasField('link')) {
            $section->link_label = $data['link_label'] ?? null;
            $section->link_url = $data['link_url'] ?? null;
        }

        if ($section->hasField('count')) {
            $section->item_limit = $data['item_limit'] ?? 6;
        }

        if ($section->hasField('map')) {
            $section->link_url = $data['map'] ?? null;     // the map address is kept in the link column
        }

        $section->is_active = $request->boolean('is_active');
        $section->sort_order = $data['sort_order'] ?? $section->sort_order;
        $section->save();

        SiteMedia::delete($old);

        return redirect()->route('home-sections.edit', $section)->with('success', 'Section saved.');
    }

    public function destroy(HomeSection $section): RedirectResponse
    {
        $files = [$section->image_path, $section->image2_path, ...$section->items()->pluck('image_path')->all()];

        DB::transaction(fn () => $section->delete());

        SiteMedia::delete($files);

        return redirect()->route('home-sections.index')->with('success', 'Section deleted.');
    }
}
