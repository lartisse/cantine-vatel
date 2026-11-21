<?php

namespace App\Http\Controllers\Super;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Support\Settings;
use Illuminate\Http\Request;

/** Paramètres de l'application réservés au super administrateur (affichage côté convives). */
class SettingsController extends Controller
{
    public function index()
    {
        return view('super.settings', ['interval' => Settings::suggestionInterval()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate(['suggestion_interval' => ['required', 'integer', 'min:0', 'max:60']], [
            'suggestion_interval.max' => 'Le délai ne peut pas dépasser 60 secondes.',
        ]);
        $old = Settings::suggestionInterval();
        Settings::set('suggestion_interval', (int) $data['suggestion_interval']);
        AuditLogger::log('settings', 'Settings', "Défilement des suggestions : {$old} s → {$data['suggestion_interval']} s", 'Carrousel des suggestions');

        return back()->with('ok', $data['suggestion_interval'] ? 'Défilement automatique : toutes les '.$data['suggestion_interval'].' s' : 'Défilement automatique désactivé');
    }
}
