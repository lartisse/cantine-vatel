<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['tags' => 'array', 'flag_words' => 'array', 'approved' => 'boolean'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }

    public function dish()
    {
        return $this->belongsTo(Dish::class)->withTrashed();
    }

    public function authorName(): string
    {
        if (! $this->user || $this->user->status !== 'active' && ! $this->user->first_name) {
            return 'Utilisateur supprimé';
        }

        return $this->user->first_name.' '.mb_substr((string) $this->user->last_name, 0, 1).'.';
    }
}
