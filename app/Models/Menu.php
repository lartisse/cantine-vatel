<?php

namespace App\Models;

use App\Support\Settings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Menu extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['date' => 'date', 'is_published' => 'boolean', 'is_closed' => 'boolean', 'published_at' => 'datetime'];
    }

    public function items()
    {
        return $this->hasMany(MenuItem::class);
    }

    public function dishes()
    {
        return $this->belongsToMany(Dish::class, 'menu_items')->withPivot('planned_portions', 'antigaspi_price', 'id')->withTrashed();
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /** Heure limite de réservation / modification pour ce menu. */
    public function cutoffAt(): Carbon
    {
        return $this->date->copy()->setTimeFromTimeString(Settings::get('cutoff', config('cantine.cutoff')));
    }

    public function isOpen(): bool
    {
        return $this->is_published && ! $this->is_closed && now()->lt($this->cutoffAt());
    }

    public function isPast(): bool
    {
        return $this->date->lt(Carbon::today());
    }

    public function label(string $format = 'l j F'): string
    {
        return ucfirst($this->date->locale(app()->getLocale())->translatedFormat($format));
    }

    /** Portions déjà réservées par plat (hors annulées). */
    public function reservedByDish(): array
    {
        return ReservationItem::query()
            ->join('reservations', 'reservations.id', '=', 'reservation_items.reservation_id')
            ->where('reservations.menu_id', $this->id)
            ->where('reservations.status', '!=', 'cancelled')
            ->groupBy('reservation_items.dish_id')
            ->selectRaw('reservation_items.dish_id, SUM(reservation_items.quantity) as qty')
            ->pluck('qty', 'dish_id')->map(fn ($v) => (int) $v)->all();
    }
}
