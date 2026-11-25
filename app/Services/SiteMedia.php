<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Pictures used on the public website (slides, photos, signatures, gallery).
 * Files live on the private disk with random names; the public reaches them only through
 * /media/{file}, and that route serves nothing but files from this folder.
 */
class SiteMedia
{
    public const FOLDER = 'site-media';
    public const RULES = ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'mimetypes:image/png,image/jpeg,image/webp', 'max:5120'];

    public static function store(UploadedFile $file): string
    {
        return $file->store(self::FOLDER, 'local');
    }

    public static function url(?string $path): ?string
    {
        if (! $path || ! Storage::disk('local')->exists($path)) {
            return null;
        }

        return route('site.media', ['file' => basename($path)], false);
    }

    /** @param array<int, string|null> $paths */
    public static function delete(array $paths): void
    {
        foreach (array_filter($paths) as $path) {
            Storage::disk('local')->delete($path);
        }
    }
}
