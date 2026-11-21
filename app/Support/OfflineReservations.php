<?php

namespace App\Support;

use App\Models\Reservation;
use App\Models\User;

/**
 * Données des réservations à venir enregistrées sur l'appareil de l'élève (localStorage)
 * pour afficher le QR code et le code de secours hors ligne (page /offline.html).
 */
class OfflineReservations
{
    public static function payload(Reservation $r): array
    {
        $r->loadMissing('menu', 'items.dish');

        return [
            'id' => $r->id, 'iso' => $r->menu->date->toDateString(), 'date' => $r->menu->label(), 'slot' => $r->slotLabel(),
            'code' => $r->code, 'qr' => 'CV:'.$r->qr_token,
            'items' => $r->items->map(fn ($i) => ($i->quantity > 1 ? $i->quantity.'× ' : '').$i->dish?->name)->values(),
        ];
    }

    /** Réservations payées d'aujourd'hui et à venir. */
    public static function forUser(User $u): array
    {
        return Reservation::with('menu', 'items.dish')->where('user_id', $u->id)->where('status', 'paid')
            ->whereHas('menu', fn ($q) => $q->whereDate('date', '>=', today()))->get()
            ->sortBy(fn ($r) => $r->menu->date)->map(fn ($r) => self::payload($r))->values()->all();
    }
}
