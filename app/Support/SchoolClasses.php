<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Classes de l'école : liste gérée par le super administrateur (Paramètres de l'application).
 * Un élève choisit une classe de la liste ou saisit la sienne (« Autre ») si elle n'y figure pas.
 */
class SchoolClasses
{
    public const OTHER = '__autre__';

    /** Lettres, chiffres, espaces et . ' - ° (ex. « 3e A », « Tle D », « CM2 », « BTS 1 Hôtellerie »). */
    public const PATTERN = "/^[\p{L}\p{N}][\p{L}\p{N} .'°\-]{0,29}$/u";

    /** @return string[] */
    public static function all(): array
    {
        return Settings::json('classes', config('cantine.classes'));
    }

    public static function save(array $classes): void
    {
        $clean = collect($classes)->map(fn ($c) => self::clean($c))->filter()->unique(fn ($c) => mb_strtolower($c))
            ->sort(fn ($a, $b) => strnatcasecmp($a, $b))->values()->all();
        Settings::set('classes', $clean);
    }

    public static function clean(?string $name): string
    {
        return trim(preg_replace('/\s+/u', ' ', (string) $name));
    }

    /** Classe déjà connue (sans tenir compte de la casse ni des espaces) : on réutilise son orthographe officielle. */
    public static function match(string $name): ?string
    {
        $key = mb_strtolower(self::clean($name));
        foreach (self::all() as $c) {
            if (mb_strtolower($c) === $key) {
                return $c;
            }
        }

        return null;
    }

    /** Classes saisies par des élèves et absentes de la liste officielle, avec le nombre d'élèves. */
    public static function proposed(): array
    {
        $official = array_map('mb_strtolower', self::all());

        return User::where('account_type', 'eleve')->whereNotNull('class_name')->where('status', '!=', 'trashed')
            ->selectRaw('class_name, COUNT(*) as n')->groupBy('class_name')->orderBy('class_name')->get()
            ->reject(fn ($r) => in_array(mb_strtolower($r->class_name), $official, true))
            ->mapWithKeys(fn ($r) => [$r->class_name => (int) $r->n])->all();
    }

    /** Règles de validation des champs « class_name » (liste ou __autre__) et « class_other » (saisie libre). */
    public static function rules(): array
    {
        return [
            'class_name' => ['nullable', 'string', 'max:40'],
            'class_other' => ['nullable', 'required_if:class_name,'.self::OTHER, 'string', 'max:30', 'regex:'.self::PATTERN],
        ];
    }

    public static function messages(): array
    {
        return [
            'class_other.required_if' => 'Saisissez le nom de votre classe.',
            'class_other.regex' => 'Nom de classe invalide (lettres, chiffres, espaces, . \' - °).',
            'class_other.max' => 'Nom de classe trop long (30 caractères maximum).',
        ];
    }

    /** Classe finale à enregistrer à partir des données validées (null = non renseignée). */
    public static function resolve(array $data): ?string
    {
        $choice = $data['class_name'] ?? null;
        if ($choice === null || $choice === '') {
            return null;
        }
        if ($choice === self::OTHER) {
            $typed = self::clean($data['class_other'] ?? '');

            return self::match($typed) ?? $typed;
        }
        $official = self::match($choice);
        if (! $official) {
            throw ValidationException::withMessages(['class_name' => 'Classe inconnue : choisissez-la dans la liste ou « Autre ».']);
        }

        return $official;
    }
}
