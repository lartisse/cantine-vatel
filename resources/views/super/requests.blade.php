@extends('layouts.admin')
@section('title', 'Demandes de suppression')
@section('nav', 'requests')
@section('content')
<div class="page-head"><div><h1>Demandes de suppression</h1><p>Accepter place le compte en corbeille ; refuser envoie une justification.</p></div></div>
<h3 class="mb">En attente ({{ $pending->count() }})</h3>
<div class="stack">
@forelse ($pending as $r)
    <div class="card"><div class="row between"><div class="row"><x-avatar :user="$r->user" /><div><b>{{ $r->user?->name }}</b><div class="small muted">{{ $r->user?->typeLabel() }} · {{ $r->user?->groupLabel() }}</div></div></div><span class="xs muted">{{ $r->created_at->format('d/m/Y') }}</span></div>
        <p class="small mt">{{ $r->reason ?: 'Aucun motif indiqué' }}</p>
        <div class="row mt"><button class="btn sm danger" data-open="m-a{{ $r->id }}">Accepter</button><button class="btn sm ghost" data-open="m-r{{ $r->id }}">Refuser</button></div></div>
    @push('modals')
    <div class="modal-bg hidden" id="m-a{{ $r->id }}"><div class="modal"><h2>Mettre {{ $r->user?->name }} en corbeille</h2>
        <form method="POST" action="{{ route('super.accounts.trash', $r->user) }}">@csrf<input type="hidden" name="request_id" value="{{ $r->id }}"><div class="field"><label>Motif *</label><input class="input" name="reason" value="Demande de l’utilisateur{{ $r->reason ? ' : '.$r->reason : '' }}" required></div>
            <div class="actions"><button type="button" class="btn ghost" data-close>Annuler</button><button class="btn danger">Mettre en corbeille</button></div></form></div></div>
    <div class="modal-bg hidden" id="m-r{{ $r->id }}"><div class="modal"><h2>Refuser la demande</h2>
        <form method="POST" action="{{ route('super.requests.handle', $r) }}">@csrf<input type="hidden" name="action" value="refuse"><div class="field"><label>Message de justification *</label><textarea class="input" name="message" rows="3" required></textarea></div>
            <div class="actions"><button type="button" class="btn ghost" data-close>Annuler</button><button class="btn">Envoyer</button></div></form></div></div>
    @endpush
@empty
    <p class="muted">Aucune demande en attente.</p>
@endforelse
</div>
<h3 class="mt mb">Traitées</h3>
<div class="table-wrap card"><table class="tbl"><tr><th>Compte</th><th>Date</th><th>Décision</th><th>Réponse</th><th>Par</th></tr>
    @forelse ($handled as $r)<tr><td>{{ $r->user?->name ?? 'Utilisateur supprimé' }}</td><td class="small">{{ $r->updated_at->format('d/m/Y') }}</td><td><span class="badge {{ $r->status === 'accepted' ? 'red' : 'gray' }}">{{ $r->status === 'accepted' ? 'Acceptée → corbeille' : 'Refusée' }}</span></td><td class="small">{{ $r->response_message }}</td><td class="small">{{ $r->handler?->name }}</td></tr>@empty<tr><td colspan="5" class="muted center">—</td></tr>@endforelse
</table></div>
@endsection
