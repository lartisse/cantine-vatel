<?php

namespace App\Http\Controllers\ParentArea;

use App\Http\Controllers\Controller;
use App\Models\LinkToken;
use App\Models\Menu;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Reservation;
use App\Models\User;
use App\Services\AccountService;
use App\Services\ReservationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Espace parent P1–P6. */
class ParentController extends Controller
{
    protected function ownChild(Request $request, User $enfant): User
    {
        abort_unless($request->user()->children()->where('users.id', $enfant->id)->exists(), 403);

        return $enfant;
    }

    public function home(Request $request)
    {
        $p = $request->user();
        $kids = $p->children()->active()->get();
        $today = Menu::whereDate('date', today())->first();
        $todayRes = $today ? Reservation::with('items.dish')->where('menu_id', $today->id)->whereIn('user_id', $kids->pluck('id'))->where('status', '!=', 'cancelled')->get()->keyBy('user_id') : collect();
        $month = now()->startOfMonth();

        return view('parent.home', [
            'p' => $p, 'kids' => $kids, 'todayRes' => $todayRes, 'unread' => $p->unreadCount(),
            'topped' => Payment::where('payer_id', $p->id)->where('created_at', '>=', $month)->sum('amount'),
            'meals' => Reservation::whereIn('user_id', $kids->pluck('id'))->where('status', 'served')->where('served_at', '>=', $month)->count(),
        ]);
    }

    public function children(Request $request)
    {
        $first = $request->user()->children()->first();

        return $first ? redirect()->route('parent.child', $first) : redirect()->route('parent.link');
    }

    public function linkForm(Request $request)
    {
        $found = session('found') ? User::find(session('found')) : null;

        return view('parent.link', ['found' => $found, 'token' => session('token')]);
    }

    protected function findToken(string $input): ?LinkToken
    {
        $input = trim($input);
        if (str_starts_with($input, 'CVLINK:')) {
            return LinkToken::with('child')->where('token', substr($input, 7))->first();
        }

        return LinkToken::with('child')->where('code', strtoupper($input))->latest()->first();
    }

    public function linkFind(Request $request)
    {
        $request->validate(['code' => ['required', 'string', 'max:80']], ['code.required' => 'Saisissez le code de secours.']);
        $t = $this->findToken($request->input('code'));
        if (! $t || ! $t->isValid() || ! $t->child || $t->child->status !== 'active' || ! $t->child->isStudent()) {
            return back()->withErrors(['code' => 'QR code non reconnu ou expiré. Demandez à votre enfant d’en générer un nouveau.']);
        }
        if ($request->user()->children()->where('users.id', $t->child_id)->exists()) {
            return back()->withErrors(['code' => 'Cet enfant est déjà lié à votre compte.']);
        }

        return redirect()->route('parent.link')->with(['found' => $t->child_id, 'token' => $request->input('code')]);
    }

    public function link(Request $request)
    {
        $t = $this->findToken((string) $request->input('code'));
        if (! $t || ! $t->isValid()) {
            return redirect()->route('parent.link')->withErrors(['code' => 'QR code expiré. Demandez-en un nouveau.']);
        }
        $p = $request->user();
        $p->children()->syncWithoutDetaching([$t->child_id => ['linked_at' => now()]]);
        $t->update(['used_at' => now()]);
        $t->child->inform('Compte parent lié', $p->name.' suit désormais vos réservations.', route('profile'), 'info', 'link');

        return redirect()->route('parent.home')->with('ok', __(':n a été ajouté(e)', ['n' => $t->child->first_name]));
    }

    public function child(Request $request, User $enfant, ReservationService $service)
    {
        $c = $this->ownChild($request, $enfant);
        $p = $request->user();
        $monday = today()->startOfWeek();
        $week = Menu::whereBetween('date', [$monday, $monday->copy()->addDays(4)])->orderBy('date')->get();
        $res = Reservation::with('items.dish', 'menu')->where('user_id', $c->id)->get();
        $weekRes = $week->mapWithKeys(fn ($m) => [$m->id => $res->first(fn ($r) => $r->menu_id === $m->id && $r->status !== 'cancelled')]);
        $upcoming = $res->filter(fn ($r) => $r->status === 'paid' && $r->menu->date->gte(today()))->sortBy(fn ($r) => $r->menu->date);
        $served = $res->where('status', 'served');
        $cats = ['plat' => 0, 'entree' => 0, 'dessert' => 0, 'boisson' => 0];
        $kcal = 0;
        foreach ($served as $r) {
            foreach ($r->items as $i) {
                $cats[$i->dish->category] = ($cats[$i->dish->category] ?? 0) + $i->quantity;
                $kcal += ($i->dish->kcal ?? 0) * $i->quantity;
            }
        }
        $open = Menu::with('items.dish')->whereDate('date', '>=', today())->where('is_published', true)->orderBy('date')->get()->filter(fn ($m) => $m->isOpen() && ! $res->contains(fn ($r) => $r->menu_id === $m->id && $r->status !== 'cancelled'));

        return view('parent.child', [
            'p' => $p, 'c' => $c, 'kids' => $p->children, 'week' => $week, 'weekRes' => $weekRes, 'upcoming' => $upcoming, 'served' => $served, 'cats' => $cats,
            'kcal' => $served->count() ? round($kcal / $served->count()) : 0, 'txs' => $c->transactions()->latest()->limit(5)->get(), 'open' => $open,
            'slots' => $open->first() ? $service->slotsLeft($open->first()) : [],
        ]);
    }

    public function updateChild(Request $request, User $enfant)
    {
        $c = $this->ownChild($request, $enfant);
        $data = $request->validate([
            'allergies' => ['array'], 'allergies.*' => [Rule::in(array_keys(config('cantine.allergens')))],
            'daily_cap' => ['nullable', 'integer', 'min:0', 'max:50000'], 'low_balance_threshold' => ['nullable', 'integer', 'min:0'],
            'auto_topup' => ['nullable', 'boolean'], 'auto_topup_amount' => ['nullable', 'integer', 'min:500'], 'field' => ['required', 'string'],
        ]);
        match ($data['field']) {
            'allergies' => $c->allergies = $data['allergies'] ?? [],
            'cap' => $c->daily_cap = $data['daily_cap'] ?: null,
            default => null,
        };
        $c->save();

        return back()->with('ok', __('Enregistré'));
    }

    public function book(Request $request, User $enfant, ReservationService $service)
    {
        $c = $this->ownChild($request, $enfant);
        $data = $request->validate(['menu_id' => ['required', 'exists:menus,id'], 'qty' => ['array'], 'slot' => ['required', 'string']]);
        $menu = Menu::findOrFail($data['menu_id']);
        $service->book($c, $menu, $data['qty'] ?? [], $data['slot'], false, $request->user());

        return back()->with('ok', __('Repas réservé pour :n', ['n' => $c->first_name]));
    }

    public function topupChoose(Request $request)
    {
        $first = $request->user()->children()->first();

        return $first ? redirect()->route('parent.topup', $first) : redirect()->route('parent.link');
    }

    public function topupForm(Request $request, User $enfant)
    {
        $c = $this->ownChild($request, $enfant);

        return view('parent.topup', ['p' => $request->user(), 'c' => $c, 'kids' => $request->user()->children, 'methods' => PaymentMethod::activeFor(config('cantine.payment_env'))->get()]);
    }

    public function topup(Request $request, User $enfant, AccountService $accounts)
    {
        $c = $this->ownChild($request, $enfant);
        $data = $request->validate(['amount' => ['required', 'integer', 'min:100', 'max:500000'], 'method' => ['required', 'integer'], 'phone' => ['nullable', 'string', 'max:30'],
            'auto_topup' => ['nullable'], 'auto_topup_amount' => ['nullable', 'integer', 'min:500']]);
        $method = PaymentMethod::activeFor(config('cantine.payment_env'))->findOrFail($data['method']);
        $payment = $accounts->topup($c, (int) $data['amount'], $method, $data['phone'] ?? '', $request->user());
        $c->auto_topup = $request->boolean('auto_topup');
        if (! empty($data['auto_topup_amount'])) {
            $c->auto_topup_amount = $data['auto_topup_amount'];
        }
        $c->save();

        return redirect()->route('parent.payments')->with('ok', __('Recharge réussie : :m pour :n (réf. :r). Nouveau solde : :b', ['m' => fcfa($payment->amount), 'n' => $c->first_name, 'r' => $payment->provider_reference, 'b' => fcfa($c->balance)]));
    }

    public function payments(Request $request)
    {
        $p = $request->user();
        $child = $request->query('enfant');
        $list = Payment::with('beneficiary', 'method')->where('payer_id', $p->id)->when($child, fn ($q) => $q->where('beneficiary_id', $child))->latest()->get();

        return view('parent.payments', ['list' => $list, 'kids' => $p->children, 'child' => $child]);
    }

    public function alerts(Request $request)
    {
        $p = $request->user();

        return view('parent.alerts', ['p' => $p, 'kids' => $p->children, 'items' => $p->appNotifications()->latest()->limit(50)->get()]);
    }

    public function saveAlerts(Request $request)
    {
        $p = $request->user();
        $data = $request->validate(['threshold' => ['array'], 'threshold.*' => ['integer', 'min:0', 'max:100000'], 'notify' => ['array']]);
        foreach ($data['threshold'] ?? [] as $id => $v) {
            $c = $p->children()->where('users.id', $id)->first();
            if ($c) {
                $c->low_balance_threshold = $v;
                $c->save();
            }
        }
        $p->notify = ['push' => isset($data['notify']['push']), 'sms' => isset($data['notify']['sms']), 'email' => isset($data['notify']['email'])];
        $p->save();

        return back()->with('ok', __('Préférences enregistrées'));
    }
}
