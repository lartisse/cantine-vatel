@extends('layouts.admin')
@section('title', 'Utilisateurs')
@section('nav', 'users')
@section('content')
<div class="page-head"><div><h1>Utilisateurs</h1><p>Consultation des comptes. Désactivation et suppression : super administrateur.</p></div></div>
<form class="toolbar row wrap mb" method="GET">
    <input class="input" name="q" value="{{ $q }}" placeholder="Nom, e-mail ou matricule" style="max-width:260px">
    <div class="chips">@foreach (['' => 'Tous', 'eleve' => 'Élèves', 'personnel' => 'Personnel', 'parent' => 'Parents'] as $k => $l)<a class="chip {{ (string) $role === $k ? 'active' : '' }}" href="?role={{ $k }}&q={{ $q }}">{{ $l }}</a>@endforeach</div>
    <button class="btn sm">{!! ic('search') !!} Rechercher</button>
</form>
<div class="table-wrap card"><table class="tbl">
    <tr><th>Nom</th><th>Type</th><th>Classe / poste</th><th>Matricule</th><th class="num">Solde</th><th>Allergies</th><th></th></tr>
    @foreach ($users as $u)
        <tr><td><div class="row"><x-avatar :user="$u" style="width:28px;height:28px;font-size:11px" /><b class="small">{{ $u->name }}</b></div></td><td>{{ $u->typeLabel() }}</td><td class="small">{{ $u->role === 'parent' ? '—' : $u->groupLabel() }}</td><td class="small">{{ $u->matricule ?: '—' }}</td>
            <td class="num" style="{{ $u->role !== 'parent' && $u->balance < $u->low_balance_threshold ? 'color:var(--orange);font-weight:700' : '' }}">{{ $u->role === 'parent' ? '—' : fcfa($u->balance) }}</td>
            <td>@foreach ($u->allergies ?? [] as $a)<span class="badge red xs">{{ allergen_label($a) }}</span>@endforeach</td>
            <td><a class="btn sm ghost" href="{{ route('admin.users.show', $u) }}">Fiche</a></td></tr>
    @endforeach
</table></div>
{{ $users->links('partials.pager') }}
@endsection
