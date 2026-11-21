<?php

namespace App\Http\Controllers\Super;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Support\MailSettings;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

/** Paramètres de l'application réservés au super administrateur (affichage côté convives). */
class SettingsController extends Controller
{
    public function index()
    {
        return view('super.settings', ['interval' => Settings::suggestionInterval(), 'smtp' => MailSettings::get(), 'smtpPassword' => MailSettings::maskedPassword()]);
    }

    /** Configuration SMTP : ressaisie du mot de passe du super admin, mot de passe SMTP chiffré, vide = conservé. */
    public function smtp(Request $request)
    {
        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'host' => ['required_if:enabled,1', 'nullable', 'string', 'max:190'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'encryption' => ['required', Rule::in(array_keys(MailSettings::ENCRYPTIONS))],
            'username' => ['nullable', 'string', 'max:190'],
            'password' => ['nullable', 'string', 'max:255'],
            'clear_password' => ['nullable', 'boolean'],
            'from_address' => ['required_if:enabled,1', 'nullable', 'email', 'max:190'],
            'from_name' => ['nullable', 'string', 'max:120'],
            'current_password' => ['required', 'current_password'],
        ], [
            'host.required_if' => 'Le serveur SMTP est obligatoire pour activer l’envoi.',
            'from_address.required_if' => 'L’adresse d’expédition est obligatoire pour activer l’envoi.',
            'from_address.email' => 'L’adresse d’expédition n’est pas valide.',
            'current_password.required' => 'Ressaisissez votre mot de passe pour confirmer.',
            'current_password.current_password' => 'Mot de passe incorrect',
        ]);
        $data['enabled'] = $request->boolean('enabled');
        $data['clear_password'] = $request->boolean('clear_password');
        MailSettings::save($data);
        $changed = ['serveur '.($data['host'] ?: '—').':'.$data['port'], $data['enabled'] ? 'activé' : 'désactivé'];
        if ($data['clear_password']) {
            $changed[] = 'mot de passe supprimé';
        } elseif (! empty($data['password'])) {
            $changed[] = 'mot de passe remplacé';
        }
        AuditLogger::log('settings', 'Settings', 'SMTP : '.implode(', ', $changed), 'Configuration e-mail (SMTP)');

        return back()->with('ok', 'Configuration SMTP enregistrée. Envoyez un e-mail de test pour la vérifier.');
    }

    /** Envoie un e-mail de test avec la configuration enregistrée (même désactivée). */
    public function smtpTest(Request $request)
    {
        $data = $request->validate(['to' => ['required', 'email', 'max:190']], ['to.email' => 'Adresse de test invalide.']);
        $previousMailer = config('mail.default');
        if (! MailSettings::apply(force: true)) {
            return back()->with('error', 'Renseignez d’abord le serveur SMTP.');
        }
        try {
            Mail::mailer('smtp')->raw(
                "Bonjour,\n\nCet e-mail confirme que la configuration SMTP de Cantine de Vatel fonctionne.\n\nEnvoyé le ".now()->format('d/m/Y à H:i').' par '.$request->user()->name.'.',
                fn ($m) => $m->to($data['to'])->subject('Cantine de Vatel — e-mail de test')
            );
            $ok = true;
            $message = 'E-mail de test envoyé à '.$data['to'];
        } catch (\Throwable $e) {
            $ok = false;
            $message = 'Échec : '.mb_strimwidth($e->getMessage(), 0, 220, '…');
        } finally {
            if (! MailSettings::get()['enabled']) {
                config(['mail.default' => $previousMailer]); // configuration non activée : on revient au mailer du .env
            }
        }
        Settings::set('smtp_tested_at', now()->toDateTimeString());
        Settings::set('smtp_test_ok', $ok ? 1 : 0);
        Settings::set('smtp_test_message', $message);
        AuditLogger::log('settings', 'Settings', 'Test SMTP : '.($ok ? 'réussi' : 'échoué'), 'Configuration e-mail (SMTP)');

        return back()->with($ok ? 'ok' : 'error', $message);
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
