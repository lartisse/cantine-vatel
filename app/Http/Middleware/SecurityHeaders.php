<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * En-têtes de sécurité HTTP sur toutes les réponses :
 * anti-clickjacking, anti-sniffing, politique de référent, permissions du navigateur,
 * Content-Security-Policy limitée à notre domaine, HSTS en HTTPS.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        $h = $response->headers;

        $h->set('X-Frame-Options', 'SAMEORIGIN');
        $h->set('X-Content-Type-Options', 'nosniff');
        $h->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        // Caméra autorisée uniquement pour notre domaine (scan QR au comptoir et liaison parent)
        $h->set('Permissions-Policy', 'camera=(self), microphone=(), geolocation=(), payment=(), usb=()');
        $h->set('Cross-Origin-Opener-Policy', 'same-origin');

        if (! $h->has('Content-Security-Policy') && ! $request->is('api/*')) {
            // Scripts et styles servis par l'application elle-même (aucun CDN). 'unsafe-inline' reste
            // nécessaire pour les petits scripts intégrés aux pages ; aucune source externe n'est autorisée.
            $h->set('Content-Security-Policy', implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline'",
                "style-src 'self' 'unsafe-inline'",
                "img-src 'self' data: blob:",
                "media-src 'self' blob:",
                "font-src 'self' data:",
                "connect-src 'self'",
                "worker-src 'self' blob:",
                "manifest-src 'self'",
                "frame-ancestors 'self'",
                "base-uri 'self'",
                "form-action 'self'",
                "object-src 'none'",
            ]));
        }

        if ($request->isSecure()) {
            $h->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
