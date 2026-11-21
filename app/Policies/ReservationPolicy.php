<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\User;

/** Une réservation n'est visible / modifiable que par son titulaire (ou un parent lié, ou l'administration). */
class ReservationPolicy
{
    public function view(User $user, Reservation $reservation): bool
    {
        return $reservation->user_id === $user->id
            || $user->isAdmin()
            || ($user->role === 'parent' && $user->children()->where('users.id', $reservation->user_id)->exists());
    }

    public function update(User $user, Reservation $reservation): bool
    {
        return $reservation->user_id === $user->id;
    }
}
