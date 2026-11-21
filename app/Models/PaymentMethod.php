<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['fields' => 'array', 'is_active_test' => 'boolean', 'is_active_live' => 'boolean', 'test_ok_test' => 'boolean', 'test_ok_live' => 'boolean', 'tested_test_at' => 'datetime', 'tested_live_at' => 'datetime'];
    }

    public function credentials()
    {
        return $this->hasMany(PaymentCredential::class);
    }

    public function isActive(string $env): bool
    {
        return (bool) $this->{'is_active_'.$env};
    }

    public function credentialsFor(string $env)
    {
        return $this->credentials->where('environment', $env)->keyBy('key_name');
    }

    public function hasAllKeys(string $env): bool
    {
        return $this->credentialsFor($env)->count() >= count($this->fields);
    }

    public function scopeActiveFor($q, string $env)
    {
        return $q->where('is_active_'.$env, true);
    }
}
