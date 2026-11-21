<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

/** Trace qui a fait quoi, quand, et depuis quelle IP. Journal en ajout seul. */
class AuditLogger
{
    public const LABELS = [
        'trash' => 'Désactivation', 'restore' => 'Restauration', 'delete' => 'Suppression définitive', 'purge' => 'Purge automatique',
        'refuse_deletion' => 'Refus de suppression', 'admin_create' => 'Création d’administrateur', 'admin_update' => 'Modification d’administrateur',
        'role_change' => 'Changement de rôle', 'admin_disable' => 'Désactivation', 'admin_enable' => 'Réactivation',
        'api_keys' => 'Clés de paiement', 'payment_toggle' => 'Activation de paiement', 'payment_test' => 'Test de connexion',
        'purge_setting' => 'Paramètre de purge', 'new_period' => 'Nouvelle période', 'allergy_checked' => 'Allergie vérifiée',
        'dish_delete' => 'Suppression de plat', 'balance_adjust' => 'Ajustement de solde', 'settings' => 'Paramètres',
        'api_key_create' => 'Clé d’API créée', 'api_key_update' => 'Clé d’API modifiée', 'api_key_suspend' => 'Clé d’API suspendue',
        'api_key_enable' => 'Clé d’API réactivée', 'api_key_rotate' => 'Clé d’API renouvelée', 'api_key_revoke' => 'Clé d’API révoquée',
        'api_key_delete' => 'Clé d’API supprimée', 'api_topup' => 'Recharge via l’API',
    ];

    public static function log(string $action, Model|string|null $target = null, ?string $details = null, ?string $targetLabel = null): AuditLog
    {
        $actor = auth()->user();

        return AuditLog::create([
            'actor_id' => $actor?->id,
            'actor_name' => $actor?->name ?? 'Système',
            'actor_role' => $actor?->role ?? 'system',
            'action' => $action,
            'target_type' => $target instanceof Model ? class_basename($target) : $target,
            'target_id' => $target instanceof Model ? $target->getKey() : null,
            'target_label' => $targetLabel ?? ($target instanceof Model ? ($target->name ?? null) : null),
            'details' => $details,
            'ip' => request()?->ip(),
            'created_at' => now(),
        ]);
    }
}
