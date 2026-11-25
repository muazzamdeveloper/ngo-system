<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TemplateAsset;
use App\Services\SiteMedia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Logos, signatures, seals and certificate templates used when issuing documents.
 * Each asset has one current file; uploading again replaces it.
 */
class TemplateController extends Controller
{
    public function index(): View
    {
        Gate::authorize('templates.manage');

        $assets = TemplateAsset::query()->get()->keyBy('key');

        return view('admin.templates.index', [
            'definitions' => TemplateAsset::ASSETS,
            'assets' => $assets,
            'missingRequired' => collect(TemplateAsset::ASSETS)
                ->filter(fn ($def, $key) => $def[1] && blank($assets->get($key)?->path))
                ->keys(),
        ]);
    }

    public function upload(Request $request, string $key): RedirectResponse
    {
        Gate::authorize('templates.manage');
        abort_unless(array_key_exists($key, TemplateAsset::ASSETS), 404);

        $file = $request->validate([
            'file' => ['required', 'file', 'mimes:png,jpg,jpeg', 'mimetypes:image/png,image/jpeg', 'max:5120'],
        ])['file'];

        $asset = TemplateAsset::query()->firstOrNew(['key' => $key]);
        $old = $asset->path;

        $asset->fill([
            'path' => SiteMedia::store($file),
            'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
        ])->save();

        if ($old) {
            SiteMedia::delete([$old]);
        }

        return back()->with('success', TemplateAsset::ASSETS[$key][0] . ' uploaded.');
    }

    public function destroy(string $key): RedirectResponse
    {
        Gate::authorize('templates.manage');
        abort_unless(array_key_exists($key, TemplateAsset::ASSETS), 404);

        $asset = TemplateAsset::query()->where('key', $key)->first();

        if ($asset?->path) {
            SiteMedia::delete([$asset->path]);
        }

        $asset?->delete();

        return back()->with('success', TemplateAsset::ASSETS[$key][0] . ' removed.');
    }
}
