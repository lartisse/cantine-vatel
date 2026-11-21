<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LinkToken extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'used_at' => 'datetime'];
    }

    public function child()
    {
        return $this->belongsTo(User::class, 'child_id');
    }

    public function isValid(): bool
    {
        return ! $this->used_at && $this->expires_at->isFuture();
    }
}
