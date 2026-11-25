<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteLink;
use App\Rules\SiteUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SiteLinkController extends Controller
{
    public function index(): View
    {
        return view('admin.site-links.index', [
            'links' => SiteLink::orderBy('group')->orderBy('sort_order')->orderBy('id')->get()->groupBy('group'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['sort_order'] = $data['sort_order'] ?? ((int) SiteLink::where('group', $data['group'])->max('sort_order') + 1);

        SiteLink::create($data);

        return redirect()->route('site-links.index')->with('success', 'Link added.');
    }

    public function edit(SiteLink $link): View
    {
        return view('admin.site-links.edit', ['link' => $link]);
    }

    public function update(Request $request, SiteLink $link): RedirectResponse
    {
        $data = $this->validated($request);
        $data['sort_order'] ??= $link->sort_order;

        $link->update($data);

        return redirect()->route('site-links.index')->with('success', 'Link updated.');
    }

    public function destroy(SiteLink $link): RedirectResponse
    {
        $link->delete();

        return redirect()->route('site-links.index')->with('success', 'Link deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'group' => ['required', Rule::in(array_keys(SiteLink::GROUPS))],
            'label' => ['required', 'string', 'max:60'],
            'url' => ['required', new SiteUrl()],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['new_tab'] = $request->boolean('new_tab');

        return $data;
    }
}
