<?php

// Public pages for the new admin modules. Loaded at top level from routes/web.php.

use App\Http\Controllers\ContactController;
use App\Http\Controllers\DonateController;
use App\Http\Controllers\PolicyPageController;
use App\Http\Controllers\PublicModuleController;
use App\Http\Controllers\SiteDocumentController;
use App\Http\Controllers\VolunteerController;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Route;

Route::get('/donate', [DonateController::class, 'create'])->name('donate');
Route::post('/donate', [DonateController::class, 'store'])->middleware('throttle:10,60')->name('donate.store');
Route::get('/donate/thanks', [DonateController::class, 'thanks'])->name('donate.thanks');

// Contact Us form (the page itself is /page/contact-us).
Route::post('/contact-us', [ContactController::class, 'store'])->middleware('throttle:10,60')->name('contact.store');

Route::get('/volunteer', [VolunteerController::class, 'create'])->name('volunteer');
Route::post('/volunteer', [VolunteerController::class, 'store'])->middleware('throttle:10,60')->name('volunteer.store');
Route::get('/volunteer/thanks', [VolunteerController::class, 'thanks'])->name('volunteer.thanks');

Route::get('/policy/{type}', [PolicyPageController::class, 'show'])->where('type', '[a-z]+')->name('policy.show');
Route::get('/documents/{file}', [SiteDocumentController::class, 'show'])->where('file', '[A-Za-z0-9.]+')->name('site.document');

// Web app manifest, only when the PWA is switched on in Site configuration.
Route::get('/manifest.webmanifest', function () {
    abort_unless(SiteSetting::get('pwa.enabled') === '1', 404);

    $name = SiteSetting::get('pwa.name') ?: config('app.name');
    $icon = SiteSetting::get('pwa.icon_path');
    $icons = $icon
        ? [['src' => route('site.media', ['file' => basename($icon)], false), 'sizes' => '512x512', 'type' => 'image/png']]
        : [];

    return response()->json([
        'name' => $name,
        'short_name' => SiteSetting::get('pwa.short_name') ?: mb_substr($name, 0, 12),
        'description' => SiteSetting::get('pwa.description'),
        'start_url' => url('/'),
        'display' => SiteSetting::get('pwa.display', 'standalone'),
        'theme_color' => SiteSetting::get('pwa.theme_color') ?: '#1e3a8a',
        'background_color' => SiteSetting::get('pwa.background_color') ?: '#ffffff',
        'icons' => $icons,
    ], 200, ['Content-Type' => 'application/manifest+json']);
})->name('pwa.manifest');

// Public list and detail pages for modules that set 'public' in config/admin.php.
foreach (config('admin.modules', []) as $key => $def) {
    if (! isset($def['public'])) {
        continue;
    }

    $path = $def['public']['path'];

    Route::get('/' . $path, [PublicModuleController::class, 'index'])->defaults('module', $key)->name("public.{$key}.index");

    if ($def['public']['detail'] ?? false) {
        Route::get('/' . $path . '/{slug}', [PublicModuleController::class, 'show'])
            ->defaults('module', $key)
            ->where('slug', '[a-z0-9-]+')
            ->name("public.{$key}.show");
    }
}
