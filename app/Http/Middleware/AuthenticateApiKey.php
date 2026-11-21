<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Authentification de l'API par clé : en-tête « Authorization: Bearer cvk_… » ou « X-Api-Key: cvk_… ».
 * Usage : ->middleware('api.key:menus:read'). Vérifie la clé (hachée), son état, son expiration,
 * la portée demandée et la limite de requêtes par minute propre à la clé.
 */
class AuthenticateApiKey
{
    public function handle(Request $request, Closure $next, ?string $scope = null)
    {
        $request->headers->set('Accept', 'application/json');
        $plain = $request->bearerToken() ?: $request->header('X-Api-Key');
        $key = ApiKey::findByPlain($plain);

        if (! $key || ! $key->isUsable()) {
            return response()->json(['error' => 'unauthenticated', 'message' => 'Clé d’API absente, invalide, révoquée ou expirée.'], 401);
        }
        if ($scope && ! $key->allows($scope)) {
            return response()->json(['error' => 'forbidden', 'message' => "Cette clé n’a pas la portée « {$scope} »."], 403);
        }

        $bucket = 'api-key:'.$key->id;
        if (RateLimiter::tooManyAttempts($bucket, max(1, $key->rate_limit))) {
            return response()->json(['error' => 'too_many_requests', 'message' => 'Limite de requêtes atteinte.', 'retry_after' => RateLimiter::availableIn($bucket)], 429)
                ->header('Retry-After', RateLimiter::availableIn($bucket));
        }
        RateLimiter::hit($bucket, 60);

        $key->forceFill(['last_used_at' => now(), 'last_used_ip' => $request->ip(), 'requests_count' => $key->requests_count + 1])->saveQuietly();
        $request->attributes->set('api_key', $key);

        $response = $next($request);
        $response->headers->set('X-RateLimit-Limit', (string) $key->rate_limit);
        $response->headers->set('X-RateLimit-Remaining', (string) RateLimiter::remaining($bucket, $key->rate_limit));

        return $response;
    }
}
