<?php

namespace App\Http\Controllers;

use App\Models\OrganizationSetting;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The logo and site icon are shown on public pages, so they are streamed to everyone.
 * They live on the private disk, so no other stored file becomes reachable.
 */
class BrandingController extends Controller
{
    public function logo(): StreamedResponse
    {
        return $this->stream(OrganizationSetting::current()->logo_path);
    }

    public function icon(): StreamedResponse
    {
        return $this->stream(OrganizationSetting::current()->favicon_path);
    }

    private function stream(?string $path): StreamedResponse
    {
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'public, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
