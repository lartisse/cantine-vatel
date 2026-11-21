@extends('layouts.public')
@section('title', 'Menu de la semaine')
@section('content')
<div style="max-width:900px;margin:0 auto;padding:32px 20px">
    <h1>Menu de la semaine</h1>
    <p class="muted mb">Consultation libre. Connectez-vous pour réserver.</p>
    <div class="grid-3">
        @forelse ($menus as $m)
            <div class="card">
                <h3>{{ $m->label() }}</h3>
                @foreach (['entree', 'plat', 'dessert', 'boisson'] as $c)
                    @php $ds = $m->dishes->where('category', $c); @endphp
                    @if ($ds->count())
                        <div class="cat-label">{{ config('cantine.categories')[$c] }}s</div>
                        @foreach ($ds as $d)<div class="summary-line small"><span>{{ $d->name }}</span><span>{{ fcfa($d->price) }}</span></div>@endforeach
                    @endif
                @endforeach
            </div>
        @empty
            <p class="muted">Le menu de la semaine n’est pas encore publié.</p>
        @endforelse
    </div>
    <a class="btn mt" href="{{ route('register') }}">Créer un compte pour réserver</a>
</div>
@endsection
