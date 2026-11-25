<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TickerItem;
use App\Rules\SiteUrl;
use Illuminate\Http\RedirectResponse;
use App\Models\OrganizationSetting;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TickerItemController extends Controller
{
    public function index(): View
    {
        return view('admin.ticker.index', [
            'items' => TickerItem::orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    /** How many of the newest post titles follow the typed headlines (0 = none). */
    public function settings(Request $request): RedirectResponse
    {
        $data = $request->validate(['ticker_posts' => ['required', 'integer', 'min:0', 'max:10']]);

        $setting = OrganizationSetting::query()->first();
        $setting?->forceFill(['ticker_posts' => $data['ticker_posts']])->save();

        return redirect()->route('ticker.index')->with('success', 'Ticker setting saved.');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['sort_order'] = $data['sort_order'] ?? ((int) TickerItem::max('sort_order') + 1);

        TickerItem::create($data);

        return redirect()->route('ticker.index')->with('success', 'Headline added.');
    }

    public function edit(TickerItem $item): View
    {
        return view('admin.ticker.edit', ['item' => $item]);
    }

    public function update(Request $request, TickerItem $item): RedirectResponse
    {
        $data = $this->validated($request);
        $data['sort_order'] ??= $item->sort_order;

        $item->update($data);

        return redirect()->route('ticker.index')->with('success', 'Headline updated.');
    }

    public function destroy(TickerItem $item): RedirectResponse
    {
        $item->delete();

        return redirect()->route('ticker.index')->with('success', 'Headline deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'text' => ['required', 'string', 'max:160'],
            'url' => ['nullable', new SiteUrl()],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
