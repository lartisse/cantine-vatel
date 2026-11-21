<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\PaymentMethod;
use App\Models\Reservation;
use App\Models\Review;
use App\Services\ReservationService;
use App\Services\SuggestionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/** U4 Réserver, U5 Mes réservations + QR code. */
class ReservationController extends Controller
{
    public function __construct(protected ReservationService $service) {}

    public function index(Request $request)
    {
        $u = $request->user();
        $all = Reservation::with('menu', 'items.dish')->where('user_id', $u->id)->get();
        $upcoming = $all->filter(fn ($r) => $r->status === 'paid' && $r->menu->date->gte(today()))->sortBy(fn ($r) => $r->menu->date);
        $past = $all->reject(fn ($r) => $r->status === 'paid' && $r->menu->date->gte(today()))->sortByDesc(fn ($r) => $r->menu->date);
        $tab = $request->query('onglet', 'avenir');

        return view('user.reservations', compact('upcoming', 'past', 'tab'));
    }

    public function create(Request $request, Menu $menu, ?Reservation $existing = null)
    {
        $u = $request->user();
        $menu->load('items.dish');
        $already = $existing ? null : Reservation::where('user_id', $u->id)->where('menu_id', $menu->id)->where('status', '!=', 'cancelled')->first();
        $dishes = $menu->items->pluck('dish')->filter();
        SuggestionService::decorate($dishes);
        $selected = $existing ? $existing->items->pluck('quantity', 'dish_id')->all() : array_filter([(int) $request->query('plat') => 1]);

        return view('user.book', [
            'u' => $u, 'menu' => $menu, 'existing' => $existing, 'already' => $already, 'selected' => $selected,
            'slots' => $this->service->slotsLeft($menu, $existing?->id), 'left' => $this->service->portionsLeft($menu, $existing),
            'methods' => PaymentMethod::activeFor(config('cantine.payment_env'))->where('kind', 'Mobile Money')->get(),
        ]);
    }

    public function store(Request $request, Menu $menu)
    {
        $data = $this->validated($request);
        $reservation = $this->service->book($request->user(), $menu, $data['qty'] ?? [], $data['slot'] ?? '', $request->boolean('confirm_allergy'), $request->user(), null, $this->topup($request));

        return redirect()->route('reservations.show', $reservation)->with('ok', __('Repas réservé !'));
    }

    public function show(Request $request, Reservation $reservation)
    {
        $this->authorizeOwner($request, $reservation);
        $reservation->load('menu', 'items.dish');
        $reviewed = Review::where('user_id', $request->user()->id)->where('menu_id', $reservation->menu_id)->exists();

        return view('user.reservation', ['r' => $reservation, 'reviewed' => $reviewed]);
    }

    public function edit(Request $request, Reservation $reservation)
    {
        $this->authorizeOwner($request, $reservation);
        if (! $reservation->canBeChanged()) {
            return redirect()->route('reservations.show', $reservation)->with('error', __('L’heure limite est passée.'));
        }

        return $this->create($request, $reservation->menu, $reservation->load('items'));
    }

    public function update(Request $request, Reservation $reservation)
    {
        $this->authorizeOwner($request, $reservation);
        $data = $this->validated($request);
        $this->service->book($request->user(), $reservation->menu, $data['qty'] ?? [], $data['slot'] ?? '', $request->boolean('confirm_allergy'), $request->user(), $reservation->load('items'), $this->topup($request));

        return redirect()->route('reservations.show', $reservation)->with('ok', __('Réservation modifiée'));
    }

    public function destroy(Request $request, Reservation $reservation)
    {
        $this->authorizeOwner($request, $reservation);
        $this->service->cancel($reservation->load('menu', 'items.dish', 'user'));

        return redirect()->route('reservations.index')->with('ok', __('Réservation annulée — :m remboursés', ['m' => fcfa($reservation->total)]));
    }

    protected function validated(Request $request): array
    {
        return $request->validate(['qty' => ['array'], 'qty.*' => ['integer', 'min:0', 'max:'.config('cantine.max_quantity')], 'slot' => ['required', 'string'], 'method' => ['nullable', 'integer'], 'phone' => ['nullable', 'string', 'max:30']],
            ['slot.required' => __('Choisissez un créneau.')]);
    }

    protected function topup(Request $request): array
    {
        $method = $request->input('method') ? PaymentMethod::activeFor(config('cantine.payment_env'))->find($request->input('method')) : null;

        return ['method' => $method, 'phone' => $request->input('phone', $request->user()->phone)];
    }

    protected function authorizeOwner(Request $request, Reservation $reservation): void
    {
        Gate::authorize('update', $reservation);
    }
}
