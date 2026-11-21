<?php

namespace App\Http\Controllers\Super;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Support\MailSettings;
use App\Support\SchoolClasses;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

/** Paramètres de l'application réservés au super administrateur (affichage côté convives). */
class SettingsController extends Controller
{
    public function index()
    {
        return view('super.settings', ['interval' => Settings::suggestionInterval(), 'smtp' => MailSettings::get(), 'smtpPassword' => MailSettings::maskedPassword(),
            'classes' => SchoolClasses::all(), 'proposed' => SchoolClasses::proposed(), 'appLinks' => Settings::appLinks(),
            'classCounts' => \App\Models\User::where('account_type', 'eleve')->whereNotNull('class_name')->where('status', '!=', 'trashed')->selectRaw('class_name, COUNT(*) as n')->groupBy('class_name')->pluck('n', 'class_name')->all()]);
    }

    /** Liens de téléchargement de l'application mobile, affichés sur l'accueil (vide = bouton masqué). */
    public function appLinks(Request $request)
    {
        $url = ['nullable', 'url:https', 'max:500'];
        $data = $request->validate(['android_url' => $url, 'ios_url' => $url], [
            'android_url.url' => 'Lien Android invalide : adresse complète en https:// attendue (Google Play ou fichier APK).',
            'ios_url.url' => 'Lien iOS invalide : adresse complète en https:// attendue (App Store ou TestFlight).',
        ]);
        $old = Settings::appLinks();
        Settings::set('app_android_url', $data['android_url'] ?? null);
        Settings::set('app_ios_url', $data['ios_url'] ?? null);
        $describe = fn ($v) => $v ?: 'aucun';
        AuditLogger::log('settings', 'Settings', 'Android : '.$describe($data['android_url'] ?? null).' · iOS : '.$describe($data['ios_url'] ?? null)
            .($old ? '' : ' (première configuration)'), 'Liens de l’application mobile');

        return back()->with('ok', 'Liens de l’application enregistrés');
    }

    /** Ajoute une ou plusieurs classes (séparées par des virgules ou des retours à la ligne). */
    public function classAdd(Request $request)
    {
        $request->validate(['names' => ['required', 'string', 'max:2000']], ['names.required' => 'Saisissez au moins une classe.']);
        $names = collect(preg_split('/[\n,;]+/', $request->input('names')))->map(fn ($n) => SchoolClasses::clean($n))->filter()->values();
        $invalid = $names->first(fn ($n) => ! preg_match(SchoolClasses::PATTERN, $n));
        if ($invalid !== null) {
            return back()->withErrors(['names' => "« {$invalid} » : nom invalide (30 caractères max ; lettres, chiffres, espaces, . ' - °)."])->withInput();
        }
        $before = SchoolClasses::all();
        $added = $names->reject(fn ($n) => SchoolClasses::match($n))->unique(fn ($n) => mb_strtolower($n))->values();
        if ($added->isEmpty()) {
            return back()->with('info', 'Ces classes existent déjà.');
        }
        SchoolClasses::save(array_merge($before, $added->all()));
        AuditLogger::log('settings', 'Settings', 'Classes ajoutées : '.$added->join(', '), 'Classes de l’école');

        return back()->with('ok', $added->count() === 1 ? 'Classe « '.$added->first().' » ajoutée' : $added->count().' classes ajoutées');
    }

    /** Retire une classe de la liste (les élèves déjà inscrits dans cette classe la conservent). */
    public function classRemove(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:40']]);
        $official = SchoolClasses::match($data['name']);
        abort_unless($official, 404);
        SchoolClasses::save(array_values(array_filter(SchoolClasses::all(), fn ($c) => $c !== $official)));
        AuditLogger::log('settings', 'Settings', 'Classe retirée : '.$official, 'Classes de l’école');

        return back()->with('ok', 'Classe « '.$official.' » retirée de la liste');
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
