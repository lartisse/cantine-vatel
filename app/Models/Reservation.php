<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['allergy_conflict' => 'array', 'served_at' => 'datetime', 'allergy_checked_at' => 'datetime'];
    }

    public const STATUS = ['paid' => 'Payée', 'served' => 'Servie', 'missed' => 'Non retirée', 'cancelled' => 'Annulée'];

    public const STATUS_BADGE = ['paid' => '', 'served' => 'green', 'missed' => 'orange', 'cancelled' => 'gray'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }

    public function items()
    {
        return $this->hasMany(ReservationItem::class);
    }

    public function checker()
    {
        return $this->belongsTo(User::class, 'allergy_checked_by');
    }

    public function bookedBy()
    {
        return $this->belongsTo(User::class, 'booked_by');
    }

    public function statusLabel(): string
    {
        return __(self::STATUS[$this->status] ?? $this->status);
    }

    public function statusBadge(): string
    {
        return self::STATUS_BADGE[$this->status] ?? '';
    }

    public function slotLabel(): string
    {
        return str_replace(':', 'h', $this->slot);
    }

    public function mainDishName(): string
    {
        $items = $this->items->sortBy(fn ($i) => array_search($i->dish?->category, ['plat', 'entree', 'dessert', 'boisson']));

        return $items->first()?->dish?->name ?? '—';
    }

    public function itemsCount(): int
    {
        return (int) $this->items->sum('quantity');
    }

    public function canBeChanged(): bool
    {
        return $this->status === 'paid' && $this->menu->isOpen();
    }
}
