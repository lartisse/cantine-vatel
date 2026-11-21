@extends('layouts.mobile')
@section('title', __('Menu de la semaine'))
@section('tab', 'menu')
@section('content')
@php
    $q = fn (array $over) => route('menu', array_merge(['semaine' => $week, 'jour' => $menu?->date->toDateString(), 'cat' => $cat, 'regime' => $diet, 'compatible' => $compat ? 1 : 0], $over));
    $cats = ['all' => __('Tout'), 'entree' => __('Entrées'), 'plat' => __('Plats'), 'dessert' => __('Desserts'), 'boisson' => __('Boissons')];
    $cutoff = slot_label(\App\Support\Settings::get('cutoff', '10:00'));
@endphp
<div class="row between mb">
    <a class="btn sm ghost" href="{{ route('menu', ['semaine' => $week - 1]) }}" aria-label="{{ __('Semaine précédente') }}">{!! ic('chevron-left') !!}</a>
    <b>{{ __('Semaine du :d', ['d' => $monday->locale(app()->getLocale())->translatedFormat('j M')]) }}</b>
    <a class="btn sm ghost" href="{{ route('menu', ['semaine' => $week + 1]) }}" aria-label="{{ __('Semaine suivante') }}">{!! ic('chevron-right') !!}</a>
</div>
@if ($menus->isEmpty())
    <div class="empty"><div class="ico">{!! ic('calendar') !!}</div>{{ __('Le menu de cette semaine n’est pas encore publié.') }}</div>
@else
<div class="days">
    @foreach ($menus as $m)
        <a class="day {{ $menu && $m->id === $menu->id ? 'active' : '' }} {{ $m->date->isToday() ? 'today' : '' }}" href="{{ $q(['jour' => $m->date->toDateString()]) }}"><small>{{ ucfirst($m->date->locale(app()->getLocale())->translatedFormat('D')) }}</small><b>{{ $m->date->format('d') }}</b></a>
    @endforeach
</div>
<div class="hscroll mb" style="gap:8px">@foreach ($cats as $k => $l)<a class="chip {{ $cat === $k ? 'active' : '' }}" href="{{ $q(['cat' => $k]) }}">{{ $l }}</a>@endforeach</div>
<div class="hscroll mb" style="gap:8px">
    <a class="chip {{ $diet === 'all' ? 'active' : '' }}" href="{{ $q(['regime' => 'all']) }}">{{ __('Tous régimes') }}</a>
    @foreach (config('cantine.diets') as $k => $l)<a class="chip {{ $diet === $k ? 'active' : '' }}" href="{{ $q(['regime' => $k]) }}">@if ($k === 'vegetarien'){!! ic('leaf') !!} @endif{{ __($l) }}</a>@endforeach
</div>
<a class="toggle" href="{{ $q(['compatible' => $compat ? 0 : 1]) }}" style="color:inherit"><span class="grow small"><b>{{ __('Compatible avec mes allergies') }}</b><br><span class="muted xs">{{ $u->allergyList() ? __('Masque les plats contenant :').' '.implode(', ', array_map('allergen_label', $u->allergyList())) : __('Aucune allergie déclarée') }}</span></span><span class="switch {{ $compat ? 'on' : '' }}"></span></a>

<div class="row between mt"><h3>{{ $menu->label() }}</h3><button class="btn sm ghost" data-open="m-reviews">@if ($menuReviews->count()){!! stars($menuReviews->avg('rating')) !!} · {{ $menuReviews->count() }} {{ __('avis') }}@else{{ __('Avis') }}@endif</button></div>

@forelse ($groups as $c => $items)
    <div class="cat-label">{{ $cats[$c] }}</div>
    <div class="stack">
        @foreach ($items as $item)
            <x-dish-row :dish="$item->dish" :user="$u" :left="$left[$item->dish_id] ?? null" :price="$item->antigaspi_price" :href="route('dish.show', [$item->dish, 'menu' => $menu->id])" />
        @endforeach
    </div>
@empty
    <div class="empty"><div class="ico">{!! ic('search') !!}</div>{{ __('Aucun plat ne correspond à ces filtres ce jour-là.') }}<br><a class="btn sm ghost mt" href="{{ route('menu', ['jour' => $menu->date->toDateString(), 'semaine' => $week]) }}">{{ __('Effacer les filtres') }}</a></div>
@endforelse

@if ($canReview)<a class="btn light block mt" href="{{ route('reviews.create', $menu) }}">{!! ic('star', 'ic-star') !!} {{ __('Donner mon avis sur ce menu') }}</a>@endif

<div class="sticky-cta">
    @if ($res)<a class="btn light block" href="{{ route('reservations.show', $res) }}">{!! ic('check') !!} {{ __('Déjà réservé — voir ma réservation') }}</a>
    @elseif ($menu->isOpen())<a class="btn block" href="{{ route('reservations.create', $menu) }}">{{ __('Réserver pour :d', ['d' => $menu->label('D j M')]) }}</a>
    @else<button class="btn block" disabled>{{ $menu->isPast() ? __('Jour passé') : __('Réservations closes à :h', ['h' => $cutoff]) }}</button>@endif
</div>

@push('modals')
<div class="modal-bg hidden" id="m-reviews"><div class="modal">
    <h2>{{ __('Avis') }} — {{ $menu->label() }}</h2>
    @forelse ($menuReviews as $v)
        <div class="card flat mb"><div class="row between"><div class="row"><x-avatar :user="$v->user" style="width:28px;height:28px;font-size:11px" /><b class="small">{{ $v->authorName() }}</b></div>{!! stars($v->rating) !!}</div>@if ($v->comment)<p class="small mt">{{ $v->comment }}</p>@endif<span class="xs muted">{{ $v->created_at->format('d/m · H:i') }}</span></div>
    @empty
        <p class="muted">{{ __('Pas encore d’avis sur ce menu.') }}</p>
    @endforelse
    <p class="xs muted mt">{{ __('Seuls les convives ayant réservé ce menu peuvent le noter.') }}</p>
    <div class="actions">@if ($canReview)<a class="btn" href="{{ route('reviews.create', $menu) }}">{{ __('Donner mon avis') }}</a>@endif<button class="btn ghost" data-close>{{ __('Fermer') }}</button></div>
</div></div>
@endpush
@endif
@endsection
