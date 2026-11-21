<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['stock' => 'float', 'threshold' => 'float'];
    }

    public function dishes()
    {
        return $this->belongsToMany(Dish::class)->withPivot('quantity');
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class);
    }
}
