<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** Colour palettes for the public site. Stored as theme.primary / theme.accent and read by the branding head partial. */
class ThemeController extends Controller
{
    public const PALETTES = [
        'classic-navy' => ['Classic Navy & Orange', 'Original signature NGO look with authoritative navy headers and high-contrast orange action buttons.', '#0b2545', '#f97316'],
        'eco-green' => ['Eco Green & Orange', 'Nature and social sustainability theme with deep environmental green and vivid orange accents.', '#166534', '#f97316'],
        'forest-green' => ['Forest Green & Gold', 'Premium ecological prestige theme with rich evergreen tones and golden luxury accents.', '#14532d', '#ca8a04'],
        'royal-purple' => ['Royal Purple & Amber', 'Sophisticated and dignified palette with regal purple and radiant warm amber accents.', '#3b0764', '#f59e0b'],
        'deep-teal' => ['Deep Teal & Coral', 'Modern healthcare and community trust theme with calm oceanic teal and energetic coral highlights.', '#005b60', '#ff6f59'],
        'tiranga' => ['Patriotic Tiranga (Tricolor)', 'National pride theme inspired by the Indian flag: saffron, white and green.', '#138808', '#ff9933'],
        'royal-blue' => ['Royal Blue & Indigo', 'Corporate transparency and global foundation theme with bright royal blue and azure highlights.', '#1e3a8a', '#3b82f6'],
    ];

    public function index(): View
    {
        Gate::authorize('site.manage');

        return view('admin.themes.index', [
            'palettes' => self::PALETTES,
            'current' => SiteSetting::get('theme.key'),
            'primary' => SiteSetting::get('theme.primary'),
            'accent' => SiteSetting::get('theme.accent'),
        ]);
    }

    public function applyPalette(Request $request): RedirectResponse
    {
        Gate::authorize('site.manage');

        $key = $request->validate(['palette' => ['required', 'in:' . implode(',', array_keys(self::PALETTES))]])['palette'];
        [$name, , $primary, $accent] = self::PALETTES[$key];

        SiteSetting::put('theme.key', $key);
        SiteSetting::put('theme.primary', $primary);
        SiteSetting::put('theme.accent', $accent);

        return back()->with('success', "{$name} palette applied to the public site.");
    }

    public function customColours(Request $request): RedirectResponse
    {
        Gate::authorize('site.manage');

        $data = $request->validate([
            'primary' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        SiteSetting::put('theme.key', 'custom');
        SiteSetting::put('theme.primary', strtolower($data['primary']));
        SiteSetting::put('theme.accent', strtolower($data['accent']));

        return back()->with('success', 'Custom brand colours saved.');
    }
}
