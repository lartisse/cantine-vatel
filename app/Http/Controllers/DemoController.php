<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Barre de démonstration (CANTINE_DEMO=true uniquement) : changer de rôle et d'heure. */
class DemoController extends Controller
{
    public const ACCOUNTS = ['awa.kone@vatel.ci', 'jm.aka@vatel.ci', 'i.kone@gmail.com', 'gestion@cantine-vatel.ci', 'superadmin@cantine-vatel.ci', 'comptoir1@cantine-vatel.ci'];

    public function login(Request $request)
    {
        abort_unless(config('cantine.demo') && in_array($request->input('email'), self::ACCOUNTS, true), 404);
        $user = User::where('email', $request->input('email'))->firstOrFail();
        $time = $request->session()->get('demo_time');
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('demo_time', $time);

        return redirect($user->homeRoute());
    }

    public function time(Request $request)
    {
        abort_unless(config('cantine.demo'), 404);
        $request->validate(['time' => ['required', 'date_format:H:i']]);
        $request->session()->put('demo_time', $request->input('time'));

        return back()->with('info', 'Heure de démonstration : '.str_replace(':', 'h', $request->input('time')));
    }
}
