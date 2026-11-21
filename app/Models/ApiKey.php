<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Clé d'API générée par le super administrateur.
 * Seul le hachage SHA-256 est stocké : la clé complète n'est montrée qu'une fois, à sa création.
 */
class ApiKey extends Model
{
    protected $guarded = ['id', 'key_hash'];

    protected $hidden = ['key_hash'];

    protected function casts(): array
    {
        return ['scopes' => 'array', 'is_active' => 'boolean', 'expires_at' => 'datetime', 'last_used_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    /** Portées disponibles : clé => [libellé, description]. */
    public const SCOPES = [
        'menus:read' => ['Menus et plats', 'Menus publiés, catalogue des plats, prix, allergènes, portions restantes'],
        'reservations:read' => ['Réservations', 'Réservations (sans code de retrait ni QR), par jour et statut'],
        'orders:read' => ['Commandes du jour', 'Quantités par plat et par créneau, prévision anti-gaspi'],
        'orders:write' => ['Service au comptoir', 'Valider le retrait d’un repas (borne ou caisse externe)'],
        'stats:read' => ['Statistiques', 'Fréquentation, recettes, taux de retrait, notes'],
        'stocks:read' => ['Stocks', 'Ingrédients, seuils et besoins de la semaine'],
        'users:read' => ['Comptes', 'Élèves, personnel et parents actifs (données personnelles)'],
        'wallet:write' => ['Recharges', 'Créditer le solde d’un convive (paiement encaissé par un service externe)'],
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Génère une nouvelle clé ; retourne [modèle, clé en clair à montrer une seule fois]. */
    public static function generate(array $attributes): array
    {
        $plain = 'cvk_'.Str::random(40);
        $key = new self($attributes);
        $key->forceFill([
            'prefix' => substr($plain, 0, 10),
            'key_hash' => hash('sha256', $plain),
            'last4' => substr($plain, -4),
        ])->save();

        return [$key, $plain];
    }

    public static function findByPlain(?string $plain): ?self
    {
        return $plain ? self::where('key_hash', hash('sha256', trim($plain)))->first() : null;
    }

    public function masked(): string
    {
        return $this->prefix.'••••••••'.$this->last4;
    }

    public function isUsable(): bool
    {
        return $this->is_active && ! $this->revoked_at && (! $this->expires_at || $this->expires_at->isFuture());
    }

    public function statusLabel(): array
    {
        return match (true) {
            (bool) $this->revoked_at => ['red', 'Révoquée'],
            $this->expires_at && $this->expires_at->isPast() => ['orange', 'Expirée'],
            ! $this->is_active => ['gray', 'Suspendue'],
            default => ['green', 'Active'],
        };
    }

    public function allows(string $scope): bool
    {
        return in_array($scope, $this->scopes ?? [], true);
    }
}
