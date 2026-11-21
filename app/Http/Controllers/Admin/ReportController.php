<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\Menu;
use App\Models\Reservation;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;

/** A6 Rapports (fréquentation, recettes, gaspillage, satisfaction, classes, stocks) et A9 statistiques par classe. */
class ReportController extends Controller
{
    public const TABS = ['frequentation' => 'Fréquentation', 'recettes' => 'Recettes', 'gaspillage' => 'Gaspillage', 'satisfaction' => 'Satisfaction', 'classes' => 'Par classe', 'stocks' => 'Stocks'];

    protected function daily(): \Illuminate\Support\Collection
    {
        return Menu::whereDate('date', '<=', today())->whereDate('date', '>=', today()->subWeeks(4))->orderBy('date')->get()->map(function ($m) {
            $r = Reservation::where('menu_id', $m->id)->where('status', '!=', 'cancelled')->get();
            $rev = Review::where('menu_id', $m->id)->where('status', 'visible');

            return ['date' => $m->date->format('d/m'), 'day' => $m->date->locale('fr')->translatedFormat('D'), 'reserved' => $r->count(), 'served' => $r->where('status', 'served')->count(),
                'missed' => $r->where('status', 'missed')->count(), 'revenue' => $r->sum('total'), 'rating' => round((float) $rev->avg('rating'), 2), 'reviews' => $rev->count(),
                'ate_part' => $r->whereIn('ate_all', ['moitie', 'peu'])->count(), 'ate_known' => $r->whereNotNull('ate_all')->count()];
        });
    }

    public static function classStats(): \Illuminate\Support\Collection
    {
        $students = User::where('account_type', 'eleve')->active()->get();
        $rows = Reservation::whereNotNull('class_snapshot')->where('status', '!=', 'cancelled')
            ->selectRaw("class_snapshot, COUNT(*) as n, SUM(CASE WHEN status='served' THEN 1 ELSE 0 END) as served, SUM(CASE WHEN status='missed' THEN 1 ELSE 0 END) as missed")
            ->groupBy('class_snapshot')->get()->keyBy('class_snapshot');

        return $rows->map(fn ($r, $c) => ['class' => $c, 'students' => $students->where('class_name', $c)->count(), 'n' => (int) $r->n, 'served' => (int) $r->served, 'missed' => (int) $r->missed,
            'per' => $students->where('class_name', $c)->count() ? round($r->n / $students->where('class_name', $c)->count(), 1) : 0])->sortBy('class')->values();
    }

    public function index(Request $request)
    {
        $tab = array_key_exists($request->query('onglet'), self::TABS) ? $request->query('onglet') : 'frequentation';
        $daily = $this->daily();
        $data = ['tab' => $tab, 'daily' => $daily, 'tabs' => self::TABS];
        if ($tab === 'classes') {
            $data['classes'] = self::classStats();
        }
        if ($tab === 'stocks') {
            $data['ingredients'] = Ingredient::orderBy('name')->get();
            $data['needs'] = StockController::needs();
        }
        if ($tab === 'satisfaction') {
            $data['dishRatings'] = Review::where('reviews.status', 'visible')->whereNotNull('dish_id')->join('dishes', 'dishes.id', '=', 'reviews.dish_id')
                ->groupBy('dishes.name')->selectRaw('dishes.name, AVG(rating) as avg, COUNT(*) as n')->orderByDesc('avg')->get();
        }

        return view('admin.reports', $data);
    }

    public function export(Request $request)
    {
        $tab = $request->query('onglet', 'frequentation');
        $rows = match ($tab) {
            'classes' => self::classStats()->map(fn ($r) => [$r['class'], $r['students'], $r['n'], $r['served'], $r['missed'], $r['per']])->prepend(['Classe', 'Élèves', 'Réservations', 'Servis', 'Non retirés', 'Repas par élève']),
            'stocks' => Ingredient::orderBy('name')->get()->map(fn ($i) => [$i->name, $i->stock, $i->unit, $i->threshold])->prepend(['Ingrédient', 'Stock', 'Unité', 'Seuil']),
            default => $this->daily()->map(fn ($d) => [$d['date'], $d['reserved'], $d['served'], $d['missed'], $d['revenue'], $d['rating']])->prepend(['Date', 'Réservés', 'Servis', 'Non retirés', 'Recettes (FCFA)', 'Note moyenne']),
        };

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            foreach ($rows as $r) {
                fputcsv($out, $r, ';');
            }
            fclose($out);
        }, "rapport-{$tab}-".today()->toDateString().'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function classes()
    {
        $stats = self::classStats();
        $students = User::where('account_type', 'eleve')->active()->count();
        $withClass = User::where('account_type', 'eleve')->active()->whereNotNull('class_name')->count();

        return view('admin.classes', compact('stats', 'students', 'withClass'));
    }
}
