<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Un compte avec un mot de passe provisoire doit le changer avant d'accéder au reste de l'application. */
class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user && $user->must_change_password && ! $request->routeIs('password.change', 'password.change.update', 'logout')) {
            return redirect()->route('password.change');
        }

        return $next($request);
    }
}
