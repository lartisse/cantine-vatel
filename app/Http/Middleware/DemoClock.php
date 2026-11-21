<?php

namespace App\Http\Middleware;

use App\Support\Clock;
use Closure;
use Illuminate\Http\Request;

/** Mode démo : fige l'horloge (date de démo + heure choisie dans la barre de démo). */
class DemoClock
{
    public function handle(Request $request, Closure $next)
    {
        Clock::apply($request->hasSession() ? $request->session()->get('demo_time') : null);

        return $next($request);
    }
}
