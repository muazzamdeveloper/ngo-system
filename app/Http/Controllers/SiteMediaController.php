<?php

namespace App\Http\Controllers;

use App\Services\SiteMedia;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SiteMediaController extends Controller
{
    public function show(string $file): StreamedResponse
    {
        // Only the random file names this application creates; anything else is a 404.
        abort_unless(preg_match('/^[A-Za-z0-9]{20,64}\.(png|jpe?g|webp)$/', $file) === 1, 404);

        $path = SiteMedia::FOLDER . '/' . $file;

        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'public, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
