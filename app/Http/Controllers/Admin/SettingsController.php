<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Support\Settings;
use Illuminate\Http\Request;

/** A10 Paramètres : créneaux et capacités, heure limite, seuil, période scolaire, mots interdits. */
class SettingsController extends Controller
{
    public function index()
    {
        return view('admin.settings', ['slots' => Settings::slots(), 'cutoff' => Settings::get('cutoff', '10:00'), 'threshold' => Settings::get('default_threshold', 1000),
            'period' => Settings::period(), 'words' => Settings::forbiddenWords(), 'refund' => Settings::get('refund_rule')]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'cutoff' => ['required', 'date_format:H:i'], 'default_threshold' => ['required', 'integer', 'min:0'],
            'slots' => ['required', 'array'], 'slots.*.time' => ['nullable', 'date_format:H:i'], 'slots.*.capacity' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'words' => ['nullable', 'string'], 'refund_rule' => ['nullable', 'string', 'max:200'],
        ]);
        $slots = [];
        foreach ($data['slots'] as $s) {
            if (! empty($s['time']) && isset($s['capacity'])) {
                $slots[$s['time']] = (int) $s['capacity'];
            }
        }
        ksort($slots);
        Settings::set('slots', $slots);
        Settings::set('cutoff', $data['cutoff']);
        Settings::set('default_threshold', $data['default_threshold']);
        Settings::set('refund_rule', $data['refund_rule'] ?? '');
        if (isset($data['words'])) {
            Settings::set('forbidden_words', collect(preg_split('/[\n,;]+/', $data['words']))->map(fn ($w) => trim($w))->filter()->unique()->values()->all());
        }
        AuditLogger::log('settings', 'Settings', 'Heure limite '.$data['cutoff'].' · '.count($slots).' créneaux', 'Paramètres de la cantine');

        return back()->with('ok', 'Paramètres enregistrés');
    }

    public function newPeriod(Request $request)
    {
        $data = $request->validate(['label' => ['required', 'string', 'max:80']]);
        $id = 'P'.now()->format('YmdHis');
        Settings::set('period', ['id' => $id, 'label' => $data['label']]);
        AuditLogger::log('new_period', 'Settings', $data['label'], 'Période scolaire');

        return back()->with('ok', 'Nouvelle période démarrée : les élèves confirmeront leur classe à leur prochaine connexion.');
    }
}
