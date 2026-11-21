<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/** Paramètres modifiables (A10) : heure limite, créneaux, période scolaire, mots interdits, purge… */
class Settings
{
    protected static ?array $cache = null;

    public static function all(): array
    {
        if (static::$cache === null) {
            try {
                static::$cache = Setting::pluck('value', 'key')->all();
            } catch (\Throwable) {
                static::$cache = [];
            }
        }

        return static::$cache;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::all()[$key] ?? $default;
    }

    public static function json(string $key, mixed $default = null): mixed
    {
        $v = static::get($key);

        return $v === null ? $default : json_decode($v, true);
    }

    public static function set(string $key, mixed $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value]);
        static::$cache = null;
    }

    /** @return array<string,int> créneau => capacité */
    public static function slots(): array
    {
        return static::json('slots', config('cantine.slots'));
    }

    public static function forbiddenWords(): array
    {
        return static::json('forbidden_words', config('cantine.forbidden_words'));
    }

    public static function period(): array
    {
        return static::json('period', ['id' => '2026-T2', 'label' => '2e trimestre 2026-2027']);
    }

    /** Délai (secondes) du défilement automatique des suggestions ; 0 = désactivé. Par défaut : 5 s. */
    public static function suggestionInterval(): int
    {
        return max(0, min(60, (int) static::get('suggestion_interval', 5)));
    }

    /** Liens de téléchargement de l'application mobile (vides = bouton masqué). */
    public static function appLinks(): array
    {
        return array_filter(['android' => static::get('app_android_url'), 'ios' => static::get('app_ios_url')]);
    }

    public static function trashDays(): int
    {
        return (int) static::get('trash_days', config('cantine.trash_days'));
    }

    public static function flush(): void
    {
        static::$cache = null;
    }
}
