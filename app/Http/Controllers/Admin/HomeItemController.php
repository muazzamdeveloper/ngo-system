<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomeItem;
use App\Models\HomeSection;
use App\Rules\SiteUrl;
use App\Services\SiteMedia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeItemController extends Controller
{
    public function store(Request $request, HomeSection $section): RedirectResponse
    {
        abort_if($section->definition()['item'] === [], 404);

        $data = $this->validated($request, $section);

        $item = new HomeItem();
        $item->home_section_id = $section->id;
        $item->sort_order = $data['sort_order'] ?? ((int) $section->items()->max('sort_order') + 1);
        $this->fill($item, $section, $request, $data);

        return redirect()->route('home-sections.edit', $section)->with('success', ($section->definition()['item_name'] ?? 'Item') . ' added.');
    }

    public function edit(HomeItem $item): View
    {
        return view('admin.home.item-edit', ['item' => $item, 'section' => $item->section]);
    }

    public function update(Request $request, HomeItem $item): RedirectResponse
    {
        $section = $item->section;
        $data = $this->validated($request, $section, $item);
        $data['sort_order'] ??= $item->sort_order;

        $this->fill($item, $section, $request, $data);

        return redirect()->route('home-sections.edit', $section)->with('success', 'Saved.');
    }

    public function destroy(HomeItem $item): RedirectResponse
    {
        $section = $item->section;
        $path = $item->image_path;

        $item->delete();
        SiteMedia::delete([$path]);

        return redirect()->route('home-sections.edit', $section)->with('success', 'Deleted.');
    }

    private function validated(Request $request, HomeSection $section, ?HomeItem $item = null): array
    {
        $fields = $section->definition()['item'];

        $rules = ['sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'], 'remove_image' => ['nullable', 'boolean']];

        $all = [
            'title' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:3000'],
            'image' => SiteMedia::RULES,
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
        ];

        foreach ($fields as $field) {
            if ($field === 'link') {
                $rules['link_label'] = ['nullable', 'string', 'max:60'];
                $rules['link_url'] = ['nullable', new SiteUrl()];
            } else {
                $rules[$field] = $all[$field];
            }
        }

        $data = $request->validate($rules);

        // An item needs something to show: a title, or a picture (new or already saved).
        $hasPicture = $request->hasFile('image') || ($item && $item->image_path && ! $request->boolean('remove_image'));

        if (empty($data['title'] ?? null) && ! $hasPicture) {
            abort(back()->withErrors(['title' => 'Please give a title or a picture.'])->withInput());
        }

        return $data;
    }

    private function fill(HomeItem $item, HomeSection $section, Request $request, array $data): void
    {
        $fields = $section->definition()['item'];
        $old = [];

        foreach (['title', 'subtitle', 'body'] as $field) {
            if (in_array($field, $fields, true)) {
                $item->{$field} = $data[$field] ?? null;
            }
        }

        if (in_array('link', $fields, true)) {
            $item->link_label = $data['link_label'] ?? null;
            $item->link_url = $data['link_url'] ?? null;
        }

        if (in_array('rating', $fields, true)) {
            $item->rating = $data['rating'] ?? null;
        }

        if (in_array('image', $fields, true)) {
            if ($request->hasFile('image')) {
                $old[] = $item->image_path;
                $item->image_path = SiteMedia::store($request->file('image'));
            } elseif ($request->boolean('remove_image')) {
                $old[] = $item->image_path;
                $item->image_path = null;
            }
        }

        $item->is_active = $request->boolean('is_active');
        $item->sort_order = $data['sort_order'] ?? $item->sort_order;
        $item->save();

        SiteMedia::delete($old);
    }
}
