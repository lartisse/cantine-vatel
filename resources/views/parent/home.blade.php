@extends('layouts.mobile')
@section('title', __('Bonjour, :name', ['name' => $p->first_name]))
@section('tab', 'home')
@section('right')<a class="icon-btn" href="{{ route('parent.alerts') }}" aria-label="Notifications">{!! ic('bell') !!}@if ($unread)<span class="dot">{{ $unread }}</span>@endif</a>@endsection
@section('content')
@foreach ($kids->filter(fn ($c) => $c->balance < $c->low_balance_threshold) as $c)
    <div class="alert orange mb">{!! ic('alert') !!} <span class="grow">{!! __('Le solde de <b>:n</b> est de :m.', ['n' => e($c->first_name), 'm' => fcfa($c->balance)]) !!}</span><a class="btn sm" href="{{ route('parent.topup', $c) }}">{{ __('Recharger') }}</a></div>
@endforeach
@if ($kids->count())
<div class="stack">
    @foreach ($kids as $c)
        @php $r = $todayRes[$c->id] ?? null; $dot = $c->balance < $c->low_balance_threshold / 2 ? 'var(--red)' : ($c->balance < $c->low_balance_threshold ? 'var(--orange)' : 'var(--green)'); @endphp
        <div class="child-card">
            <a class="row" href="{{ route('parent.child', $c) }}" style="color:inherit"><x-avatar :user="$c" /><div class="grow"><b>{{ $c->name }}</b><div class="small muted">{{ $c->class_name ?: __('Classe non renseignée') }}</div></div><div style="text-align:right"><div class="xs muted">{{ __('Solde') }}</div><b><span class="pill-dot" style="background:{{ $dot }}"></span> {{ fcfa($c->balance) }}</b></div></a>
            <div class="divider"></div>
            <div class="row between small"><span class="muted">{{ __('Aujourd’hui') }}</span>@if ($r)<span>{{ $r->mainDishName() }} · <span class="badge {{ $r->statusBadge() }}">{{ $r->statusLabel() }}</span></span>@else<span class="badge gray">{{ __('Pas de réservation') }}</span>@endif</div>
            <div class="row mt"><a class="btn sm light grow" href="{{ route('parent.topup', $c) }}">{{ __('Recharger') }}</a><a class="btn sm ghost grow" href="{{ route('parent.child', $c) }}">{{ __('Voir ses repas') }}</a></div>
        </div>
    @endforeach
</div>
@else
    <div class="empty"><div class="ico">{!! ic('users') !!}</div><b>{{ __('Aucun enfant lié') }}</b><p class="small">{{ __('Ajoutez votre premier enfant en scannant son QR code.') }}</p></div>
@endif
<a class="btn ghost block mt" href="{{ route('parent.link') }}">+ {{ __('Ajouter un enfant') }}</a>
<div class="section-title"><h3>{{ __('Ce mois-ci') }}</h3></div>
<div class="card"><div class="row between"><span class="muted small">{{ __('Total rechargé') }}</span><b>{{ fcfa($topped) }}</b></div><div class="row between mt"><span class="muted small">{{ __('Repas pris par vos enfants') }}</span><b>{{ $meals }}</b></div><a class="small mt" style="display:block" href="{{ route('parent.payments') }}">{{ __('Historique des paiements') }} ›</a></div>
@endsection
