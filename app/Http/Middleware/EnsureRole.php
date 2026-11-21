<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Usage : ->middleware('role:admin,super_admin'). Le super admin a aussi tous les droits admin. */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }
        if (in_array('admin', $roles, true) && $user->role === 'super_admin') {
            return $next($request);
        }
        abort_unless(in_array($user->role, $roles, true), 403, 'Accès refusé');

        return $next($request);
    }
}
