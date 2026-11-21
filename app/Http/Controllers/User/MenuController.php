<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Dish;
use App\Models\Menu;
use App\Models\Reservation;
use App\Models\Review;
use App\Services\ReservationService;
use App\Services\SuggestionService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/** U2 Menu de la semaine (filtres) et U3 fiche plat. */
class MenuController extends Controller
{
    public function index(Request $request, ReservationService $service)
    {
        $u = $request->user();
        $week = (int) $request->query('semaine', 0);
        $monday = today()->startOfWeek()->addWeeks($week);
        $menus = Menu::whereBetween('date', [$monday, $monday->copy()->addDays(4)])->where('is_published', true)->orderBy('date')->get();
        $dayParam = $request->query('jour');
        $menu = $dayParam ? $menus->first(fn ($m) => $m->date->toDateString() === $dayParam) : null;
        $menu ??= $menus->first(fn ($m) => $m->date->isToday()) ?? $menus->first(fn ($m) => $m->date->gte(today())) ?? $menus->first();

        $cat = $request->query('cat', 'all');
        $diet = $request->query('regime', 'all');
        $compat = $request->boolean('compatible');
        $groups = [];
        $left = [];
        $res = null;
        $menuReviews = collect();
        $canReview = false;
        if ($menu) {
            $menu->load('items.dish');
            $left = $service->portionsLeft($menu);
            $dishes = $menu->items->pluck('dish')->filter();
            SuggestionService::decorate($dishes);
            foreach (['entree', 'plat', 'dessert', 'boisson'] as $c) {
                if ($cat !== 'all' && $cat !== $c) {
                    continue;
                }
                $list = $menu->items->filter(fn ($i) => $i->dish && $i->dish->category === $c
                    && ($diet === 'all' || in_array($diet, $i->dish->diets ?? [], true))
                    && (! $compat || ! $u->conflictsWith($i->dish)));
                if ($list->count()) {
                    $groups[$c] = $list;
                }
            }
            $res = Reservation::where('user_id', $u->id)->where('menu_id', $menu->id)->where('status', '!=', 'cancelled')->first();
            $menuReviews = Review::with('user')->where('menu_id', $menu->id)->whereNull('dish_id')->where('status', 'visible')->latest()->get();
            $canReview = $res && ! $menu->date->isFuture() && ! Review::where('user_id', $u->id)->where('menu_id', $menu->id)->exists();
        }

        return view('user.menu', compact('u', 'menus', 'menu', 'groups', 'left', 'cat', 'diet', 'compat', 'res', 'week', 'monday', 'menuReviews', 'canReview'));
    }

    public function dish(Request $request, Dish $plat)
    {
        $u = $request->user();
        SuggestionService::decorate([$plat]);
        $plat->load('ingredients');
        $reviews = $plat->reviews()->with('user')->where('status', 'visible')->whereNotNull('comment')->latest()->limit(3)->get();
        $menu = $request->query('menu') ? Menu::find($request->query('menu')) : null;

        return view('user.dish', ['d' => $plat, 'u' => $u, 'reviews' => $reviews, 'menu' => $menu, 'conflicts' => $u->conflictsWith($plat)]);
    }
}
