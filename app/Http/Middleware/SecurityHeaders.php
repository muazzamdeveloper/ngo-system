<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds browser security headers to every web page.
 *
 * - X-Frame-Options: other sites cannot embed the admin panel (clickjacking).
 * - X-Content-Type-Options: browsers must not guess a file type (nosniff).
 * - Referrer-Policy: links do not leak full member URLs to other sites.
 * - Permissions-Policy: the site does not ask for camera, microphone, location or payment access.
 * - Strict-Transport-Security: only sent over HTTPS, so the browser always uses HTTPS afterwards.
 *
 * Headers a controller has already set (for example on PDF and file downloads) are kept as they are.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = [
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=()',
        ];

        if ($request->isSecure()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000';
        }

        foreach ($headers as $name => $value) {
            if (! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        return $response;
    }
}
