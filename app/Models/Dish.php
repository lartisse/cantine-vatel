<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Dish extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['allergens' => 'array', 'diets' => 'array', 'is_archived' => 'boolean'];
    }

    public function ingredients()
    {
        return $this->belongsToMany(Ingredient::class)->withPivot('quantity');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function menuItems()
    {
        return $this->hasMany(MenuItem::class);
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::url($this->image_path) : null;
    }

    public function categoryLabel(): string
    {
        return config('cantine.categories')[$this->category] ?? $this->category;
    }

    public function icon(): string
    {
        return config('cantine.category_icons')[$this->category] ?? 'utensils';
    }

    public function visibleReviews()
    {
        return $this->reviews()->where('status', 'visible');
    }

    public function avgRating(): ?float
    {
        $avg = $this->visibleReviews()->avg('rating');

        return $avg ? round($avg, 1) : null;
    }

    public function scopeAvailable($q)
    {
        return $q->where('is_archived', false);
    }
}
