@extends('layouts.admin')
@section('title', 'Corbeille')
@section('nav', 'trash')
@section('content')
<div class="page-head"><div><h1>Corbeille</h1><p>Comptes désactivés : données conservées, restauration possible.</p></div>
    <form method="POST" action="{{ route('super.trash.purge') }}" data-confirm="Exécuter la purge maintenant ?" data-confirm-text="Les comptes en corbeille depuis plus de {{ $days }} jours seront supprimés définitivement." data-danger>@csrf<button class="btn ghost">{!! ic('refresh') !!} Exécuter la purge maintenant</button></form></div>
<div class="grid-2">
    <div class="table-wrap card"><table class="tbl">
        <tr><th>Nom</th><th>Type</th><th>Mise en corbeille</th><th>Motif</th><th>Purge</th><th></th></tr>
        @forelse ($users as $u)
            @php $left = $days - (int) $u->trashed_at->diffInDays(now()); @endphp
            <tr><td><b class="small">{{ $u->name }}</b></td><td>{{ $u->typeLabel() }}</td><td class="small">{{ $u->trashed_at->format('d/m/Y') }}<br><span class="xs muted">par {{ $u->trashed_by }}</span></td><td class="small">{{ $u->trash_reason }}</td>
                <td><span class="badge {{ $left <= 0 ? 'red' : ($left <= 7 ? 'orange' : 'gray') }}">{{ $left <= 0 ? 'Due' : 'Purge dans '.$left.' j' }}</span></td>
                <td class="row" style="gap:6px"><form method="POST" action="{{ route('super.trash.restore', $u) }}" data-confirm="Restaurer {{ $u->name }} ?" data-confirm-text="L’utilisateur et son parent seront notifiés.">@csrf<button class="btn sm light">Restaurer</button></form><button class="btn sm danger" data-open="m-del{{ $u->id }}">Supprimer définitivement</button></td></tr>
            @push('modals')
            <div class="modal-bg hidden" id="m-del{{ $u->id }}"><div class="modal" style="max-width:600px">
                <h2>Supprimer définitivement {{ $u->name }}</h2>
                <div class="alert red">{!! ic('alert') !!} <b>Action irréversible</b></div>
                <div class="grid-2-eq mt small"><div><b>Effacé</b><ul><li>Identité, contact, photo</li><li>Classe, matricule, poste</li><li>Allergies</li><li>Liaison parent</li></ul></div><div><b>Conservé, anonymisé</b><ul><li>Réservations (statistiques)</li><li>Avis « Utilisateur supprimé »</li><li>Écritures comptables</li></ul></div></div>
                <form method="POST" action="{{ route('super.trash.destroy', $u) }}" class="mt">@csrf @method('DELETE')
                    <div class="chips mb"><label class="chip active"><input type="radio" name="mode" value="name" checked class="hidden" onchange="this.closest('form').querySelectorAll('.chip').forEach(c=>c.classList.toggle('active',c.contains(this)));this.closest('form').querySelector('[name=confirm]').type='text'">Retaper le nom</label><label class="chip"><input type="radio" name="mode" value="password" class="hidden" onchange="this.closest('form').querySelectorAll('.chip').forEach(c=>c.classList.toggle('active',c.contains(this)));this.closest('form').querySelector('[name=confirm]').type='password'">Mon mot de passe</label></div>
                    <div class="field"><label>Saisissez « {{ $u->name }} » ou votre mot de passe</label><input class="input" name="confirm" autocomplete="off" required data-expected="{{ mb_strtolower($u->name) }}" oninput="const f=this.form,m=f.mode.value;f.querySelector('.btn.danger').disabled=m==='name'?this.value.trim().toLowerCase()!==this.dataset.expected:this.value.length<4"></div>
                    <div class="actions"><button type="button" class="btn ghost" data-close>Annuler</button><button class="btn danger" disabled>Supprimer définitivement</button></div>
                </form></div></div>
            @endpush
        @empty
            <tr><td colspan="6" class="center muted">La corbeille est vide.</td></tr>
        @endforelse
    </table></div>
    <div class="card"><h3>Purge automatique</h3><p class="small mt">Les comptes en corbeille sont supprimés définitivement après ce délai (planificateur Laravel, chaque nuit).</p>
        <form method="POST" action="{{ route('super.trash.delay') }}" class="row mt">@csrf @method('PUT')<input class="input" type="number" min="1" max="365" name="days" value="{{ $days }}" style="max-width:100px"><span class="small">jours</span><button class="btn sm">Enregistrer</button></form></div>
</div>
@endsection
