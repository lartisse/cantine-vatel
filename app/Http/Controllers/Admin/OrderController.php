<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Reservation;
use App\Services\ReservationService;
use App\Support\Settings;
use Illuminate\Http\Request;

/** A3 Commandes du jour et prévision de la semaine. */
class OrderController extends Controller
{
    public function __construct(protected ReservationService $service) {}

    protected function menu(Request $request): ?Menu
    {
        $date = $request->query('jour', today()->toDateString());

        return Menu::with('items.dish')->whereDate('date', $date)->first();
    }

    protected function filtered(Request $request, Menu $menu)
    {
        $q = Reservation::with('user', 'items.dish', 'checker')->where('menu_id', $menu->id)->where('status', '!=', 'cancelled');
        if ($s = $request->query('creneau')) {
            $q->where('slot', $s);
        }
        $st = $request->query('statut');
        if ($st === 'allergie') {
            $q->whereNotNull('allergy_conflict');
        } elseif ($st) {
            $q->where('status', $st);
        }
        if ($term = trim((string) $request->query('q'))) {
            $q->where(fn ($w) => $w->where('code', 'like', "%$term%")->orWhereHas('user', fn ($u) => $u->where('first_name', 'like', "%$term%")->orWhere('last_name', 'like', "%$term%")));
        }
        if ($id = $request->query('reservation')) {
            $q->where('id', $id);
        }

        return $q->orderBy('slot')->orderBy('id');
    }

    public function index(Request $request)
    {
        $menu = $this->menu($request);
        $tab = $request->query('onglet', 'jour');
        $slots = array_keys(Settings::slots());
        $data = ['menu' => $menu, 'tab' => $tab, 'slots' => $slots];
        if ($menu) {
            $all = Reservation::with('items')->where('menu_id', $menu->id)->where('status', '!=', 'cancelled')->get();
            $grid = [];
            foreach ($all as $r) {
                foreach ($r->items as $it) {
                    $grid[$it->dish_id][$r->slot] = ($grid[$it->dish_id][$r->slot] ?? 0) + $it->quantity;
                }
            }
            $data += [
                'list' => $this->filtered($request, $menu)->paginate(40)->withQueryString(),
                'grid' => $grid, 'counts' => ['reserved' => $all->count(), 'served' => $all->where('status', 'served')->count(), 'toServe' => $all->where('status', 'paid')->count(), 'allergy' => $all->whereNotNull('allergy_conflict')->count()],
                'forecast' => $this->service->forecast($menu),
            ];
        }
        // Semaine : réservés / prévus par plat × jour
        $monday = ($menu?->date ?? today())->copy()->startOfWeek();
        $week = Menu::with('items.dish')->whereBetween('date', [$monday, $monday->copy()->addDays(4)])->orderBy('date')->get();
        $weekGrid = [];
        foreach ($week as $m) {
            $res = $m->reservedByDish();
            foreach ($m->items as $it) {
                $weekGrid[$it->dish_id]['dish'] = $it->dish;
                $weekGrid[$it->dish_id]['days'][$m->id] = [$res[$it->dish_id] ?? 0, $it->planned_portions];
            }
        }
        $data += ['week' => $week, 'weekGrid' => $weekGrid, 'days' => Menu::whereDate('date', '>=', today()->subDays(7))->whereDate('date', '<=', today()->addDays(7))->where('is_published', true)->orderBy('date')->get()];

        return view('admin.orders', $data);
    }

    public function export(Request $request)
    {
        $menu = $this->menu($request);
        abort_unless($menu, 404);
        $rows = $this->filtered($request, $menu)->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Code', 'Convive', 'Classe / poste', 'Créneau', 'Plateau', 'Total', 'Statut', 'Allergie'], ';');
            foreach ($rows as $r) {
                fputcsv($out, [$r->code, $r->user?->name ?? 'Utilisateur supprimé', $r->class_snapshot ?? $r->user?->groupLabel(), $r->slotLabel(),
                    $r->items->map(fn ($i) => $i->quantity.'× '.$i->dish->name)->join(', '), $r->total, $r->statusLabel(), $r->allergy_conflict ? 'OUI' : ''], ';');
            }
            fclose($out);
        }, 'commandes-'.$menu->date->toDateString().'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function production(Request $request)
    {
        $menu = $this->menu($request);
        abort_unless($menu, 404);

        return view('admin.production', ['menu' => $menu, 'forecast' => $this->service->forecast($menu), 'slots' => array_keys(Settings::slots()),
            'allergy' => Reservation::with('user', 'items.dish')->where('menu_id', $menu->id)->where('status', '!=', 'cancelled')->whereNotNull('allergy_conflict')->get()]);
    }

    public function serve(Reservation $reservation)
    {
        $result = $this->service->scan($reservation->qr_token, true);

        return back()->with($result['state'] === 'ok' ? 'ok' : 'error', $result['title'].' — '.($reservation->user?->name ?? ''));
    }

    public function close(Request $request)
    {
        $menu = $this->menu($request);
        abort_unless($menu, 404);
        $n = $this->service->closeDay($menu);

        return back()->with('ok', "Journée clôturée : {$n} repas non retiré(s).");
    }

    /** Panier anti-gaspi : met en vente les portions restantes à -50 %. */
    public function antigaspi(Request $request)
    {
        $menu = $this->menu($request);
        abort_unless($menu, 404);
        $left = $this->service->portionsLeft($menu);
        $n = 0;
        foreach ($menu->items as $item) {
            if (($left[$item->dish_id] ?? 0) > 0 && $item->dish->category === 'plat') {
                $item->update(['antigaspi_price' => (int) round($item->dish->price / 2 / 50) * 50]);
                $n++;
            }
        }
        if ($n && ! $menu->isOpen()) {
            $menu->update(['is_closed' => false]);
        }

        return back()->with('ok', "{$n} plat(s) mis en vente à -50 % (panier anti-gaspi).");
    }
}
