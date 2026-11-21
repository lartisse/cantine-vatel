<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

/** Journal d'audit : en ajout seul, jamais modifiable ni supprimable. */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Le journal d’audit ne peut pas être modifié.'));
        static::deleting(fn () => throw new LogicException('Le journal d’audit ne peut pas être supprimé.'));
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
