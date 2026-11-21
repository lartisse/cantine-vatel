<?php

if (! function_exists('fcfa')) {
    function fcfa(int|float|null $n): string
    {
        return number_format((float) $n, 0, ',', "\u{202F}").' FCFA';
    }
}

if (! function_exists('num')) {
    function num(int|float|null $n): string
    {
        return number_format((float) $n, 0, ',', "\u{202F}");
    }
}

if (! function_exists('ic')) {
    function ic(string $name, string $class = 'ic'): string
    {
        return '<svg class="'.$class.'" aria-hidden="true"><use href="#i-'.$name.'"></use></svg>';
    }
}

if (! function_exists('stars')) {
    /** Étoiles + note (style de la maquette). */
    function stars(?float $rating, ?int $count = null): string
    {
        if (! $rating) {
            return '<span class="muted xs">'.e(__('Pas encore noté')).'</span>';
        }
        $full = (int) round($rating);
        $html = '<span class="stars">'.str_repeat(ic('star', 'ic-star'), $full).str_repeat(ic('star', 'ic-off'), 5 - $full).'</span>';

        return $html.' <span class="xs muted">'.number_format($rating, 1, ',', '').($count !== null ? ' ('.$count.')' : '').'</span>';
    }
}

if (! function_exists('allergen_label')) {
    function allergen_label(string $k): string
    {
        return __(config('cantine.allergens')[$k] ?? $k);
    }
}

if (! function_exists('slot_label')) {
    function slot_label(string $slot): string
    {
        return str_replace(':', 'h', $slot);
    }
}
