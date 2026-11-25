<?php

namespace App\Support;

/** Turns a YouTube link into a privacy-friendly embed address. Returns null for anything else. */
class YouTube
{
    public static function embedUrl(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        if (! preg_match('~(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_\-]{6,})~', $url, $m)) {
            return null;
        }

        return 'https://www.youtube-nocookie.com/embed/' . $m[1];
    }
}
