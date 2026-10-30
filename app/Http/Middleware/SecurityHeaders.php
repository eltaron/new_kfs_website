<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds baseline security headers to every web response.
 *
 * The Content-Security-Policy is sent as Report-Only by default because the
 * public layout relies on inline scripts/styles and several CDNs. Switch to
 * enforcement with CSP_ENFORCE=true once reports are clean.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! config('security.headers.enabled', true)) {
            return $response;
        }

        $headers = $response->headers;

        $headers->set('X-Frame-Options', 'SAMEORIGIN', false);
        $headers->set('X-Content-Type-Options', 'nosniff', false);
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin', false);
        // Microphone is needed by the voice-command / voice-complaint features.
        $headers->set(
            'Permissions-Policy',
            'microphone=(self), camera=(), geolocation=(self), payment=(), usb=(), interest-cohort=()',
            false
        );

        if ($request->isSecure()) {
            $headers->set(
                'Strict-Transport-Security',
                'max-age=' . (int) config('security.headers.hsts_max_age', 31536000) . '; includeSubDomains',
                false
            );
        }

        $cspHeader = config('security.headers.csp_enforce')
            ? 'Content-Security-Policy'
            : 'Content-Security-Policy-Report-Only';

        if (! $headers->has('Content-Security-Policy') && ! $headers->has('Content-Security-Policy-Report-Only')) {
            $headers->set($cspHeader, $this->contentSecurityPolicy());
        }

        return $response;
    }

    protected function contentSecurityPolicy(): string
    {
        $cdn = 'https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://unpkg.com https://code.jquery.com https://cdn.datatables.net';
        $google = 'https://translate.google.com https://translate.googleapis.com https://www.gstatic.com https://www.google.com https://maps.googleapis.com';
        $tiles = 'https://server.arcgisonline.com https://*.tile.openstreetmap.org https://*.basemaps.cartocdn.com https://nominatim.openstreetmap.org https://api.qrserver.com';

        $directives = [
            "default-src 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            "frame-ancestors 'self'",
            // No form-action: payment gateways receive cross-origin form posts.
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' {$cdn} {$google}",
            "style-src 'self' 'unsafe-inline' {$cdn} https://fonts.googleapis.com https://fonts.bunny.net https://www.gstatic.com",
            "font-src 'self' data: {$cdn} https://fonts.gstatic.com https://fonts.bunny.net",
            "img-src 'self' data: blob: https:",
            "connect-src 'self' {$cdn} {$tiles} {$google}",
            "frame-src 'self' https://www.youtube.com https://www.youtube-nocookie.com https://www.google.com https://maps.google.com",
            "media-src 'self' blob: data:",
            "worker-src 'self' blob:",
        ];

        if ($uri = config('security.headers.csp_report_uri')) {
            $directives[] = 'report-uri ' . $uri;
        }

        return implode('; ', $directives);
    }
}
