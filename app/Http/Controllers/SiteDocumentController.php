<?php

namespace App\Http\Controllers;

use App\Services\SiteDocument;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Serves uploaded legal PDFs. Only the random names this application creates are accepted. */
class SiteDocumentController extends Controller
{
    public function show(string $file): StreamedResponse
    {
        abort_unless(preg_match('/^[A-Za-z0-9]{20,64}\.pdf$/', $file) === 1, 404);

        $path = SiteDocument::FOLDER . '/' . $file;
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="document.pdf"',
            'Cache-Control' => 'public, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
