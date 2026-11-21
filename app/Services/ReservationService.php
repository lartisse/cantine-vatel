<?php

namespace App\Services;

use App\Models\Dish;
use App\Models\Menu;
use App\Models\Reservation;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Règles métier de la réservation : heure limite, capacité des créneaux, portions,
 * débit du solde, remboursement, détection des allergènes, service au comptoir.
 */
class ReservationService
{
    /** Places restantes par créneau pour un menu. */
    public function slotsLeft(Menu $menu, ?int $exceptReservationId = null): array
    {
        $taken = Reservation::where('menu_id', $menu->id)->where('status', '!=', 'cancelled')
            ->when($exceptReservationId, fn ($q) => $q->where('id', '!=', $exceptReservationId))
            ->groupBy('slot')->selectRaw('slot, COUNT(*) as n')->pluck('n', 'slot');
        $out = [];
        foreach (Settings::slots() as $slot => $cap) {
            $out[$slot] = ['capacity' => (int) $cap, 'left' => max(0, (int) $cap - (int) ($taken[$slot] ?? 0))];
        }

        return $out;
    }

    /** Portions restantes par plat. */
    public function portionsLeft(Menu $menu, ?Reservation $except = null): array
    {
        $reserved = $menu->reservedByDish();
        if ($except) {
            foreach ($except->items as $it) {
                $reserved[$it->dish_id] = ($reserved[$it->dish_id] ?? 0) - $it->quantity;
            }
        }
        $left = [];
        foreach ($menu->items as $item) {
            $left[$item->dish_id] = max(0, $item->planned_portions - ($reserved[$item->dish_id] ?? 0));
        }

        return $left;
    }

    /** Allergènes du profil présents dans les plats choisis : [[dish_id, name, allergens[]], …] */
    public function conflicts(User $user, array $dishIds): array
    {
        $out = [];
        foreach (Dish::withTrashed()->whereIn('id', $dishIds)->get() as $dish) {
            if ($c = $user->conflictsWith($dish)) {
                $out[] = ['dish_id' => $dish->id, 'name' => $dish->name, 'allergens' => $c];
            }
        }

        return $out;
    }

    /**
     * Réserve (ou modifie) le repas d'un jour.
     *
     * @param  array<int,int>  $quantities  dish_id => quantité
     * @param  array{method?: \App\Models\PaymentMethod|null, phone?: string|null}  $topup  complément Mobile Money si le solde manque
     */
    public function book(User $user, Menu $menu, array $quantities, string $slot, bool $confirmAllergy = false, ?User $bookedBy = null, ?Reservation $existing = null, array $topup = []): Reservation
    {
        $quantities = array_filter(array_map('intval', $quantities), fn ($q) => $q > 0);
        $max = config('cantine.max_quantity');

        $errors = [];
        if (! $menu->isOpen()) {
            $errors['menu'] = 'Les réservations sont closes pour ce jour (heure limite : '.$menu->cutoffAt()->format('H\hi').').';
        }
        if (! $quantities) {
            $errors['items'] = 'Choisissez au moins un plat.';
        }
        $slots = $this->slotsLeft($menu, $existing?->id);
        if (! isset($slots[$slot])) {
            $errors['slot'] = 'Choisissez un créneau.';
        } elseif ($slots[$slot]['left'] <= 0) {
            $errors['slot'] = 'Ce créneau est complet.';
        }
        $menu->loadMissing('items');
        $menuDishIds = $menu->items->pluck('dish_id')->all();
        $left = $this->portionsLeft($menu, $existing);
        foreach ($quantities as $dishId => $q) {
            if (! in_array((int) $dishId, $menuDishIds, true)) {
                $errors['items'] = 'Un plat choisi ne fait pas partie du menu.';
            } elseif ($q > $max) {
                $errors['items'] = "{$max} portions maximum par plat.";
            } elseif ($q > ($left[$dishId] ?? 0)) {
                $errors['items'] = 'Un plat choisi est épuisé.';
            }
        }
        $already = Reservation::where('user_id', $user->id)->where('menu_id', $menu->id)->where('status', '!=', 'cancelled')
            ->when($existing, fn ($q) => $q->where('id', '!=', $existing->id))->exists();
        if ($already) {
            $errors['menu'] = 'Vous avez déjà réservé ce jour.';
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        $dishes = Dish::withTrashed()->whereIn('id', array_keys($quantities))->get()->keyBy('id');
        $prices = $menu->items->keyBy('dish_id');
        $total = 0;
        foreach ($quantities as $dishId => $q) {
            $unit = $prices[$dishId]->antigaspi_price ?? $dishes[$dishId]->price;
            $total += $unit * $q;
        }

        if ($user->daily_cap && $total > $user->daily_cap) {
            throw ValidationException::withMessages(['items' => 'Plafond journalier dépassé ('.number_format($user->daily_cap, 0, ',', ' ').' FCFA).']);
        }

        $conflicts = $this->conflicts($user, array_keys($quantities));
        if ($conflicts && ! $confirmAllergy) {
            throw ValidationException::withMessages(['allergy' => 'Plat incompatible avec vos allergies.']);
        }

        return DB::transaction(function () use ($user, $menu, $quantities, $slot, $conflicts, $bookedBy, $existing, $total, $prices, $dishes, $topup) {
            $user = User::lockForUpdate()->find($user->id);
            $available = $user->balance + ($existing?->total ?? 0);

            if ($available < $total) {
                if (empty($topup['method'])) {
                    throw ValidationException::withMessages(['balance' => 'Solde insuffisant.']);
                }
                $diff = $total - $available;
                app(AccountService::class)->topup($user, $diff, $topup['method'], $topup['phone'] ?? '', $bookedBy ?? $user);
                $user->refresh();
            }

            if ($existing) {
                $user->balance += $existing->total;
                Transaction::create(['user_id' => $user->id, 'type' => 'refund', 'amount' => $existing->total, 'label' => 'Modification — '.$existing->mainDishName()]);
                $existing->items()->delete();
                $reservation = $existing;
                $reservation->fill(['slot' => $slot, 'total' => $total, 'allergy_conflict' => $conflicts ?: null, 'allergy_checked_by' => null, 'allergy_checked_at' => null]);
                $reservation->save();
            } else {
                $reservation = Reservation::create([
                    'user_id' => $user->id, 'menu_id' => $menu->id, 'slot' => $slot, 'status' => 'paid',
                    'code' => $this->uniqueCode(), 'qr_token' => Str::random(32), 'total' => $total,
                    'class_snapshot' => $user->account_type === 'eleve' ? $user->class_name : null,
                    'booked_by' => $bookedBy && $bookedBy->id !== $user->id ? $bookedBy->id : null,
                    'allergy_conflict' => $conflicts ?: null,
                ]);
            }
            foreach ($quantities as $dishId => $q) {
                $reservation->items()->create(['dish_id' => $dishId, 'quantity' => $q, 'unit_price' => $prices[$dishId]->antigaspi_price ?? $dishes[$dishId]->price]);
            }
            $user->balance -= $total;
            $user->save();
            $reservation->load('items.dish', 'menu');
            Transaction::create(['user_id' => $user->id, 'type' => 'meal', 'amount' => -$total, 'label' => 'Repas — '.$reservation->mainDishName()]);

            $day = $menu->label('D j M');
            $user->inform($existing ? 'Réservation modifiée' : 'Repas réservé', $reservation->mainDishName().', '.$day.', créneau '.$reservation->slotLabel().'.', route('reservations.index'), 'reservation', 'utensils', $reservation->id);
            foreach ($user->parents as $parent) {
                $parent->inform('Repas réservé — '.$user->first_name, $reservation->mainDishName().', créneau '.$reservation->slotLabel().'.', route('parent.child', $user), 'reservation', 'utensils', $reservation->id);
            }
            if ($conflicts) {
                $this->alertAdmins($user, $reservation, $conflicts);
            }
            $this->checkLowBalance($user);

            return $reservation;
        });
    }

    public function cancel(Reservation $reservation): void
    {
        if (! $reservation->canBeChanged()) {
            throw ValidationException::withMessages(['menu' => 'L’heure limite est passée : la réservation ne peut plus être annulée.']);
        }
        DB::transaction(function () use ($reservation) {
            $reservation->update(['status' => 'cancelled']);
            $user = $reservation->user;
            $user->balance += $reservation->total;
            $user->save();
            Transaction::create(['user_id' => $user->id, 'type' => 'refund', 'amount' => $reservation->total, 'label' => 'Annulation — '.$reservation->mainDishName()]);
            $user->inform('Réservation annulée', 'Remboursement de '.number_format($reservation->total, 0, ',', ' ').' FCFA sur votre solde.', route('wallet'), 'refund', 'undo');
        });
    }

    protected function alertAdmins(User $user, Reservation $reservation, array $conflicts): void
    {
        $allergenLabels = config('cantine.allergens');
        foreach ($conflicts as $c) {
            $names = implode(', ', array_map(fn ($a) => mb_strtolower($allergenLabels[$a] ?? $a), $c['allergens']));
            $body = "{$user->name} ({$user->groupLabel()}) a réservé « {$c['name']} », qui contient : {$names}, allergène déclaré dans son profil. "
                .$reservation->menu->label('l j M').', créneau '.$reservation->slotLabel().'.';
            foreach (User::whereIn('role', ['admin', 'super_admin'])->active()->get() as $admin) {
                $admin->inform('Allergie : réservation à risque', $body, route('admin.alerts'), 'allergy', 'alert', $reservation->id);
            }
            foreach ($user->parents as $parent) {
                $parent->inform('Alerte allergie — '.$user->first_name, "{$user->first_name} a réservé « {$c['name']} » ({$names}). La cantine a été prévenue.", route('parent.child', $user), 'allergy', 'alert', $reservation->id);
            }
        }
    }

    public function checkLowBalance(User $user): void
    {
        if ($user->balance >= $user->low_balance_threshold) {
            return;
        }
        $fmt = fn ($n) => number_format($n, 0, ',', ' ');
        $user->inform('Solde bas', "Votre solde est de {$fmt($user->balance)} FCFA (seuil : {$fmt($user->low_balance_threshold)} FCFA).", route('wallet.topup'), 'low_balance', 'alert');
        foreach ($user->parents as $parent) {
            $parent->inform('Solde bas — '.$user->first_name, "Le solde de {$user->first_name} est de {$fmt($user->balance)} FCFA (seuil : {$fmt($user->low_balance_threshold)} FCFA).", route('parent.topup', $user), 'low_balance', 'alert');
        }
        // Recharge automatique sous le seuil (configurée par le parent)
        if ($user->auto_topup && ($parent = $user->parents->first())) {
            $method = \App\Models\PaymentMethod::activeFor(config('cantine.payment_env'))->first();
            if ($method) {
                app(AccountService::class)->topup($user, $user->auto_topup_amount, $method, $parent->phone ?? '', $parent, 'Recharge automatique');
            }
        }
    }

    protected function uniqueCode(): string
    {
        do {
            $code = (string) random_int(100000, 999999);
        } while (Reservation::where('code', $code)->whereIn('status', ['paid'])->exists());

        return $code;
    }

    /** Créneau en cours au comptoir. */
    public function currentSlot(): ?string
    {
        $now = now()->format('H:i');
        $current = null;
        foreach (array_keys(Settings::slots()) as $slot) {
            if ($now >= $slot) {
                $current = $slot;
            }
        }

        return $current ?? array_key_first(Settings::slots());
    }

    /**
     * Service au comptoir (A11) : code à 6 chiffres ou jeton QR.
     *
     * @return array{state: string, title: string, reservation: ?Reservation}
     */
    public function scan(string $input, bool $force = false, ?User $agent = null): array
    {
        $input = trim($input);
        if (str_starts_with($input, 'CV:')) {
            $input = substr($input, 3);
        }
        $q = Reservation::with('user', 'items.dish', 'menu', 'checker');
        $reservation = preg_match('/^\d{6}$/', $input)
            ? (clone $q)->where('code', $input)->whereHas('menu', fn ($m) => $m->whereDate('date', today()))->first()
                ?? (clone $q)->where('code', $input)->latest()->first()
            : (clone $q)->where('qr_token', $input)->first();

        if (! $reservation) {
            return ['state' => 'err', 'title' => 'Code invalide', 'reservation' => null];
        }
        if (! $reservation->menu->date->isToday()) {
            return ['state' => 'err', 'title' => 'Pas pour aujourd’hui ('.$reservation->menu->label('D j M').')', 'reservation' => $reservation];
        }
        if ($reservation->status === 'cancelled') {
            return ['state' => 'err', 'title' => 'Réservation annulée', 'reservation' => $reservation];
        }
        if ($reservation->status === 'served') {
            return ['state' => 'warn', 'title' => 'Déjà servi à '.$reservation->served_at->format('H\hi'), 'reservation' => $reservation];
        }
        $current = $this->currentSlot();
        if (! $force && $current && $reservation->slot !== $current) {
            return ['state' => 'warn', 'title' => 'Mauvais créneau (réservé pour '.$reservation->slotLabel().')', 'reservation' => $reservation, 'can_force' => true];
        }

        $reservation->update(['status' => 'served', 'served_at' => now()]);
        $user = $reservation->user;
        if ($user) {
            $user->inform('Comment était votre repas ?', 'Notez '.$reservation->mainDishName().' et le menu du jour.', route('reviews.create', $reservation->menu), 'review', 'star', $reservation->id);
            foreach ($user->parents as $parent) {
                $parent->inform('Repas servi — '.$user->first_name, $reservation->mainDishName().' servi à '.now()->format('H\hi').'.', route('parent.child', $user), 'served', 'check-circle', $reservation->id);
            }
        }

        return ['state' => 'ok', 'title' => 'Validé — servir', 'reservation' => $reservation->fresh(['user', 'items.dish', 'menu', 'checker'])];
    }

    /** Clôture de la journée : les repas non retirés passent en « missed ». */
    public function closeDay(Menu $menu): int
    {
        $n = Reservation::where('menu_id', $menu->id)->where('status', 'paid')->update(['status' => 'missed']);
        $menu->update(['is_closed' => true]);

        return $n;
    }

    /** Taux d'absence observé sur les 4 dernières semaines (non retirés / (servis + non retirés)). */
    public function absenceRate(): float
    {
        $rows = Reservation::whereIn('status', ['served', 'missed'])
            ->whereHas('menu', fn ($q) => $q->whereDate('date', '<', today())->whereDate('date', '>=', today()->subWeeks(4)))
            ->selectRaw("SUM(CASE WHEN status = 'missed' THEN 1 ELSE 0 END) as missed, COUNT(*) as n")->first();

        return $rows && $rows->n ? round($rows->missed / $rows->n, 3) : 0.08;
    }

    /** Prévision anti-gaspi : « X réservés → préparez Y » par plat. */
    public function forecast(Menu $menu): array
    {
        $rate = $this->absenceRate();
        $reserved = $menu->reservedByDish();
        $out = [];
        foreach ($menu->items()->with('dish')->get() as $item) {
            $x = $reserved[$item->dish_id] ?? 0;
            $out[] = ['dish' => $item->dish, 'planned' => $item->planned_portions, 'reserved' => $x, 'prepare' => (int) ceil($x * (1 - $rate))];
        }

        return ['rate' => $rate, 'rows' => $out];
    }
}
