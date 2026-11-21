@extends('layouts.mobile')
@section('title', __('Mes réservations'))
@section('tab', 'resa')
@section('content')
<div class="tabs">
    <a class="{{ $tab === 'avenir' ? 'active' : '' }}" href="?onglet=avenir" style="flex:1;text-align:center;padding:8px;border-radius:9px;font-weight:600;color:{{ $tab === 'avenir' ? 'var(--ink)' : 'var(--muted)' }};{{ $tab === 'avenir' ? 'background:#fff;box-shadow:var(--shadow)' : '' }}">{{ __('À venir') }} ({{ $upcoming->count() }})</a>
    <a class="{{ $tab !== 'avenir' ? 'active' : '' }}" href="?onglet=passees" style="flex:1;text-align:center;padding:8px;border-radius:9px;font-weight:600;color:{{ $tab !== 'avenir' ? 'var(--ink)' : 'var(--muted)' }};{{ $tab !== 'avenir' ? 'background:#fff;box-shadow:var(--shadow)' : '' }}">{{ __('Passées') }}</a>
</div>
@php $list = $tab === 'avenir' ? $upcoming : $past; @endphp
<div class="stack">
@forelse ($list as $r)
    @php $main = $r->items->sortBy(fn ($i) => array_search($i->dish->category, ['plat', 'entree', 'dessert', 'boisson']))->first()?->dish; @endphp
    <a class="card" href="{{ route('reservations.show', $r) }}" style="display:block;color:inherit">
        <div class="row between"><b>{{ $r->menu->label() }}</b><span class="badge {{ $r->statusBadge() }}">{{ $r->statusLabel() }}</span></div>
        <div class="row mt"><x-dish-img :dish="$main" size="sm" /><div class="grow small"><b>{{ $main?->name }}</b><div class="muted xs">{{ $r->items->map(fn ($i) => ($i->quantity > 1 ? $i->quantity.'× ' : '').$i->dish->name)->join(', ') }}</div></div><div class="small">{!! ic('clock') !!} {{ $r->slotLabel() }}</div></div>
        @if ($r->status === 'paid')<div class="small bold mt" style="color:var(--blue)">{{ __('Afficher le QR code') }} ›</div>@endif
    </a>
@empty
    <div class="empty"><div class="ico">{!! ic('ticket') !!}</div><b>{{ $tab === 'avenir' ? __('Aucune réservation à venir') : __('Aucune réservation passée') }}</b>@if ($tab === 'avenir')<p class="small">{{ __('Consultez le menu pour réserver votre prochain repas.') }}</p><a class="btn mt" href="{{ route('menu') }}">{{ __('Voir le menu') }}</a>@endif</div>
@endforelse
</div>
@endsection
