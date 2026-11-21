<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\Menu;
use App\Models\Reservation;
use App\Models\Review;
use App\Services\ReservationService;
use App\Services\SuggestionService;
use App\Support\Settings;
use Illuminate\Http\Request;

/** A1 Tableau de bord : indicateurs, prévision anti-gaspi, impact, alertes. */
class DashboardController extends Controller
{
    public function index(Request $request, ReservationService $service, SuggestionService $suggestions)
    {
        $days = (int) $request->query('jours', 7) === 30 ? 30 : 7;
        $today = Menu::whereDate('date', today())->first();
        $todayRes = $today ? Reservation::where('menu_id', $today->id)->where('status', '!=', 'cancelled')->get() : collect();
        $yesterday = Menu::whereDate('date', '<', today())->orderByDesc('date')->first();
        $yRes = $yesterday ? Reservation::where('menu_id', $yesterday->id)->whereIn('status', ['served', 'missed'])->get() : collect();
        $pickupRate = $yRes->count() ? round($yRes->where('status', 'served')->count() / $yRes->count() * 100) : null;
        $avg = Review::where('status', 'visible')->where('created_at', '>=', now()->subDays(30))->avg('rating');

        // Réservés / servis par jour
        $history = Menu::whereDate('date', '<=', today())->whereDate('date', '>=', today()->subDays($days))->orderBy('date')->get()->map(function ($m) {
            $r = Reservation::where('menu_id', $m->id)->where('status', '!=', 'cancelled');

            return ['label' => $m->date->locale('fr')->translatedFormat('D d/m'), 'reserved' => (clone $r)->count(), 'served' => (clone $r)->where('status', 'served')->count(), 'missed' => (clone $r)->where('status', 'missed')->count()];
        });
        $slots = [];
        foreach (Settings::slots() as $s => $cap) {
            $slots[$s] = ['n' => $todayRes->where('slot', $s)->count(), 'cap' => $cap];
        }
        $tomorrow = Menu::whereDate('date', '>', today())->orderBy('date')->first();
        $tomorrowCount = $tomorrow ? Reservation::where('menu_id', $tomorrow->id)->where('status', '!=', 'cancelled')->count() : 0;

        $byClass = Reservation::whereNotNull('class_snapshot')->where('status', '!=', 'cancelled')
            ->whereHas('menu', fn ($q) => $q->whereDate('date', '>=', today()->subDays($days))->whereDate('date', '<=', today()))
            ->groupBy('class_snapshot')->selectRaw('class_snapshot, COUNT(*) as n')->orderByDesc('n')->limit(8)->pluck('n', 'class_snapshot');

        $forecast = $today ? $service->forecast($today) : null;
        $risk = $todayRes->filter(fn ($r) => $r->allergy_conflict && ! $r->allergy_checked_by)->count();
        $lowStock = Ingredient::whereColumn('stock', '<', 'threshold')->get();

        // Impact anti-gaspi : portions non cuisinées grâce aux réservations (prévu − réservé) sur les jours passés
        $saved = 0;
        foreach (Menu::with('items')->whereDate('date', '<', today())->whereDate('date', '>=', today()->subDays(30))->get() as $m) {
            $reserved = array_sum($m->reservedByDish());
            $saved += max(0, $m->items->sum('planned_portions') - $reserved);
        }
        $ratings = SuggestionService::ratings();
        uasort($ratings, fn ($a, $b) => $b[0] <=> $a[0]);
        $dishNames = \App\Models\Dish::withTrashed()->pluck('name', 'id');
        $rated = collect($ratings)->filter(fn ($r) => $r[1] >= 3);

        return view('admin.dashboard', [
            'days' => $days, 'today' => $today, 'todayRes' => $todayRes, 'pickupRate' => $pickupRate, 'avg' => $avg, 'history' => $history,
            'slots' => $slots, 'tomorrow' => $tomorrow, 'tomorrowCount' => $tomorrowCount, 'byClass' => $byClass, 'forecast' => $forecast,
            'risk' => $risk, 'lowStock' => $lowStock, 'saved' => $saved, 'best' => $rated->take(3), 'worst' => $rated->reverse()->take(3), 'dishNames' => $dishNames,
            'chefs' => $suggestions->chefOfTheWeek(3),
        ]);
    }
}
