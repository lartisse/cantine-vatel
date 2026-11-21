<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservationItem extends Model
{
    protected $guarded = ['id'];

    public $timestamps = false;

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function dish()
    {
        return $this->belongsTo(Dish::class)->withTrashed();
    }
}
