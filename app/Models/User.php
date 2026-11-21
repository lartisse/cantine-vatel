<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $guarded = ['id', 'role', 'balance', 'status'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'allergies' => 'array',
            'diets' => 'array',
            'notify' => 'array',
            'auto_topup' => 'boolean',
            'trashed_at' => 'datetime',
        ];
    }

    public const ROLE_LABELS = ['user' => 'Utilisateur', 'parent' => 'Parent', 'admin' => 'Administrateur', 'super_admin' => 'Super administrateur'];

    // ---------- Relations ----------
    public function children()
    {
        return $this->belongsToMany(User::class, 'parent_child', 'parent_id', 'child_id')->withPivot('linked_at');
    }

    public function parents()
    {
        return $this->belongsToMany(User::class, 'parent_child', 'child_id', 'parent_id')->withPivot('linked_at');
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function appNotifications()
    {
        return $this->hasMany(AppNotification::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function deletionRequests()
    {
        return $this->hasMany(DeletionRequest::class);
    }

    // ---------- Helpers ----------
    public function getNameAttribute(): string
    {
        return trim(($this->first_name ?? '').' '.($this->last_name ?? '')) ?: 'Utilisateur supprimé';
    }

    public function initials(): string
    {
        return mb_strtoupper(mb_substr($this->first_name ?? 'U', 0, 1).mb_substr($this->last_name ?? 'S', 0, 1));
    }

    public function avatarUrl(): ?string
    {
        return $this->avatar_path ? Storage::url($this->avatar_path) : null;
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'super_admin'], true);
    }

    public function isSuper(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isStudent(): bool
    {
        return $this->role === 'user' && $this->account_type === 'eleve';
    }

    public function isCounter(): bool
    {
        return $this->role === 'admin' && str_contains(mb_strtolower((string) $this->position), 'comptoir');
    }

    public function typeLabel(): string
    {
        if ($this->role === 'user') {
            return $this->account_type === 'personnel' ? 'Personnel' : 'Élève';
        }

        return self::ROLE_LABELS[$this->role] ?? $this->role;
    }

    /** Classe (élève) ou poste (personnel). */
    public function groupLabel(): string
    {
        if ($this->account_type === 'personnel') {
            return $this->position ?: 'Personnel';
        }

        return $this->class_name ?: 'Classe non renseignée';
    }

    public function missingFields(): array
    {
        if ($this->role !== 'user') {
            return [];
        }
        $missing = [];
        if ($this->account_type === 'eleve') {
            if (! $this->class_name) {
                $missing[] = 'classe';
            }
            if (! $this->matricule) {
                $missing[] = 'matricule';
            }
        } elseif (! $this->position) {
            $missing[] = 'poste';
        }
        if (! $this->avatar_path) {
            $missing[] = 'photo';
        }

        return $missing;
    }

    public function homeRoute(): string
    {
        return match ($this->role) {
            'parent' => route('parent.home'),
            'admin', 'super_admin' => $this->isCounter() ? route('admin.counter') : route('admin.dashboard'),
            default => route('user.home'),
        };
    }

    public function allergyList(): array
    {
        return $this->allergies ?? [];
    }

    public function conflictsWith(Dish $dish): array
    {
        return array_values(array_intersect($this->allergyList(), $dish->allergens ?? []));
    }

    public function unreadCount(): int
    {
        return $this->appNotifications()->whereNull('read_at')->count();
    }

    public function inform(string $title, ?string $body = null, ?string $url = null, string $kind = 'info', string $icon = 'bell', ?int $reservationId = null): AppNotification
    {
        return $this->appNotifications()->create(compact('title', 'body', 'url', 'kind', 'icon') + ['reservation_id' => $reservationId]);
    }

    public function scopeActive($q)
    {
        return $q->where('status', 'active');
    }
}
