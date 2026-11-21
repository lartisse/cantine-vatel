<?php

namespace App\Http\Controllers\Super;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Gestion des clés d'API de consommation (super administrateur uniquement). */
class ApiKeyController extends Controller
{
    public function index()
    {
        return view('super.api-keys', ['keys' => ApiKey::with('creator')->latest()->get(), 'scopes' => ApiKey::SCOPES, 'newKey' => session('new_api_key')]);
    }

    protected function rules(bool $withPassword = true): array
    {
        return array_filter([
            'name' => ['required', 'string', 'max:80'],
            'scopes' => ['required', 'array', 'min:1'], 'scopes.*' => [Rule::in(array_keys(ApiKey::SCOPES))],
            'rate_limit' => ['required', 'integer', 'min:1', 'max:1000'],
            'expires_at' => ['nullable', 'date', 'after:today'],
            'notes' => ['nullable', 'string', 'max:500'],
            'current_password' => $withPassword ? ['required', 'current_password'] : null,
        ]);
    }

    protected function messages(): array
    {
        return ['name.required' => 'Donnez un nom à la clé (application ou service).', 'scopes.required' => 'Choisissez au moins une permission.',
            'expires_at.after' => 'La date d’expiration doit être dans le futur.', 'current_password.required' => 'Ressaisissez votre mot de passe pour confirmer.',
            'current_password.current_password' => 'Mot de passe incorrect'];
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules(), $this->messages());
        [$key, $plain] = ApiKey::generate(collect($data)->except('current_password')->all() + ['created_by' => $request->user()->id, 'is_active' => true]);
        AuditLogger::log('api_key_create', 'ApiKey', 'Permissions : '.implode(', ', $key->scopes).' · '.$key->rate_limit.' req/min', $key->name);

        // La clé complète n'est affichée qu'une seule fois (session flash), jamais stockée en clair
        return redirect()->route('super.api')->with('new_api_key', ['name' => $key->name, 'key' => $plain]);
    }

    public function update(Request $request, ApiKey $apiKey)
    {
        abort_if($apiKey->revoked_at, 422, 'Clé révoquée.');
        $data = $request->validate($this->rules(false), $this->messages());
        $apiKey->update($data);
        AuditLogger::log('api_key_update', 'ApiKey', 'Permissions : '.implode(', ', $apiKey->scopes).' · '.$apiKey->rate_limit.' req/min', $apiKey->name);

        return back()->with('ok', 'Clé « '.$apiKey->name.' » mise à jour');
    }

    public function toggle(ApiKey $apiKey)
    {
        abort_if($apiKey->revoked_at, 422, 'Clé révoquée.');
        $apiKey->update(['is_active' => ! $apiKey->is_active]);
        AuditLogger::log($apiKey->is_active ? 'api_key_enable' : 'api_key_suspend', 'ApiKey', null, $apiKey->name);

        return back()->with('ok', 'Clé « '.$apiKey->name.' » '.($apiKey->is_active ? 'réactivée' : 'suspendue'));
    }

    /** Remplace la clé par une nouvelle (mêmes permissions) et révoque l'ancienne. */
    public function rotate(Request $request, ApiKey $apiKey)
    {
        $request->validate(['current_password' => ['required', 'current_password']], $this->messages());
        [$new, $plain] = ApiKey::generate($apiKey->only(['name', 'scopes', 'rate_limit', 'expires_at', 'notes']) + ['created_by' => $request->user()->id, 'is_active' => true]);
        $apiKey->update(['is_active' => false, 'revoked_at' => now()]);
        AuditLogger::log('api_key_rotate', 'ApiKey', 'Ancienne clé '.$apiKey->masked().' révoquée', $apiKey->name);

        return redirect()->route('super.api')->with('new_api_key', ['name' => $new->name, 'key' => $plain]);
    }

    public function revoke(ApiKey $apiKey)
    {
        $apiKey->update(['is_active' => false, 'revoked_at' => now()]);
        AuditLogger::log('api_key_revoke', 'ApiKey', $apiKey->masked(), $apiKey->name);

        return back()->with('ok', 'Clé « '.$apiKey->name.' » révoquée définitivement');
    }

    public function destroy(ApiKey $apiKey)
    {
        abort_unless($apiKey->revoked_at, 422, 'Révoquez la clé avant de la supprimer.');
        $name = $apiKey->name;
        $apiKey->delete();
        AuditLogger::log('api_key_delete', 'ApiKey', null, $name);

        return back()->with('ok', 'Clé « '.$name.' » supprimée');
    }
}
