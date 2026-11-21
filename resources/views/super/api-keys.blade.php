@extends('layouts.admin')
@section('title', 'Clés d’API')
@section('nav', 'api')
@section('content')
@php
    $base = url('/api/v1');
    $endpoints = [
        ['GET', '/ping', null, 'Vérifier une clé (nom, permissions, limite)'],
        ['GET', '/menus?du=AAAA-MM-JJ&au=AAAA-MM-JJ', 'menus:read', 'Menus publiés de la période (semaine en cours par défaut)'],
        ['GET', '/menus/{date}', 'menus:read', 'Menu publié d’un jour, créneaux et portions restantes'],
        ['GET', '/plats?categorie=plat', 'menus:read', 'Catalogue des plats (prix, allergènes, régimes, notes)'],
        ['GET', '/reservations?date=…&statut=paid', 'reservations:read', 'Réservations d’un jour, paginées (sans code de retrait)'],
        ['GET', '/commandes?date=…', 'orders:read', 'Quantités par plat et par créneau, prévision « à préparer »'],
        ['POST', '/comptoir/servir', 'orders:write', 'Valider un retrait : { "code": "204863" }'],
        ['GET', '/statistiques?jours=7', 'stats:read', 'Réservations, servis, non retirés, recettes, note moyenne'],
        ['GET', '/stocks', 'stocks:read', 'Ingrédients, seuils, besoins de la semaine'],
        ['GET', '/utilisateurs?type=eleve', 'users:read', 'Comptes actifs (données personnelles)'],
        ['POST', '/utilisateurs/{id}/recharges', 'wallet:write', 'Créditer un solde : { "amount": 5000, "reference": "TX-123" } (idempotent)'],
    ];
@endphp
<div class="page-head">
    <div><h1>Clés d’API</h1><p>Accès des applications et services partenaires aux données de la cantine.</p></div>
    <button class="btn" data-open="m-new-key">{!! ic('plus') !!} Nouvelle clé</button>
</div>
<div class="secure-note mb">{!! ic('lock') !!} <span><b>Chaque clé n’est affichée qu’une fois, à sa création.</b> Elle est ensuite stockée hachée (SHA-256) : personne ne peut la relire, ni en base ni ici. Donnez à chaque service uniquement les permissions dont il a besoin.</span></div>

<div class="stack">
@forelse ($keys as $k)
    @php [$color, $status] = $k->statusLabel(); @endphp
    <div class="card {{ $k->revoked_at ? 'flat' : '' }}" style="{{ $k->revoked_at ? 'opacity:.7' : '' }}">
        <div class="row between wrap">
            <div><b>{{ $k->name }}</b> <span class="badge {{ $color }}">{{ $status }}</span><div class="mt"><code>{{ $k->masked() }}</code></div></div>
            <div class="row wrap" style="gap:6px">
                @unless ($k->revoked_at)
                    <button class="btn sm ghost" data-open="m-edit-{{ $k->id }}">{!! ic('pencil') !!} Modifier</button>
                    <form method="POST" action="{{ route('super.api.toggle', $k) }}">@csrf<button class="btn sm ghost">{{ $k->is_active ? 'Suspendre' : 'Réactiver' }}</button></form>
                    <button class="btn sm light" data-open="m-rotate-{{ $k->id }}">{!! ic('refresh') !!} Renouveler</button>
                    <form method="POST" action="{{ route('super.api.revoke', $k) }}" data-confirm="Révoquer « {{ $k->name }} » ?" data-confirm-text="Le service qui l’utilise perdra immédiatement l’accès. Cette action est définitive." data-confirm-ok="Révoquer" data-danger>@csrf<button class="btn sm danger">Révoquer</button></form>
                @else
                    <form method="POST" action="{{ route('super.api.destroy', $k) }}" data-confirm="Supprimer « {{ $k->name }} » ?" data-confirm-text="La clé révoquée disparaîtra de la liste (l’historique reste dans le journal d’audit)." data-confirm-ok="Supprimer" data-danger>@csrf @method('DELETE')<button class="btn sm danger">{!! ic('trash') !!} Supprimer</button></form>
                @endunless
            </div>
        </div>
        <div class="row wrap mt" style="gap:4px">@foreach ($k->scopes as $s)<span class="badge {{ str_ends_with($s, ':write') ? 'orange' : '' }} xs" title="{{ $scopes[$s][1] ?? '' }}">{{ $s }}</span>@endforeach</div>
        <div class="grid-3 mt small muted" style="gap:4px 16px">
            <span>Limite : <b>{{ $k->rate_limit }}</b> requêtes/min</span>
            <span>Requêtes : <b>{{ num($k->requests_count) }}</b></span>
            <span>Dernier appel : <b>{{ $k->last_used_at ? $k->last_used_at->format('d/m/Y H:i').' · '.$k->last_used_ip : 'jamais' }}</b></span>
            <span>Expiration : <b>{{ $k->expires_at ? $k->expires_at->format('d/m/Y') : 'aucune' }}</b></span>
            <span>Créée le {{ $k->created_at->format('d/m/Y') }} par {{ $k->creator?->name ?? '—' }}</span>
            @if ($k->revoked_at)<span>Révoquée le {{ $k->revoked_at->format('d/m/Y H:i') }}</span>@endif
        </div>
        @if ($k->notes)<p class="xs muted mt">{{ $k->notes }}</p>@endif
    </div>
    @unless ($k->revoked_at)
    @push('modals')
    <div class="modal-bg hidden" id="m-edit-{{ $k->id }}"><div class="modal"><h2>Modifier « {{ $k->name }} »</h2>
        <form method="POST" action="{{ route('super.api.update', $k) }}">@csrf @method('PUT')
            @include('super.partials.api-key-fields', ['k' => $k])
            <div class="actions"><button type="button" class="btn ghost" data-close>Annuler</button><button class="btn">Enregistrer</button></div>
        </form></div></div>
    <div class="modal-bg hidden" id="m-rotate-{{ $k->id }}"><div class="modal"><h2>Renouveler « {{ $k->name }} »</h2>
        <p class="small">Une nouvelle clé est créée avec les mêmes permissions ; l’ancienne (<code>{{ $k->masked() }}</code>) est <b>révoquée immédiatement</b>. Mettez à jour le service juste après.</p>
        <form method="POST" action="{{ route('super.api.rotate', $k) }}" class="mt">@csrf
            <div class="field"><label>Votre mot de passe</label><input class="input" type="password" name="current_password" required autocomplete="current-password"></div>
            <div class="actions"><button type="button" class="btn ghost" data-close>Annuler</button><button class="btn">Renouveler la clé</button></div>
        </form></div></div>
    @endpush
    @endunless
@empty
    <div class="empty"><div class="ico">{!! ic('link') !!}</div><b>Aucune clé d’API</b><p class="small">Créez une clé pour chaque application ou service qui doit accéder aux données.</p></div>
@endforelse
</div>

<div class="card mt">
    <h3>{!! ic('file') !!} Documentation de l’API</h3>
    <p class="small mt">URL de base : <code>{{ $base }}</code> · réponses en JSON · authentification par en-tête :</p>
    <pre class="api-code">Authorization: Bearer cvk_…        (ou  X-Api-Key: cvk_…)</pre>
    <div class="table-wrap mt"><table class="tbl">
        <tr><th>Méthode</th><th>Chemin</th><th>Permission</th><th>Description</th></tr>
        @foreach ($endpoints as [$m, $path, $scope, $desc])
            <tr><td><span class="badge {{ $m === 'POST' ? 'orange' : '' }}">{{ $m }}</span></td><td><code>{{ $path }}</code></td><td>{{ $scope ? $scope : '—' }}</td><td class="small">{{ $desc }}</td></tr>
        @endforeach
    </table></div>
    <p class="small mt"><b>Exemple</b></p>
    <pre class="api-code">curl -H "Authorization: Bearer cvk_VOTRE_CLE" "{{ $base }}/menus"</pre>
    <p class="xs muted mt">Codes : 401 clé absente, invalide, révoquée ou expirée · 403 permission manquante · 422 données invalides · 429 limite de requêtes atteinte (en-tête Retry-After). Chaque réponse indique <code>X-RateLimit-Remaining</code>. Détail complet : <code>docs/API.md</code>.</p>
</div>

@push('modals')
<div class="modal-bg {{ $errors->hasAny(['name', 'scopes', 'rate_limit', 'expires_at', 'current_password']) && old('_form') === 'create' ? '' : 'hidden' }}" id="m-new-key"><div class="modal"><h2>Nouvelle clé d’API</h2>
    <form method="POST" action="{{ route('super.api.store') }}">@csrf<input type="hidden" name="_form" value="create">
        @include('super.partials.api-key-fields', ['k' => null])
        <div class="field mt"><label>Votre mot de passe</label><input class="input" type="password" name="current_password" required autocomplete="current-password">@error('current_password')<span class="error">{{ $message }}</span>@enderror</div>
        <div class="actions"><button type="button" class="btn ghost" data-close>Annuler</button><button class="btn">Créer la clé</button></div>
    </form></div></div>

@if ($newKey)
<div class="modal-bg" id="m-show-key" data-locked="1"><div class="modal">
    <h2>{!! ic('check-circle') !!} Clé créée : {{ $newKey['name'] }}</h2>
    <div class="alert orange">{!! ic('alert') !!} <span><b>Copiez-la maintenant.</b> Elle ne sera plus jamais affichée.</span></div>
    <div class="row mt"><input class="input grow" id="new-key-value" value="{{ $newKey['key'] }}" readonly style="font-family:monospace" onclick="this.select()"><button type="button" class="btn" onclick="navigator.clipboard.writeText(document.getElementById('new-key-value').value).then(()=>App.toast('Clé copiée','green'))">{!! ic('copy') !!} Copier</button></div>
    <div class="actions"><button type="button" class="btn ghost" data-close>J’ai copié la clé</button></div>
</div></div>
@endif
@endpush
@endsection
