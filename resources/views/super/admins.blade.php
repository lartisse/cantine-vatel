@extends('layouts.admin')
@section('title', 'Administrateurs')
@section('nav', 'admins')
@section('content')
<div class="page-head"><div><h1>Administrateurs</h1><p>Comptes administrateur et super administrateur.</p></div><button class="btn" data-open="m-new">{!! ic('plus') !!} Créer un administrateur</button></div>
<div class="table-wrap card"><table class="tbl">
    <tr><th>Nom</th><th>E-mail</th><th>Fonction</th><th>Rôle</th><th>Statut</th><th></th></tr>
    @foreach ($admins as $a)
        <tr><td><div class="row"><x-avatar :user="$a" style="width:28px;height:28px;font-size:11px" /><b class="small">{{ $a->name }}</b>@if ($a->id === auth()->id())<span class="badge">Vous</span>@endif</div></td><td class="small">{{ $a->email }}</td><td class="small">{{ $a->position }}</td>
            <td><span class="badge {{ $a->isSuper() ? '' : 'gray' }}">{{ $a->typeLabel() }}</span></td>
            <td><span class="badge {{ $a->status === 'active' ? 'green' : 'red' }}">{{ $a->status === 'active' ? 'Actif' : 'Désactivé' }}</span></td>
            <td class="row" style="gap:6px"><button class="btn sm ghost" data-open="m-e{{ $a->id }}">Modifier</button>
                @if ($a->id !== auth()->id())
                    @if ($a->status === 'active')<button class="btn sm danger" data-open="m-d{{ $a->id }}">Désactiver</button>
                    @else<form method="POST" action="{{ route('super.admins.toggle', $a) }}">@csrf<button class="btn sm light">Réactiver</button></form>@endif
                @endif</td></tr>
        @push('modals')
        <div class="modal-bg hidden" id="m-e{{ $a->id }}"><div class="modal"><h2>Modifier {{ $a->name }}</h2>
            <form method="POST" action="{{ route('super.admins.update', $a) }}">@csrf @method('PUT')
                <div class="grid-2-eq"><div class="field"><label>Prénom</label><input class="input" name="first_name" value="{{ $a->first_name }}" required></div><div class="field"><label>Nom</label><input class="input" name="last_name" value="{{ $a->last_name }}" required></div></div>
                <div class="field"><label>Fonction</label><input class="input" name="position" value="{{ $a->position }}"></div>
                <div class="field"><label>Rôle</label><select class="input" name="role" {{ $a->id === auth()->id() ? 'disabled' : '' }}><option value="admin" {{ $a->role === 'admin' ? 'selected' : '' }}>Administrateur</option><option value="super_admin" {{ $a->role === 'super_admin' ? 'selected' : '' }}>Super administrateur</option></select>@if ($a->id === auth()->id())<input type="hidden" name="role" value="{{ $a->role }}"><span class="xs muted">Impossible de changer son propre rôle.</span>@endif</div>
                <div class="actions"><button type="button" class="btn ghost" data-close>Annuler</button><button class="btn">Enregistrer</button></div></form></div></div>
        <div class="modal-bg hidden" id="m-d{{ $a->id }}"><div class="modal"><h2>Désactiver {{ $a->name }} ?</h2>
            <form method="POST" action="{{ route('super.admins.toggle', $a) }}">@csrf<div class="field"><label>Motif *</label><input class="input" name="reason" required></div>
                <div class="actions"><button type="button" class="btn ghost" data-close>Annuler</button><button class="btn danger">Désactiver</button></div></form></div></div>
        @endpush
    @endforeach
</table></div>
@push('modals')
<div class="modal-bg {{ $errors->any() ? '' : 'hidden' }}" id="m-new"><div class="modal"><h2>Créer un administrateur</h2>
    <form method="POST" action="{{ route('super.admins.store') }}">@csrf
        <div class="grid-2-eq"><div class="field"><label>Prénom *</label><input class="input" name="first_name" value="{{ old('first_name') }}" required></div><div class="field"><label>Nom *</label><input class="input" name="last_name" value="{{ old('last_name') }}" required></div></div>
        <div class="field"><label>E-mail *</label><input class="input" type="email" name="email" value="{{ old('email') }}" required>@error('email')<span class="error">{{ $message }}</span>@enderror</div>
        <div class="field"><label>Fonction</label><input class="input" name="position" value="{{ old('position') }}"></div>
        <div class="field"><label>Rôle</label><select class="input" name="role"><option value="admin">Administrateur</option><option value="super_admin">Super administrateur</option></select></div>
        <p class="xs muted">Un e-mail d’activation sera envoyé (simulé en démo).</p>
        <div class="actions"><button type="button" class="btn ghost" data-close>Annuler</button><button class="btn">Créer</button></div></form></div></div>
@endpush
@endsection
