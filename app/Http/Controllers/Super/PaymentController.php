<?php

namespace App\Http\Controllers\Super;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Payments\SimulatedGateway;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rule;

/**
 * S5 Moyens de paiement. Clés chiffrées (Crypt::encryptString, AES-256 avec APP_KEY),
 * seuls les 4 derniers caractères gardés en clair, jamais renvoyées au navigateur.
 * Toutes les routes passent par le middleware password.confirm (ressaisie du mot de passe).
 */
class PaymentController extends Controller
{
    protected function env(Request $request): string
    {
        return $request->input('env', $request->query('env', 'test')) === 'live' ? 'live' : 'test';
    }

    public function index(Request $request)
    {
        $env = $this->env($request);
        $confirmedAt = (int) $request->session()->get('auth.password_confirmed_at', 0);

        return view('super.payments', ['methods' => PaymentMethod::with('credentials')->orderBy('id')->get(), 'env' => $env,
            'remaining' => max(0, config('auth.password_timeout') - (now()->unix() - $confirmedAt))]);
    }

    public function keys(Request $request, PaymentMethod $method)
    {
        $env = $this->env($request);
        $fields = collect($method->fields)->pluck(0)->all();
        $request->validate(['keys' => ['array'], 'keys.*' => ['nullable', 'string', 'min:8', 'max:255'], 'env' => ['required', Rule::in(['test', 'live'])]],
            ['keys.*.min' => 'Clé trop courte (8 caractères minimum).']);
        $changed = [];
        foreach ($request->input('keys', []) as $name => $value) {
            if (! in_array($name, $fields, true) || $value === null || $value === '') {
                continue; // vide = conserver la clé existante
            }
            if ($env === 'live' && preg_match('/(^|_)test(_|$)/i', $value)) {
                return back()->with('error', 'Clé de test saisie en Production.');
            }
            preg_match('/^([a-z]+_(?:[a-z]+_)*)/i', $value, $m);
            $method->credentials()->updateOrCreate(['environment' => $env, 'key_name' => $name], [
                'value' => Crypt::encryptString($value), 'prefix' => isset($m[1]) ? substr($m[1], 0, 20) : null, 'last4' => substr($value, -4),
            ]);
            $changed[] = $name;
        }
        if ($changed) {
            $method->update(['test_ok_'.$env => null, 'tested_'.$env.'_at' => null]);
            AuditLogger::log('api_keys', 'PaymentMethod', 'Clé(s) remplacée(s) : '.implode(', ', $changed), $method->name.' ('.($env === 'live' ? 'Production' : 'Test').')');
        }

        return back()->with('ok', $changed ? count($changed).' clé(s) enregistrée(s) et chiffrée(s). Testez la connexion avant la mise en service.' : 'Aucune clé modifiée');
    }

    public function deleteKeys(Request $request, PaymentMethod $method)
    {
        $env = $this->env($request);
        $method->credentials()->where('environment', $env)->delete();
        $method->update(['is_active_'.$env => false, 'test_ok_'.$env => null]);
        AuditLogger::log('api_keys', 'PaymentMethod', 'Clés supprimées', $method->name.' ('.$env.')');

        return back()->with('ok', 'Clés supprimées');
    }

    public function test(Request $request, PaymentMethod $method)
    {
        $env = $this->env($request);
        $method->load('credentials');
        $result = SimulatedGateway::make($method, $env)->testConnection();
        $method->update(['test_ok_'.$env => $result['ok'], 'tested_'.$env.'_at' => now()]);
        AuditLogger::log('payment_test', 'PaymentMethod', $result['message'], $method->name.' ('.$env.')');

        return back()->with($result['ok'] ? 'ok' : 'error', $method->name.' : '.$result['message']);
    }

    public function toggle(Request $request, PaymentMethod $method)
    {
        $env = $this->env($request);
        $method->load('credentials');
        $field = 'is_active_'.$env;
        if (! $method->$field && (! $method->hasAllKeys($env) || ! $method->{'test_ok_'.$env})) {
            return back()->with('error', 'Activation refusée : toutes les clés et un test de connexion réussi sont requis.');
        }
        $method->update([$field => ! $method->$field]);
        AuditLogger::log('payment_toggle', 'PaymentMethod', $method->$field ? 'Activé' : 'Désactivé (clés conservées)', $method->name.' ('.$env.')');

        return back()->with('ok', $method->name.' '.($method->$field ? 'activé' : 'désactivé (clés conservées)'));
    }
}
