@extends('layouts.admin')
@section('title', 'Plats et recettes')
@section('nav', 'dishes')
@section('content')
<div class="page-head">
    <div><h1>Plats et recettes</h1><p>{{ $dishes->count() }} plat(s) dans le catalogue</p></div>
    <a class="btn" href="{{ route('admin.dishes.create') }}">{!! ic('plus') !!} Nouveau plat</a>
</div>
<form class="toolbar row wrap mb" method="GET">
    <input class="input" name="q" value="{{ $q }}" placeholder="Rechercher" style="max-width:240px">
    <div class="chips"><a class="chip {{ ! $cat ? 'active' : '' }}" href="?">Tous</a>@foreach (config('cantine.categories') as $k => $l)<a class="chip {{ $cat === $k ? 'active' : '' }}" href="?cat={{ $k }}">{{ $l }}s</a>@endforeach</div>
    <label class="check"><input type="checkbox" name="archives" value="1" {{ request('archives') ? 'checked' : '' }} onchange="this.form.submit()"> Archivés</label>
</form>
<div class="dish-grid">
    @foreach ($dishes as $d)
        <a class="dish-card" href="{{ route('admin.dishes.edit', $d) }}" style="color:inherit">
            <x-dish-img :dish="$d" />
            <div class="info"><div class="row between"><b>{{ $d->name }}</b><b>{{ fcfa($d->price) }}</b></div>
                <div class="xs muted">{{ $d->categoryLabel() }}@if ($d->kcal) · {{ $d->kcal }} kcal @endif @if ($d->is_archived)<span class="badge gray">Archivé</span>@endif</div>
                <div class="xs mt">{!! stars($d->avg_rating, $d->rating_count) !!}</div>
                <div class="row wrap mt" style="gap:4px">@foreach ($d->allergens ?? [] as $a)<span class="badge gray xs">{{ allergen_label($a) }}</span>@endforeach</div>
            </div>
        </a>
    @endforeach
</div>
@endsection
