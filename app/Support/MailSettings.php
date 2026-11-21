<?php

namespace App\Support;

use Illuminate\Support\Facades\Crypt;

/**
 * Configuration SMTP saisie par le super administrateur (Paramètres de l'application).
 * Stockée dans la table `settings` ; le mot de passe est chiffré (Crypt::encryptString, APP_KEY),
 * seuls ses 4 derniers caractères sont gardés pour l'affichage masqué. Il n'est jamais renvoyé au navigateur.
 * Quand elle est activée, elle remplace la configuration MAIL_* du fichier .env.
 */
class MailSettings
{
    public const ENCRYPTIONS = ['tls' => 'TLS (STARTTLS, port 587)', 'ssl' => 'SSL (port 465)', 'none' => 'Aucun chiffrement'];

    /** Valeurs affichables (sans le mot de passe). */
    public static function get(): array
    {
        return [
            'enabled' => (bool) Settings::get('smtp_enabled', false),
            'host' => Settings::get('smtp_host', ''),
            'port' => (int) Settings::get('smtp_port', 587),
            'encryption' => Settings::get('smtp_encryption', 'tls'),
            'username' => Settings::get('smtp_username', ''),
            'has_password' => (bool) Settings::get('smtp_password'),
            'password_last4' => Settings::get('smtp_password_last4', ''),
            'from_address' => Settings::get('smtp_from_address', ''),
            'from_name' => Settings::get('smtp_from_name', config('app.name')),
            'tested_at' => Settings::get('smtp_tested_at'),
            'test_ok' => Settings::get('smtp_test_ok'),
            'test_message' => Settings::get('smtp_test_message'),
        ];
    }

    public static function save(array $data): void
    {
        foreach (['enabled', 'host', 'port', 'encryption', 'username', 'from_address', 'from_name'] as $k) {
            Settings::set('smtp_'.$k, $k === 'enabled' ? (int) ! empty($data[$k]) : ($data[$k] ?? ''));
        }
        if (! empty($data['clear_password'])) {
            Settings::set('smtp_password', null);
            Settings::set('smtp_password_last4', null);
        } elseif (isset($data['password']) && $data['password'] !== '') {
            Settings::set('smtp_password', Crypt::encryptString($data['password']));
            Settings::set('smtp_password_last4', mb_substr($data['password'], -4));
        }
        // Toute modification invalide le dernier test
        Settings::set('smtp_tested_at', null);
        Settings::set('smtp_test_ok', null);
        Settings::set('smtp_test_message', null);
    }

    public static function password(): ?string
    {
        $enc = Settings::get('smtp_password');
        if (! $enc) {
            return null;
        }
        try {
            return Crypt::decryptString($enc);
        } catch (\Throwable) {
            return null; // APP_KEY changée : le mot de passe doit être ressaisi
        }
    }

    /** Applique la configuration à Laravel (au démarrage et juste avant un e-mail de test). */
    public static function apply(bool $force = false): bool
    {
        $s = static::get();
        if ((! $s['enabled'] && ! $force) || ! $s['host']) {
            return false;
        }
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.scheme' => $s['encryption'] === 'ssl' ? 'smtps' : 'smtp',
            'mail.mailers.smtp.url' => null,
            'mail.mailers.smtp.host' => $s['host'],
            'mail.mailers.smtp.port' => $s['port'],
            'mail.mailers.smtp.username' => $s['username'] ?: null,
            'mail.mailers.smtp.password' => static::password(),
            'mail.mailers.smtp.timeout' => 15,
            'mail.from.address' => $s['from_address'] ?: config('mail.from.address'),
            'mail.from.name' => $s['from_name'] ?: config('app.name'),
        ]);
        if (app()->resolved('mail.manager')) {
            app('mail.manager')->purge('smtp'); // reconstruit le transport avec les nouvelles valeurs
        }

        return true;
    }

    public static function maskedPassword(): string
    {
        $s = static::get();

        return $s['has_password'] ? '••••••••'.$s['password_last4'] : 'Non configuré';
    }
}
