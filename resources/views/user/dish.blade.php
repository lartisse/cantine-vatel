@extends('layouts.mobile')
@section('title', $d->name)
@section('tab', 'menu')
@section('back', url()->previous() !== url()->current() ? url()->previous() : route('menu'))
@section('content')
<div style="margin:-16px -16px 16px"><x-dish-img :dish="$d" size="lg" /></div>
<div class="row between"><h2>{{ $d->name }}</h2><b>{{ fcfa($d->price) }}</b></div>
<div class="small mt">{!! stars($d->avg_rating, $d->rating_count) !!}</div>
<div class="small muted mt">{{ __($d->categoryLabel()) }}@if ($d->kcal) · {{ $d->kcal }} kcal @endif</div>
@if ($conflicts)
    <div class="alert red mt">{!! ic('alert') !!} <span>{{ __('Contient :a, allergène déclaré dans votre profil.', ['a' => implode(', ', array_map(fn ($a) => mb_strtolower(allergen_label($a)), $conflicts))]) }}</span></div>
@endif
<p class="mt">{{ $d->description }}</p>
@if ($d->ingredients->count())
    <div class="cat-label">{{ __('Ingrédients') }}</div>
    <p class="small">{{ $d->ingredients->pluck('name')->join(', ') }}</p>
@endif
<div class="cat-label">{{ __('Allergènes') }}</div>
<div class="row wrap">@forelse ($d->allergens ?? [] as $a)<span class="badge {{ in_array($a, $conflicts, true) ? 'red' : 'gray' }}">{{ allergen_label($a) }}</span>@empty<span class="small muted">{{ __('Aucun allergène majeur') }}</span>@endforelse</div>
<div class="cat-label">{{ __('Régimes') }}</div>
<div class="row wrap">@forelse ($d->diets ?? [] as $k)<span class="badge green">{{ __(config('cantine.diets')[$k] ?? $k) }}</span>@empty<span class="small muted">—</span>@endforelse</div>
@if ($d->creator_name)<p class="xs muted mt">{!! ic('graduation') !!} {{ __('Recette créée par :n', ['n' => $d->creator_name]) }}</p>@endif
<div class="cat-label">{{ __('Avis') }}</div>
@forelse ($reviews as $v)
    <div class="card flat mb"><div class="row between"><b class="small">{{ $v->authorName() }}</b>{!! stars($v->rating) !!}</div><p class="small mt">{{ $v->comment }}</p></div>
@empty
    <p class="small muted">{{ __('Pas encore de commentaire.') }}</p>
@endforelse
<div class="sticky-cta">
    @if ($menu && $menu->isOpen())<a class="btn block" href="{{ route('reservations.create', [$menu, 'plat' => $d->id]) }}">{{ __('Ajouter à ma réservation') }}</a>
    @else<a class="btn ghost block" href="{{ route('menu') }}">{{ __('Retour au menu') }}</a>@endif
</div>
@endsection
