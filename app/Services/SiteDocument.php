<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * PDF files uploaded to the site (legal documents). Stored on the private disk under a random
 * name and served only through /documents/{file}.
 */
class SiteDocument
{
    public const FOLDER = 'site-documents';

    public static function store(UploadedFile $file): string
    {
        return $file->store(self::FOLDER, 'local');
    }

    public static function url(?string $path): ?string
    {
        if (! $path || ! Storage::disk('local')->exists($path)) {
            return null;
        }

        return route('site.document', ['file' => basename($path)], false);
    }

    public static function delete(array $paths): void
    {
        foreach (array_filter($paths) as $path) {
            Storage::disk('local')->delete($path);
        }
    }
}
