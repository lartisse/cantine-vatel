<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\StockController;
use App\Models\Dish;
use App\Models\Ingredient;
use App\Models\Menu;
use App\Models\Reservation;
use App\Models\Review;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\ReservationService;
use App\Services\SuggestionService;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * API REST v1 (JSON) pour les applications et services partenaires.
 * Authentification par clé d'API (middleware api.key), une portée par groupe de routes.
 * Documentation : docs/API.md et écran Super admin › Clés d'API.
 */
class V1Controller extends Controller
{
    public function __construct(protected ReservationService $service) {}

    // ---------- Formatage ----------

    protected function dish(Dish $d, ?array $ratings = null, ?int $left = null, ?int $price = null): array
    {
        $r = $ratings[$d->id] ?? null;

        return array_filter([
            'id' => $d->id, 'name' => $d->name, 'category' => $d->category, 'price' => $price ?? $d->price, 'regular_price' => $d->price,
            'kcal' => $d->kcal, 'description' => $d->description, 'allergens' => $d->allergens ?? [], 'diets' => $d->diets ?? [],
            'image_url' => $d->image_path ? url($d->imageUrl()) : null, 'rating' => $r[0] ?? null, 'ratings_count' => $r[1] ?? 0,
            'portions_left' => $left, 'archived' => $d->is_archived,
        ], fn ($v) => $v !== null);
    }

    protected function menu(Menu $m, array $ratings): array
    {
        $left = $this->service->portionsLeft($m);

        return [
            'date' => $m->date->toDateString(), 'published' => $m->is_published, 'open_for_booking' => $m->isOpen(),
            'booking_cutoff' => $m->cutoffAt()->toIso8601String(),
            'slots' => collect($this->service->slotsLeft($m))->map(fn ($s, $t) => ['time' => $t] + $s)->values(),
            'dishes' => $m->items->filter(fn ($i) => $i->dish)->map(fn ($i) => $this->dish($i->dish, $ratings, $left[$i->dish_id] ?? 0, $i->antigaspi_price ?? $i->dish->price) + ['planned_portions' => $i->planned_portions])->values(),
        ];
    }

    protected function reservation(Reservation $r): array
    {
        return [
            'id' => $r->id, 'date' => $r->menu->date->toDateString(), 'slot' => $r->slot, 'status' => $r->status,
            'total' => $r->total, 'class' => $r->class_snapshot,
            'user' => $r->user ? ['id' => $r->user->id, 'name' => $r->user->name, 'type' => $r->user->account_type] : null,
            'items' => $r->items->map(fn ($i) => ['dish_id' => $i->dish_id, 'name' => $i->dish?->name, 'quantity' => $i->quantity, 'unit_price' => $i->unit_price])->values(),
            'allergy_conflict' => (bool) $r->allergy_conflict, 'allergy_checked' => (bool) $r->allergy_checked_by,
            'served_at' => $r->served_at?->toIso8601String(), 'created_at' => $r->created_at?->toIso8601String(),
        ];
    }

    protected function day(Request $request, string $param = 'date'): Carbon
    {
        $request->validate([$param => ['nullable', 'date_format:Y-m-d']]);

        return $request->filled($param) ? Carbon::parse($request->query($param)) : today();
    }

    // ---------- Général ----------

    public function ping(Request $request)
    {
        $key = $request->attributes->get('api_key');

        return response()->json(['ok' => true, 'service' => 'Cantine de Vatel', 'version' => 'v1', 'time' => now()->toIso8601String(),
            'key' => ['name' => $key->name, 'scopes' => $key->scopes, 'rate_limit_per_minute' => $key->rate_limit, 'expires_at' => $key->expires_at?->toIso8601String()]]);
    }

    // ---------- menus:read ----------

    public function menus(Request $request)
    {
        $request->validate(['du' => ['nullable', 'date_format:Y-m-d'], 'au' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:du']]);
        $from = $request->filled('du') ? Carbon::parse($request->query('du')) : today()->startOfWeek();
        $to = $request->filled('au') ? Carbon::parse($request->query('au')) : $from->copy()->addDays(6);
        abort_if($from->diffInDays($to) > 62, 422, 'Période limitée à 62 jours.');
        $ratings = SuggestionService::ratings();
        $menus = Menu::with('items.dish')->where('is_published', true)->whereDate('date', '>=', $from)->whereDate('date', '<=', $to)->orderBy('date')->get();

        return response()->json(['from' => $from->toDateString(), 'to' => $to->toDateString(), 'data' => $menus->map(fn ($m) => $this->menu($m, $ratings))->values()]);
    }

    public function menuOfDay(string $date)
    {
        abort_unless(preg_match('/^\d{4}-\d{2}-\d{2}$/', $date), 422, 'Date attendue au format AAAA-MM-JJ.');
        $m = Menu::with('items.dish')->where('is_published', true)->whereDate('date', $date)->first();
        abort_unless($m, 404, 'Aucun menu publié à cette date.');

        return response()->json(['data' => $this->menu($m, SuggestionService::ratings())]);
    }

    public function dishes(Request $request)
    {
        $request->validate(['categorie' => ['nullable', 'in:'.implode(',', array_keys(config('cantine.categories')))]]);
        $ratings = SuggestionService::ratings();
        $dishes = Dish::query()->when($request->query('categorie'), fn ($q, $c) => $q->where('category', $c))
            ->when(! $request->boolean('archives'), fn ($q) => $q->where('is_archived', false))->orderBy('category')->orderBy('name')->get();

        return response()->json(['data' => $dishes->map(fn ($d) => $this->dish($d, $ratings))->values()]);
    }

    // ---------- reservations:read ----------

    public function reservations(Request $request)
    {
        $day = $this->day($request);
        $request->validate(['statut' => ['nullable', 'in:paid,served,missed,cancelled'], 'par_page' => ['nullable', 'integer', 'min:1', 'max:200']]);
        $page = Reservation::with('user', 'items.dish', 'menu')->whereHas('menu', fn ($q) => $q->whereDate('date', $day))
            ->when($request->query('statut'), fn ($q, $s) => $q->where('status', $s))->orderBy('slot')->orderBy('id')
            ->paginate((int) $request->query('par_page', 50))->withQueryString();

        return response()->json(['date' => $day->toDateString(), 'data' => collect($page->items())->map(fn ($r) => $this->reservation($r)),
            'meta' => ['page' => $page->currentPage(), 'per_page' => $page->perPage(), 'total' => $page->total(), 'last_page' => $page->lastPage()]]);
    }

    // ---------- orders:read / orders:write ----------

    public function orders(Request $request)
    {
        $day = $this->day($request);
        $m = Menu::with('items.dish')->whereDate('date', $day)->first();
        abort_unless($m, 404, 'Aucun menu à cette date.');
        $res = Reservation::with('items')->where('menu_id', $m->id)->where('status', '!=', 'cancelled')->get();
        $grid = [];
        foreach ($res as $r) {
            foreach ($r->items as $it) {
                $grid[$it->dish_id][$r->slot] = ($grid[$it->dish_id][$r->slot] ?? 0) + $it->quantity;
            }
        }
        $forecast = $this->service->forecast($m);

        return response()->json(['date' => $day->toDateString(), 'closed' => $m->is_closed,
            'totals' => ['reserved' => $res->count(), 'served' => $res->where('status', 'served')->count(), 'to_serve' => $res->where('status', 'paid')->count(),
                'missed' => $res->where('status', 'missed')->count(), 'allergy_risks' => $res->whereNotNull('allergy_conflict')->count(), 'revenue' => $res->sum('total')],
            'absence_rate' => $forecast['rate'],
            'dishes' => collect($forecast['rows'])->map(fn ($row) => ['dish_id' => $row['dish']->id, 'name' => $row['dish']->name, 'category' => $row['dish']->category,
                'planned' => $row['planned'], 'reserved' => $row['reserved'], 'to_prepare' => $row['prepare'], 'by_slot' => (object) ($grid[$row['dish']->id] ?? [])])->values()]);
    }

    public function serve(Request $request)
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:64'], 'forcer' => ['nullable', 'boolean']]);
        $result = $this->service->scan($data['code'], $request->boolean('forcer'));
        $r = $result['reservation'];
        $status = ['ok' => 200, 'warn' => 409, 'err' => $r ? 409 : 404][$result['state']];

        return response()->json(['state' => $result['state'], 'message' => $result['title'], 'can_force' => $result['can_force'] ?? false,
            'reservation' => $r ? $this->reservation($r->loadMissing('user', 'items.dish', 'menu')) : null,
            'allergy_warning' => $r && $r->allergy_conflict ? collect($r->allergy_conflict)->map(fn ($c) => $c['name'].' : '.implode(', ', $c['allergens']))->values() : []], $status);
    }

    // ---------- stats:read ----------

    public function stats(Request $request)
    {
        $request->validate(['jours' => ['nullable', 'integer', 'min:1', 'max:90']]);
        $days = (int) $request->query('jours', 7);
        $menus = Menu::whereDate('date', '<=', today())->whereDate('date', '>=', today()->subDays($days))->orderBy('date')->get();
        $daily = $menus->map(function ($m) {
            $r = Reservation::where('menu_id', $m->id)->where('status', '!=', 'cancelled')->get();

            return ['date' => $m->date->toDateString(), 'reserved' => $r->count(), 'served' => $r->where('status', 'served')->count(),
                'missed' => $r->where('status', 'missed')->count(), 'revenue' => $r->sum('total')];
        });
        $served = $daily->sum('served');
        $missed = $daily->sum('missed');

        return response()->json(['period_days' => $days, 'totals' => ['reserved' => $daily->sum('reserved'), 'served' => $served, 'missed' => $missed, 'revenue' => $daily->sum('revenue'),
            'pickup_rate' => $served + $missed ? round($served / ($served + $missed), 4) : null,
            'average_rating' => round((float) Review::where('status', 'visible')->where('created_at', '>=', now()->subDays($days))->avg('rating'), 2) ?: null],
            'daily' => $daily->values()]);
    }

    // ---------- stocks:read ----------

    public function stocks()
    {
        $needs = StockController::needs();

        return response()->json(['data' => Ingredient::orderBy('name')->get()->map(fn ($i) => ['id' => $i->id, 'name' => $i->name, 'unit' => $i->unit,
            'stock' => (float) $i->stock, 'threshold' => (float) $i->threshold, 'weekly_need' => round($needs[$i->id] ?? 0, 2),
            'status' => $i->stock <= 0 ? 'out' : ($i->stock < $i->threshold ? 'low' : 'ok')])->values()]);
    }

    // ---------- users:read ----------

    public function users(Request $request)
    {
        $request->validate(['type' => ['nullable', 'in:eleve,personnel,parent'], 'par_page' => ['nullable', 'integer', 'min:1', 'max:200']]);
        $type = $request->query('type');
        $page = User::active()->whereIn('role', ['user', 'parent'])
            ->when($type === 'parent', fn ($q) => $q->where('role', 'parent'))
            ->when(in_array($type, ['eleve', 'personnel'], true), fn ($q) => $q->where('account_type', $type))
            ->orderBy('last_name')->paginate((int) $request->query('par_page', 50))->withQueryString();

        return response()->json(['data' => collect($page->items())->map(fn ($u) => ['id' => $u->id, 'first_name' => $u->first_name, 'last_name' => $u->last_name,
            'role' => $u->role, 'type' => $u->account_type, 'class' => $u->class_name, 'matricule' => $u->matricule, 'position' => $u->position,
            'balance' => $u->role === 'parent' ? null : $u->balance, 'allergies' => $u->allergyList()]),
            'meta' => ['page' => $page->currentPage(), 'per_page' => $page->perPage(), 'total' => $page->total(), 'last_page' => $page->lastPage()]]);
    }

    // ---------- wallet:write ----------

    /** Crédite le solde d'un convive après un paiement encaissé par un service externe. Idempotent sur « reference ». */
    public function topup(Request $request, User $user)
    {
        abort_unless($user->role === 'user' && $user->status === 'active', 404, 'Convive introuvable.');
        $data = $request->validate(['amount' => ['required', 'integer', 'min:100', 'max:500000'], 'reference' => ['required', 'string', 'max:80'], 'label' => ['nullable', 'string', 'max:120']]);
        $key = $request->attributes->get('api_key');

        if ($existing = Transaction::where('user_id', $user->id)->where('type', 'topup')->where('reference', $data['reference'])->first()) {
            return response()->json(['ok' => true, 'duplicate' => true, 'transaction_id' => $existing->id, 'balance' => $user->balance], 200);
        }
        $tx = DB::transaction(function () use ($user, $data, $key) {
            $user->balance += $data['amount'];
            $user->save();

            return Transaction::create(['user_id' => $user->id, 'type' => 'topup', 'amount' => $data['amount'],
                'label' => ($data['label'] ?? 'Recharge').' (via '.$key->name.')', 'reference' => $data['reference']]);
        });
        $user->inform('Compte rechargé', fcfa($data['amount']).' ajoutés (réf. '.$data['reference'].').', route('wallet'), 'topup', 'coins');
        AuditLogger::log('api_topup', $user, '+'.$data['amount'].' FCFA · réf. '.$data['reference'].' · clé « '.$key->name.' »');

        return response()->json(['ok' => true, 'duplicate' => false, 'transaction_id' => $tx->id, 'balance' => $user->fresh()->balance], 201);
    }
}
