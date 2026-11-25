<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Services\SiteMedia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Site configuration: SEO, PWA app, homepage popup, tracking codes and map.
 * Stored in site_settings. Tracking codes are only ever written by admins with site.manage.
 */
class SiteConfigController extends Controller
{
    /** Plain text settings: key => max length. */
    private const TEXT = [
        'seo.title' => 255, 'seo.description' => 500, 'seo.keywords' => 500, 'seo.author' => 150,
        'pwa.name' => 80, 'pwa.short_name' => 30, 'pwa.description' => 300,
        'pwa.theme_color' => 7, 'pwa.background_color' => 7,
        'popup.title' => 150, 'popup.message' => 1000, 'popup.button_text' => 40, 'popup.button_url' => 500,
        'map.embed_url' => 1000,
    ];

    public function edit(): View
    {
        Gate::authorize('site.manage');

        return view('admin.site-config.edit', [
            'values' => SiteSetting::allWithPrefix(''),
            'popupImage' => SiteMedia::url(SiteSetting::get('popup.image_path')),
            'pwaIcon' => SiteMedia::url(SiteSetting::get('pwa.icon_path')),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        Gate::authorize('site.manage');

        $rules = [
            'pwa.display' => ['required', 'in:standalone,fullscreen,minimal-ui,browser'],
            'pwa.enabled' => ['nullable', 'boolean'],
            'popup.enabled' => ['nullable', 'boolean'],
            'popup.image' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
            'pwa.icon' => ['nullable', 'file', 'mimes:png', 'max:2048', 'dimensions:min_width=192,min_height=192'],
            'popup.button_url' => ['nullable', 'string', 'max:500'],
            'tracking.ga' => ['nullable', 'string', 'max:5000'],
            'tracking.fb_pixel' => ['nullable', 'string', 'max:5000'],
            'tracking.gsc_meta' => ['nullable', 'string', 'max:500'],
        ];

        foreach (self::TEXT as $key => $max) {
            $rules[$key] = ['nullable', 'string', 'max:' . $max];
        }

        $data = $request->validate($rules);

        foreach (self::TEXT as $key => $max) {
            SiteSetting::put($key, filled(data_get($data, $key) ?? null) ? trim(data_get($data, $key)) : null);
        }

        // Colours must be real hex values or the theme falls back to the organization colour.
        foreach (['pwa.theme_color', 'pwa.background_color'] as $colour) {
            $value = SiteSetting::get($colour);
            if ($value !== null && ! preg_match('/^#[0-9A-Fa-f]{6}$/', $value)) {
                SiteSetting::put($colour, null);
            }
        }

        SiteSetting::put('pwa.enabled', $request->boolean('pwa.enabled') ? '1' : '0');
        SiteSetting::put('pwa.display', data_get($data, 'pwa.display'));
        SiteSetting::put('popup.enabled', $request->boolean('popup.enabled') ? '1' : '0');
        // Tracking code is injected into every public page, so only the super admin may change it.
        // A staff member with "site.manage" keeps the saved codes and cannot add their own.
        if ($request->user()->isSuperAdmin()) {
            SiteSetting::put('tracking.ga', data_get($data, 'tracking.ga') ?? null);
            SiteSetting::put('tracking.fb_pixel', data_get($data, 'tracking.fb_pixel') ?? null);
            SiteSetting::put('tracking.gsc_meta', data_get($data, 'tracking.gsc_meta') ?? null);
        }

        if ($request->hasFile('popup.image')) {
            $this->replaceFile('popup.image_path', SiteMedia::store($request->file('popup.image')));
        }

        if ($request->hasFile('pwa.icon')) {
            $this->replaceFile('pwa.icon_path', SiteMedia::store($request->file('pwa.icon')));
        }

        return redirect()->route('site-config.edit')->with('success', 'Site configuration saved.');
    }

    private function replaceFile(string $key, string $path): void
    {
        $old = SiteSetting::get($key);
        SiteSetting::put($key, $path);

        if ($old) {
            SiteMedia::delete([$old]);
        }
    }
}
