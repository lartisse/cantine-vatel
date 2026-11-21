<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Langue enregistrée sur le compte (espaces utilisateur et parent uniquement). */
class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $locale = 'fr';
        if ($user && in_array($user->role, ['user', 'parent'], true) && ! $request->is('admin*', 'super*')) {
            $locale = $user->locale === 'en' ? 'en' : 'fr';
        }
        app()->setLocale($locale);

        return $next($request);
    }
}
