@extends('layouts.admin')
@section('title', 'Alertes')
@section('nav', 'alerts')
@section('content')
<div class="page-head">
    <div><h1>Alertes</h1><p>Réservations à risque allergique et notifications de la cantine.</p></div>
    <form method="POST" action="{{ route('admin.alerts.readAll') }}">@csrf<button class="btn sm ghost">Tout marquer comme lu</button></form>
</div>
<div class="stack">
@forelse ($items as $n)
    @php $r = $n->reservation; @endphp
    <div class="card {{ $n->read_at ? 'flat' : '' }}" style="{{ $n->read_at ? '' : 'border-left:4px solid var(--blue)' }}">
        <div class="row between"><div class="row"><span class="notif-ic" style="color:{{ $n->kind === 'allergy' ? 'var(--red)' : 'inherit' }}">{!! ic($n->icon) !!}</span><b>{{ $n->title }}</b>@unless ($n->read_at)<span class="badge">Nouveau</span>@endunless</div><span class="xs muted">{{ $n->created_at->format('d/m · H:i') }}</span></div>
        <p class="small mt">{{ $n->body }}</p>
        @if ($n->kind === 'allergy' && $r)
            <div class="row wrap mt">
                @if ($r->status === 'cancelled')<span class="badge gray">Réservation annulée entre-temps</span>
                @else<span class="badge {{ $r->statusBadge() }}">{{ $r->statusLabel() }}</span>@endif
                @if ($r->checker)<span class="badge green">{!! ic('check') !!} Vérifié par {{ $r->checker->first_name }} {{ mb_substr($r->checker->last_name, 0, 1) }}.</span>@endif
                <span class="grow"></span>
                <a class="btn sm ghost" href="{{ route('admin.orders', ['jour' => $r->menu->date->toDateString(), 'reservation' => $r->id]) }}">Voir la commande</a>
                @if ($r->user?->parents->count())<button class="btn sm ghost" data-open="m-c{{ $n->id }}p">{!! ic('send') !!} Contacter le parent</button>@endif
                <button class="btn sm ghost" data-open="m-c{{ $n->id }}u">{!! ic('send') !!} Contacter le convive</button>
                @unless ($r->checker)<form method="POST" action="{{ route('admin.alerts.check', $r) }}">@csrf<button class="btn sm green">{!! ic('check') !!} Marquer comme vérifié</button></form>@endunless
            </div>
            @push('modals')
                @foreach (['p' => ['parent', 'au parent'], 'u' => ['user', 'au convive']] as $k => [$to, $lbl])
                <div class="modal-bg hidden" id="m-c{{ $n->id }}{{ $k }}"><div class="modal"><h2>Message {{ $lbl }}</h2>
                    <form method="POST" action="{{ route('admin.alerts.contact', $r) }}">@csrf<input type="hidden" name="to" value="{{ $to }}">
                        <textarea class="input" name="message" rows="4">Bonjour, {{ $r->user?->first_name }} a réservé « {{ collect($r->allergy_conflict)->pluck('name')->join(', ') }} » le {{ $r->menu->label('l j M') }}, plat qui contient un allergène déclaré dans son profil. Merci de nous confirmer s’il faut remplacer ce plat. — La cantine</textarea>
                        <div class="actions"><button type="button" class="btn ghost" data-close>Annuler</button><button class="btn">Envoyer</button></div></form>
                </div></div>
                @endforeach
            @endpush
        @elseif ($n->url)
            <a class="small" href="{{ route('notifications.open', $n) }}">Ouvrir ›</a>
        @endif
    </div>
@empty
    <div class="empty"><div class="ico">{!! ic('bell') !!}</div>Aucune alerte.</div>
@endforelse
</div>
@endsection
