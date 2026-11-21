@extends('layouts.mobile')
@section('title', __('Ma réservation'))
@section('back', route('reservations.index'))
@section('no_tabs', 1)
@section('content')
@php $cutoff = slot_label(\App\Support\Settings::get('cutoff', '10:00')); @endphp
@if ($r->status === 'paid')
    <div class="qr-box">
        <div data-qr="CV:{{ $r->qr_token }}" data-size="200"></div>
        <div class="qr-code-text">{{ $r->code }}</div>
        <p class="xs muted">{{ __('Code de secours à donner au comptoir') }}</p>
        <span class="badge green mt">{!! ic('check') !!} {{ __('Disponible hors ligne') }}</span>
    </div>
@else
    <div class="card center"><span class="badge {{ $r->statusBadge() }}">{{ $r->statusLabel() }}</span>
        <p class="mt">@if ($r->status === 'served'){{ __('Repas servi') }}{{ $r->served_at ? ' '.__('à').' '.$r->served_at->format('H\hi') : '' }}.@elseif ($r->status === 'cancelled'){{ __('Réservation annulée et remboursée.') }}@else{{ __('Repas non retiré.') }}@endif</p></div>
@endif
<div class="card mt">
    <div class="row between"><b>{{ $r->menu->label() }}</b><span class="badge {{ $r->statusBadge() }}">{{ $r->statusLabel() }}</span></div>
    <div class="small mt">{!! ic('clock') !!} {{ __('Créneau') }} <b>{{ $r->slotLabel() }}</b></div>
    <div class="divider"></div>
    @foreach ($r->items as $i)<div class="summary-line"><span>@if ($i->quantity > 1)<b>{{ $i->quantity }}×</b> @endif{{ $i->dish->name }}</span><span>{{ fcfa($i->unit_price * $i->quantity) }}</span></div>@endforeach
    <div class="summary-line total"><span>{{ __('Total payé') }}</span><span>{{ fcfa($r->total) }}</span></div>
</div>
@if ($r->allergy_conflict)
    <div class="alert red mt">{!! ic('alert') !!} <span>{{ __('Plat contenant un allergène déclaré : l’équipe de la cantine a été prévenue.') }}</span></div>
@endif
@if ($r->canBeChanged())
    <div class="row mt">
        <a class="btn ghost grow" href="{{ route('reservations.edit', $r) }}">{{ __('Modifier') }}</a>
        <form method="POST" action="{{ route('reservations.destroy', $r) }}" class="grow" data-confirm="{{ __('Annuler la réservation ?') }}" data-confirm-text="{{ __(':m seront recrédités sur le solde.', ['m' => fcfa($r->total)]) }}" data-confirm-ok="{{ __('Annuler la réservation') }}" data-danger>@csrf @method('DELETE')<button class="btn danger block">{{ __('Annuler') }}</button></form>
    </div>
    <p class="xs muted center mt">{{ __('Modifiable jusqu’à :h le :d — remboursement immédiat sur le solde.', ['h' => $cutoff, 'd' => mb_strtolower($r->menu->label())]) }}</p>
@endif
@if ($r->status !== 'cancelled' && ! $r->menu->date->isFuture())
    <a class="btn {{ $reviewed ? 'ghost' : '' }} block mt" href="{{ route('reviews.create', $r->menu) }}">@if ($reviewed){{ __('Voir mon avis') }}@else{!! ic('star', 'ic-star') !!} {{ __('Donner mon avis sur ce menu') }}@endif</a>
@endif
@push('scripts')
<script>App.saveOffline('resa-{{ $r->id }}', { code: '{{ $r->code }}', qr: 'CV:{{ $r->qr_token }}', date: @json($r->menu->label()), slot: '{{ $r->slotLabel() }}' });</script>
@endpush
@endsection
