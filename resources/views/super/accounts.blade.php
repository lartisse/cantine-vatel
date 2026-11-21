@extends('layouts.admin')
@section('title', 'Comptes')
@section('nav', 'accounts')
@section('content')
<div class="page-head"><div><h1>Comptes</h1><p>Comptes actifs (utilisateurs et parents).</p></div><a class="btn ghost" href="{{ route('super.trash') }}">{!! ic('trash') !!} Corbeille</a></div>
<form class="toolbar row mb" method="GET"><input class="input" name="q" value="{{ $q }}" placeholder="Nom ou e-mail" style="max-width:280px"><button class="btn sm">{!! ic('search') !!} Rechercher</button></form>
<div class="table-wrap card"><table class="tbl">
    <tr><th>Nom</th><th>Type</th><th>Classe / poste</th><th>Liens</th><th class="num">Solde</th><th></th></tr>
    @foreach ($users as $u)
        <tr><td><div class="row"><x-avatar :user="$u" style="width:28px;height:28px;font-size:11px" /><div><b class="small">{{ $u->name }}</b><div class="xs muted">{{ $u->email }}</div></div></div></td><td>{{ $u->typeLabel() }}</td><td class="small">{{ $u->role === 'parent' ? '—' : $u->groupLabel() }}</td>
            <td class="small">{{ $u->role === 'parent' ? $u->children->pluck('first_name')->join(', ') : $u->parents->pluck('name')->join(', ') }}</td>
            <td class="num">{{ $u->role === 'parent' ? '—' : fcfa($u->balance) }}</td>
            <td><button class="btn sm danger" data-open="m-t{{ $u->id }}">{!! ic('trash') !!} Corbeille</button></td></tr>
        @push('modals')
        <div class="modal-bg hidden" id="m-t{{ $u->id }}"><div class="modal"><h2>Mettre {{ $u->name }} en corbeille ?</h2>
            <ul class="small"><li>Connexion bloquée (déconnexion à la requête suivante)</li><li>Compte invisible</li><li>Données conservées, restauration possible</li><li>L’utilisateur et son parent sont notifiés</li></ul>
            <form method="POST" action="{{ route('super.accounts.trash', $u) }}">@csrf<div class="field mt"><label>Motif *</label><input class="input" name="reason" required></div>
                <div class="actions"><button type="button" class="btn ghost" data-close>Annuler</button><button class="btn danger">Mettre en corbeille</button></div></form></div></div>
        @endpush
    @endforeach
</table></div>
{{ $users->links('partials.pager') }}
@endsection
