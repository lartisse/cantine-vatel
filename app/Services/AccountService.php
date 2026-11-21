<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\DeletionRequest;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\User;
use App\Payments\SimulatedGateway;
use App\Support\Settings;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Portefeuille, corbeille, restauration, suppression définitive et anonymisation. */
class AccountService
{
    public function __construct(protected ImageService $images) {}

    public function topup(User $beneficiary, int $amount, PaymentMethod $method, string $phone, ?User $payer = null, ?string $label = null): Payment
    {
        $env = config('cantine.payment_env');
        $result = SimulatedGateway::make($method, $env)->charge($amount, $phone, 'Recharge Cantine de Vatel');
        if (! $result['ok']) {
            throw ValidationException::withMessages(['amount' => 'Paiement refusé : '.$result['message']]);
        }
        $payer ??= $beneficiary;

        return DB::transaction(function () use ($beneficiary, $amount, $method, $payer, $result, $env, $label) {
            $payment = Payment::create([
                'payer_id' => $payer->id, 'beneficiary_id' => $beneficiary->id, 'payment_method_id' => $method->id,
                'amount' => $amount, 'status' => 'success', 'environment' => $env, 'provider_reference' => $result['reference'],
            ]);
            $beneficiary->balance += $amount;
            $beneficiary->save();
            $by = $payer->id !== $beneficiary->id ? ' (par '.$payer->name.')' : '';
            Transaction::create(['user_id' => $beneficiary->id, 'type' => 'topup', 'amount' => $amount, 'label' => ($label ?? 'Recharge '.$method->name).$by, 'reference' => $result['reference']]);
            if ($payer->id !== $beneficiary->id) {
                $beneficiary->inform('Compte rechargé', number_format($amount, 0, ',', ' ').' FCFA ajoutés par '.$payer->first_name.'.', route('wallet'), 'topup', 'coins');
            }

            return $payment;
        });
    }

    public function adjust(User $user, int $amount, string $reason): void
    {
        DB::transaction(function () use ($user, $amount, $reason) {
            $user->balance += $amount;
            $user->save();
            Transaction::create(['user_id' => $user->id, 'type' => 'adjustment', 'amount' => $amount, 'label' => 'Ajustement au guichet — '.$reason]);
            AuditLogger::log('balance_adjust', $user, ($amount > 0 ? '+' : '').$amount.' FCFA · '.$reason);
        });
    }

    /** Phase 1 : corbeille. Données conservées, connexion bloquée, restauration possible. */
    public function trash(User $user, string $reason): void
    {
        $user->forceFill(['status' => 'trashed', 'trashed_at' => now(), 'trash_reason' => $reason, 'trashed_by' => auth()->user()?->name])->save();
        DB::table('sessions')->where('user_id', $user->id)->delete();
        $user->inform('Compte désactivé', 'Votre compte a été placé en corbeille. Motif : '.$reason, null, 'account', 'lock');
        foreach ($user->parents as $parent) {
            $parent->inform('Compte désactivé — '.$user->first_name, 'Le compte de '.$user->first_name.' a été désactivé. Motif : '.$reason, null, 'account', 'lock');
        }
        AuditLogger::log('trash', $user, 'Motif : '.$reason);
    }

    public function restore(User $user): void
    {
        $user->forceFill(['status' => 'active', 'trashed_at' => null, 'trash_reason' => null, 'trashed_by' => null])->save();
        $user->inform('Compte restauré', 'Votre compte est de nouveau actif.', null, 'account', 'check-circle');
        foreach ($user->parents as $parent) {
            $parent->inform('Compte restauré — '.$user->first_name, 'Le compte de '.$user->first_name.' est de nouveau actif.', null, 'account', 'check-circle');
        }
        AuditLogger::log('restore', $user);
    }

    /** Phase 2 : suppression définitive (uniquement depuis la corbeille). Anonymise réservations, avis, transactions. */
    public function forceDelete(User $user, string $action = 'delete'): void
    {
        if ($user->status !== 'trashed') {
            throw ValidationException::withMessages(['user' => 'Seul un compte en corbeille peut être supprimé définitivement.']);
        }
        $label = $user->name;
        DB::transaction(function () use ($user) {
            $this->images->delete($user->avatar_path);
            DB::table('reservations')->where('user_id', $user->id)->update(['user_id' => null]);
            DB::table('reviews')->where('user_id', $user->id)->update(['user_id' => null]);
            DB::table('transactions')->where('user_id', $user->id)->update(['user_id' => null]);
            DB::table('payments')->where('beneficiary_id', $user->id)->update(['beneficiary_id' => null]);
            DB::table('payments')->where('payer_id', $user->id)->update(['payer_id' => null]);
            DB::table('parent_child')->where('parent_id', $user->id)->orWhere('child_id', $user->id)->delete();
            DB::table('link_tokens')->where('child_id', $user->id)->delete();
            DeletionRequest::where('user_id', $user->id)->update(['user_id' => null]);
            AppNotification::where('user_id', $user->id)->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            $user->delete();
        });
        AuditLogger::log($action, 'User', $action === 'purge' ? 'Purge automatique après '.Settings::trashDays().' jours' : 'Données personnelles effacées, statistiques anonymisées', $label);
    }

    /** Purge des comptes en corbeille depuis plus de N jours (planificateur quotidien). */
    public function purge(): int
    {
        $users = User::where('status', 'trashed')->where('trashed_at', '<=', now()->subDays(Settings::trashDays()))->get();
        foreach ($users as $u) {
            $this->forceDelete($u, 'purge');
        }

        return $users->count();
    }
}
