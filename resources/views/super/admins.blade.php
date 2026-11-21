@extends('layouts.admin')
@section('title', 'Équipe')
@section('nav', 'admins')
@section('content')
@php
    $roles = ['counter' => ['Agent de comptoir', 'Écran Comptoir uniquement (scan des QR codes)'], 'admin' => ['Administrateur', 'Gestion de la cantine'], 'super_admin' => ['Super administrateur', 'Tous les droits, dont les comptes, paiements et API']];
    $roleSelect = fn ($current, $disabled = false) => '<select class="input" name="role" '.($disabled ? 'disabled' : '').'>'.collect($roles)->map(fn ($r, $k) => '<option value="'.$k.'" '.($current === $k ? 'selected' : '').'>'.e($r[0]).' — '.e($r[1]).'</option>')->join('').'</select>';
@endphp
<div class="page-head"><div><h1>Équipe</h1><p>Agents de comptoir, administrateurs et super administrateurs.</p></div><button class="btn" data-open="m-new">{!! ic('plus') !!} Créer un compte</button></div>
<div class="secure-note mb">{!! ic('lock') !!} <span>Chaque compte créé ou réinitialisé reçoit un <b>mot de passe provisoire affiché une seule fois</b>, à transmettre en main propre. Il devra le remplacer à sa première connexion.</span></div>
<div class="table-wrap card"><table class="tbl">
    <tr><th>Nom</th><th>E-mail</th><th>Fonction</th><th>Rôle</th><th>Statut</th><th></th></tr>
    @foreach ($admins as $a)
        <tr><td><div class="row"><x-avatar :user="$a" style="width:28px;height:28px;font-size:11px" /><b class="small">{{ $a->name }}</b>@if ($a->id === auth()->id())<span class="badge">Vous</span>@endif</div></td><td class="small">{{ $a->email }}</td><td class="small">{{ $a->position }}</td>
            <td><span class="badge {{ $a->isSuper() ? '' : ($a->role === 'counter' ? 'orange' : 'gray') }}">{{ $a->typeLabel() }}</span></td>
            <td><span class="badge {{ $a->status === 'active' ? 'green' : 'red' }}">{{ $a->status === 'active' ? 'Actif' : 'Désactivé' }}</span>@if ($a->must_change_password)<br><span class="xs muted">mot de passe provisoire</span>@endif</td>
            <td class="row" style="gap:6px"><button class="btn sm ghost" data-open="m-e{{ $a->id }}">Modifier</button>
                @if ($a->id !== auth()->id())
                    <form method="POST" action="{{ route('super.admins.password', $a) }}" data-confirm="Réinitialiser le mot de passe de {{ $a->name }} ?" data-confirm-text="Un mot de passe provisoire sera affiché une seule fois ; ses sessions ouvertes seront fermées." data-confirm-ok="Réinitialiser">@csrf<button class="btn sm ghost">{!! ic('key') !!} Mot de passe</button></form>
                    @if ($a->status === 'active')<button class="btn sm danger" data-open="m-d{{ $a->id }}">Désactiver</button>
                    @else<form method="POST" action="{{ route('super.admins.toggle', $a) }}">@csrf<button class="btn sm light">Réactiver</button></form>@endif
                @endif</td></tr>
        @push('modals')
        <div class="modal-bg hidden" id="m-e{{ $a->id }}"><div class="modal"><h2>Modifier {{ $a->name }}</h2>
            <form method="POST" action="{{ route('super.admins.update', $a) }}">@csrf @method('PUT')
                <div class="grid-2-eq"><div class="field"><label>Prénom</label><input class="input" name="first_name" value="{{ $a->first_name }}" required></div><div class="field"><label>Nom</label><input class="input" name="last_name" value="{{ $a->last_name }}" required></div></div>
                <div class="field"><label>Fonction</label><input class="input" name="position" value="{{ $a->position }}"></div>
                <div class="field"><label>Rôle</label>{!! $roleSelect($a->role, $a->id === auth()->id()) !!}@if ($a->id === auth()->id())<input type="hidden" name="role" value="{{ $a->role }}"><span class="xs muted">Impossible de changer son propre rôle.</span>@endif</div>
                <div class="actions"><button type="button" class="btn ghost" data-close>Annuler</button><button class="btn">Enregistrer</button></div></form></div></div>
        <div class="modal-bg hidden" id="m-d{{ $a->id }}"><div class="modal"><h2>Désactiver {{ $a->name }} ?</h2>
            <form method="POST" action="{{ route('super.admins.toggle', $a) }}">@csrf<div class="field"><label>Motif *</label><input class="input" name="reason" required></div>
                <div class="actions"><button type="button" class="btn ghost" data-close>Annuler</button><button class="btn danger">Désactiver</button></div></form></div></div>
        @endpush
    @endforeach
</table></div>
@push('modals')
<div class="modal-bg {{ $errors->hasAny(['first_name', 'last_name', 'email', 'role']) ? '' : 'hidden' }}" id="m-new"><div class="modal"><h2>Créer un compte d’équipe</h2>
    <form method="POST" action="{{ route('super.admins.store') }}">@csrf
        <div class="grid-2-eq"><div class="field"><label>Prénom *</label><input class="input" name="first_name" value="{{ old('first_name') }}" required></div><div class="field"><label>Nom *</label><input class="input" name="last_name" value="{{ old('last_name') }}" required></div></div>
        <div class="field"><label>E-mail *</label><input class="input" type="email" name="email" value="{{ old('email') }}" required>@error('email')<span class="error">{{ $message }}</span>@enderror</div>
        <div class="field"><label>Fonction</label><input class="input" name="position" value="{{ old('position') }}"></div>
        <div class="field"><label>Rôle</label>{!! $roleSelect(old('role', 'admin')) !!}</div>
        <div class="actions"><button type="button" class="btn ghost" data-close>Annuler</button><button class="btn">Créer</button></div></form></div></div>

@if ($newPassword)
<div class="modal-bg" id="m-temp-pass" data-locked="1"><div class="modal">
    <h2>{!! ic('key') !!} Mot de passe provisoire</h2>
    <p class="small">Compte : <b>{{ $newPassword['name'] }}</b> ({{ $newPassword['email'] }})</p>
    <div class="alert orange mt">{!! ic('alert') !!} <span><b>Notez-le maintenant</b> et transmettez-le en main propre : il ne sera plus jamais affiché. La personne devra le changer à sa première connexion.</span></div>
    <div class="row mt"><input class="input grow" id="temp-pass" value="{{ $newPassword['password'] }}" readonly style="font-family:monospace;font-size:18px" onclick="this.select()"><button type="button" class="btn" onclick="navigator.clipboard.writeText(document.getElementById('temp-pass').value).then(()=>App.toast('Copié','green'))">{!! ic('copy') !!} Copier</button></div>
    <div class="actions"><button type="button" class="btn ghost" data-close>C’est noté</button></div>
</div></div>
@endif
@endpush
@endsection
