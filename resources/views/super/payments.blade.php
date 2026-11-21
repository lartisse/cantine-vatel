@extends('layouts.admin')
@section('title', 'Moyens de paiement')
@section('nav', 'payments')
@section('content')
<div class="page-head"><div><h1>Moyens de paiement</h1><p>Clés par moyen et par environnement.</p></div>
    <div class="env-tabs"><a class="{{ $env === 'test' ? 'active test' : '' }}" href="?env=test" style="padding:8px 14px;display:inline-block">Test</a><a class="{{ $env === 'live' ? 'active prod' : '' }}" href="?env=live" style="padding:8px 14px;display:inline-block">Production</a></div></div>
<div class="secure-note mb">{!! ic('lock') !!} <b>Écran réservé au super administrateur</b> — clés chiffrées (AES-256), jamais réaffichées ni envoyées au navigateur. Identité confirmée : encore {{ intdiv($remaining, 60) }} min.</div>
@if ($env === 'test')<div class="alert orange mb">Environnement de test : aucun débit réel (pilote simulé).</div>@endif
<div class="prov-grid">
@foreach ($methods as $m)
    @php $creds = $m->credentialsFor($env); $active = $m->isActive($env); $ok = $m->{'test_ok_'.$env}; $at = $m->{'tested_'.$env.'_at'}; @endphp
    <div class="card">
        <div class="prov-head row between">
            <div class="row"><span class="logo" style="background:{{ $m->color }};color:{{ $m->code === 'momo' ? '#1d1d1b' : '#fff' }}">{{ $m->logo }}</span><div><b>{{ $m->name }}</b><div class="xs muted">{{ $m->kind }}</div></div></div>
            <form method="POST" action="{{ route('super.payments.toggle', $m) }}">@csrf<input type="hidden" name="env" value="{{ $env }}"><button class="switch {{ $active ? 'on' : '' }}" title="{{ $active ? 'Désactiver' : 'Activer' }}" aria-label="Activer"></button></form>
        </div>
        <div class="row wrap mt" style="gap:4px">
            <span class="badge {{ $active ? 'green' : 'gray' }}">{{ $active ? 'Activé' : 'Désactivé' }}</span>
            <span class="badge {{ $creds->count() >= count($m->fields) ? '' : 'orange' }}">{{ $creds->count() >= count($m->fields) ? 'Clés configurées' : $creds->count().' / '.count($m->fields).' clés' }}</span>
            @if ($ok !== null)<span class="badge {{ $ok ? 'green' : 'red' }}">{{ $ok ? 'Test OK' : 'Test échoué' }}</span>@endif
        </div>
        <div class="mt">
            @foreach ($m->fields as [$key, $label])
                <div class="key-row"><span class="small">{{ $label }}</span>@if (isset($creds[$key]))<code>{{ $creds[$key]->masked() }}</code>@else<span class="xs muted">Non configurée</span>@endif</div>
            @endforeach
        </div>
        <p class="xs muted mt">{{ $at ? 'Dernier test : '.$at->format('d/m/Y H:i') : 'Jamais testé' }}</p>
        <div class="row wrap mt">
            <button class="btn sm ghost" data-open="m-k{{ $m->id }}">{!! ic('key') !!} {{ $creds->count() ? 'Remplacer les clés' : 'Ajouter les clés' }}</button>
            <form method="POST" action="{{ route('super.payments.test', $m) }}">@csrf<input type="hidden" name="env" value="{{ $env }}"><button class="btn sm light">Tester la connexion</button></form>
            @if ($creds->count())<form method="POST" action="{{ route('super.payments.keys.delete', $m) }}" data-confirm="Supprimer les clés {{ $m->name }} ?" data-confirm-text="Le moyen sera désactivé dans cet environnement." data-danger>@csrf @method('DELETE')<input type="hidden" name="env" value="{{ $env }}"><button class="btn sm danger">Supprimer</button></form>@endif
        </div>
    </div>
    @push('modals')
    <div class="modal-bg hidden" id="m-k{{ $m->id }}"><div class="modal"><h2>Clés {{ $m->name }} — {{ $env === 'live' ? 'Production' : 'Test' }}</h2>
        <form method="POST" action="{{ route('super.payments.keys', $m) }}" autocomplete="off">@csrf @method('PUT')<input type="hidden" name="env" value="{{ $env }}">
            @foreach ($m->fields as [$key, $label])
                <div class="field"><label>{{ $label }}</label><input class="input" type="password" name="keys[{{ $key }}]" minlength="8" placeholder="{{ isset($creds[$key]) ? 'Laisser vide pour conserver '.$creds[$key]->masked() : 'Non configurée' }}" autocomplete="new-password"></div>
            @endforeach
            <p class="xs muted">Les clés sont chiffrées à l’enregistrement. Seuls les 4 derniers caractères restent visibles.</p>
            <div class="actions"><button type="button" class="btn ghost" data-close>Annuler</button><button class="btn">Enregistrer</button></div>
        </form></div></div>
    @endpush
@endforeach
</div>
@endsection
