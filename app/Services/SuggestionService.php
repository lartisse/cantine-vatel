<?php

namespace App\Services;

use App\Models\Dish;
use App\Models\Menu;
use App\Models\Reservation;
use App\Models\ReservationItem;
use App\Models\Review;
use App\Models\User;

/** Suggestions personnalisées par règles (historique, notes, allergies, régimes) + « Chef de la semaine ». */
class SuggestionService
{
    /**
     * Un plat suggéré par catégorie (entrée, plat, dessert, boisson) pour le prochain jour réservable :
     * note moyenne + plats déjà appréciés + régime, en excluant les allergènes du profil et les plats épuisés.
     *
     * @return array{menu: Menu, items: array<string, array{dish: Dish, reason: string}>}|null
     */
    public function forUser(User $u): ?array
    {
        $liked = ReservationItem::whereHas('reservation', fn ($q) => $q->where('user_id', $u->id)->where('status', 'served'))->pluck('dish_id')->unique()->all();
        $ratings = Review::where('status', 'visible')->whereNotNull('dish_id')->groupBy('dish_id')->selectRaw('dish_id, AVG(rating) as avg')->pluck('avg', 'dish_id');
        $menus = Menu::with('items.dish')->whereDate('date', '>=', today())->where('is_published', true)->orderBy('date')->limit(5)->get();
        $service = app(ReservationService::class);
        $without = $u->allergyList() ? ' ('.__('sans').' '.implode(', ', array_map(fn ($a) => mb_strtolower(allergen_label($a)), $u->allergyList())).')' : '';

        foreach ($menus as $menu) {
            if (! $menu->isOpen() || Reservation::where('user_id', $u->id)->where('menu_id', $menu->id)->where('status', '!=', 'cancelled')->exists()) {
                continue;
            }
            $left = $service->portionsLeft($menu);
            $best = [];
            foreach ($menu->items as $item) {
                $d = $item->dish;
                if (! $d || $u->conflictsWith($d) || ($left[$d->id] ?? 0) <= 0) {
                    continue;
                }
                $rating = (float) ($ratings[$d->id] ?? 3.5);
                $wasLiked = in_array($d->id, $liked, true);
                $score = $rating + ($wasLiked ? 0.6 : 0)
                    + (in_array('vegetarien', $u->diets ?? [], true) && in_array('vegetarien', $d->diets ?? [], true) ? 1 : 0)
                    - (array_diff($u->diets ?? [], $d->diets ?? []) ? 2 : 0);
                if (! isset($best[$d->category]) || $score > $best[$d->category]['score']) {
                    $reason = ($wasLiked ? __('Vous l’avez déjà apprécié.').' ' : '')
                        .__('Noté :r/5 et compatible avec votre profil', ['r' => number_format($rating, 1, ',', '')]).$without.'.';
                    $best[$d->category] = ['dish' => $d, 'score' => $score, 'reason' => $reason];
                }
            }
            if ($best) {
                $ordered = [];
                foreach (['entree', 'plat', 'dessert', 'boisson'] as $c) {
                    if (isset($best[$c])) {
                        $ordered[$c] = $best[$c];
                    }
                }

                return ['menu' => $menu, 'items' => $ordered];
            }
        }

        return null;
    }

    /** Classement « Chef de la semaine » : meilleure note moyenne des 7 derniers jours (3 avis minimum). */
    public function chefOfTheWeek(int $limit = 5)
    {
        return Review::query()->where('reviews.status', 'visible')->whereNotNull('dish_id')
            ->where('reviews.created_at', '>=', now()->subDays(30))
            ->join('dishes', 'dishes.id', '=', 'reviews.dish_id')
            ->groupBy('dish_id', 'dishes.name', 'dishes.creator_name')
            ->havingRaw('COUNT(*) >= 3')
            ->selectRaw('dish_id, dishes.name, dishes.creator_name, AVG(rating) as avg, COUNT(*) as n')
            ->orderByDesc('avg')->limit($limit)->get();
    }

    /** Notes moyennes par plat (pour éviter N+1). */
    public static function ratings(): array
    {
        return Review::where('status', 'visible')->whereNotNull('dish_id')->groupBy('dish_id')->selectRaw('dish_id, AVG(rating) as avg, COUNT(*) as n')
            ->get()->mapWithKeys(fn ($r) => [$r->dish_id => [round($r->avg, 1), $r->n]])->all();
    }

    public static function decorate($dishes): void
    {
        $r = self::ratings();
        foreach ($dishes as $d) {
            if ($d instanceof Dish) {
                $d->avg_rating = $r[$d->id][0] ?? null;
                $d->rating_count = $r[$d->id][1] ?? 0;
            }
        }
    }
}
