@extends('layouts.mobile')
@section('title', __('Bonjour, :name', ['name' => $u->first_name]))
@section('tab', 'home')
@section('right')<a class="icon-btn" href="{{ route('notifications') }}" aria-label="Notifications">{!! ic('bell') !!}@if ($unread)<span class="dot">{{ $unread }}</span>@endif</a>@endsection
@section('content')
@php $cutoff = slot_label(\App\Support\Settings::get('cutoff', '10:00')); @endphp

@if ($askClass)
    <div class="card banner-q mb">
        <span class="badge">{{ __('Nouvelle période') }} · {{ $period['label'] }}</span>
        @if ($u->class_name)
            <p class="mt">{!! __('Êtes-vous toujours en <b>:c</b> ?', ['c' => e($u->class_name)]) !!}</p>
            <div class="row mt">
                <form method="POST" action="{{ route('user.class') }}">@csrf<input type="hidden" name="action" value="confirm"><button class="btn sm">{{ __('Oui, c’est bien ça') }}</button></form>
                <button class="btn sm ghost" data-open="m-class">{{ __('Corriger') }}</button>
            </div>
        @else
            <p class="mt small">{{ __('Voulez-vous indiquer votre classe ? Cela aide la cantine à mieux prévoir les repas.') }}</p>
            <div class="row mt">
                <button class="btn sm" data-open="m-class">{{ __('Ajouter ma classe') }}</button>
                <form method="POST" action="{{ route('user.class') }}">@csrf<input type="hidden" name="action" value="skip"><button class="btn sm ghost">{{ __('Ignorer') }}</button></form>
            </div>
        @endif
    </div>
    @push('modals')
    <div class="modal-bg hidden" id="m-class"><div class="modal">
        <h2>{{ __('Ma classe') }}</h2><p class="small muted mb">{{ $period['label'] }}</p>
        <form method="POST" action="{{ route('user.class') }}">@csrf<input type="hidden" name="action" value="save">
            <div class="field"><label>{{ __('Classe') }}</label><select class="input" name="class_name"><option value="">{{ __('Je préfère ne pas l’indiquer') }}</option>@foreach (config('cantine.classes') as $c)<option {{ $u->class_name === $c ? 'selected' : '' }}>{{ $c }}</option>@endforeach</select></div>
            <div class="actions"><button type="button" class="btn ghost" data-close>{{ __('Annuler') }}</button><button class="btn">{{ __('Enregistrer') }}</button></div>
        </form>
    </div></div>
    @endpush
@endif

<div class="balance-card">
    <div class="small" style="color:#bbb">{{ __('Mon solde') }}</div>
    <div class="amount">{{ num($u->balance) }} <small>FCFA</small></div>
    <div class="row"><a class="btn sky sm" href="{{ route('wallet.topup') }}">+ {{ __('Recharger') }}</a><a class="btn sm" style="background:#2b2b28" href="{{ route('wallet') }}">{{ __('Historique') }}</a></div>
</div>
@if ($u->balance < $u->low_balance_threshold)
    <div class="alert orange mt">{!! ic('alert') !!} {{ __('Solde bas : rechargez pour pouvoir réserver.') }}</div>
@endif

@if ($antigaspi->count())
    <div class="banner-gaspi mt">
        <b>{!! ic('leaf') !!} {{ __('Panier anti-gaspi') }}</b>
        <p class="small mt">{{ __('Portions restantes à -50 % aujourd’hui :') }} {{ $antigaspi->map(fn ($i) => $i->dish->name.' ('.fcfa($i->antigaspi_price).')')->join(', ') }}</p>
    </div>
@endif

<div class="mt">
@if ($res)
    <div class="card">
        <div class="row between"><b>{{ __('Mon repas d’aujourd’hui') }}</b><span class="badge {{ $res->statusBadge() }}">{{ $res->statusLabel() }}</span></div>
        @php $main = $res->items->sortBy(fn ($i) => array_search($i->dish->category, ['plat', 'entree', 'dessert', 'boisson']))->first()->dish; @endphp
        <div class="row mt"><x-dish-img :dish="$main" size="md" /><div class="grow"><b>{{ $main->name }}</b><div class="small muted">{{ $res->items->map(fn ($i) => ($i->quantity > 1 ? $i->quantity.'× ' : '').$i->dish->name)->join(', ') }}</div><div class="small mt">{!! ic('clock') !!} {{ __('Créneau') }} <b>{{ $res->slotLabel() }}</b></div></div></div>
        @if ($res->status === 'paid')<a class="btn block mt" href="{{ route('reservations.show', $res) }}">{{ __('Afficher mon QR code') }}</a>
        @elseif ($res->status === 'served')<a class="btn light block mt" href="{{ route('reviews.create', $res->menu) }}">{!! ic('star', 'ic-star') !!} {{ __('Noter ce repas') }}</a>@endif
    </div>
@elseif ($today && $today->isOpen())
    <div class="card" style="border:2px dashed var(--sky)"><b>{{ __('Vous n’avez pas encore réservé') }}</b><p class="small muted">{{ __('Il reste jusqu’à :h pour réserver le repas d’aujourd’hui.', ['h' => $cutoff]) }}</p><a class="btn block mt" href="{{ route('reservations.create', $today) }}">{{ __('Réserver maintenant') }}</a></div>
@else
    <div class="card"><b>{{ $today ? __('Réservations du jour closes') : __('Pas de service aujourd’hui') }}</b><p class="small muted">@if ($today){{ __('Les réservations ferment à :h.', ['h' => $cutoff]) }}@endif @if ($next){{ __('Pensez à réserver pour :d.', ['d' => mb_strtolower($next->label())]) }}@endif</p>
        @if ($next)<a class="btn light block mt" href="{{ route('reservations.create', $next) }}">{{ __('Réserver pour :d', ['d' => $next->label('D j M')]) }}</a>@endif</div>
@endif
</div>

@if ($toRate && (! $res || $toRate->id !== $res->id))
    <a class="card flat mt row" href="{{ route('reviews.create', $toRate->menu) }}" style="color:inherit">{!! ic('star', 'ic-star') !!}<span class="grow small"><b>{{ __('Comment était votre repas ?') }}</b><br><span class="muted">{{ $toRate->menu->label() }} · {{ $toRate->mainDishName() }}</span></span>{!! ic('chevron-right') !!}</a>
@endif

@if ($today)
    <div class="section-title"><h3>{{ __('Plats du jour') }}</h3><a class="small" href="{{ route('menu') }}">{{ __('Tout le menu') }} ›</a></div>
    @php \App\Services\SuggestionService::decorate($today->dishes); @endphp
    <div class="hscroll">
        @foreach ($today->dishes->sortBy(fn ($d) => array_search($d->category, ['plat', 'entree', 'dessert', 'boisson']))->where('category', '!=', 'boisson') as $d)
            @php $c = $u->conflictsWith($d); @endphp
            <a class="mini-dish" href="{{ route('dish.show', [$d, 'menu' => $today->id]) }}" style="color:inherit"><x-dish-img :dish="$d" /><div class="info"><b class="small">{{ $d->name }}</b><div class="xs">{{ fcfa($d->price) }}</div>@if ($c)<span class="badge red xs mt">{!! ic('alert') !!} {{ allergen_label($c[0]) }}</span>@else<div class="xs">{!! stars($d->avg_rating) !!}</div>@endif</div></a>
        @endforeach
    </div>
@endif

@if ($suggestion)
    @php $sMenu = $suggestion['menu']; $sIds = collect($suggestion['items'])->map(fn ($s) => $s['dish']->id)->join(','); @endphp
    <div class="section-title"><h3>{{ __('Suggestions pour vous') }}</h3><span class="small muted">{{ $sMenu->label() }}</span></div>
    <div class="sugg-carousel" data-carousel data-interval="{{ $interval }}" role="region" aria-roledescription="carrousel" aria-label="{{ __('Suggestions pour vous') }}">
    <div class="sugg-scroll" data-track>
        @foreach ($suggestion['items'] as $cat => $s)
            <div class="card flat sugg-card" data-slide role="group" aria-roledescription="diapositive" aria-label="{{ $loop->iteration }} / {{ count($suggestion['items']) }}">
                <span class="badge gray xs">{{ __(config('cantine.categories')[$cat]) }}</span>
                <div class="row mt"><x-dish-img :dish="$s['dish']" size="md" /><div class="grow"><b>{{ $s['dish']->name }}</b><div class="small muted">{{ fcfa($s['dish']->price) }}</div></div></div>
                <p class="xs mt grow">{{ $s['reason'] }}</p>
                <a class="btn light block mt" href="{{ route('reservations.create', [$sMenu, 'plat' => $s['dish']->id]) }}">{{ __('Réserver ce plat') }}</a>
            </div>
        @endforeach
    </div>
    @if (count($suggestion['items']) > 1)
        <div class="sugg-nav">
            <button type="button" class="sugg-arrow" data-prev aria-label="{{ __('Suggestion précédente') }}">{!! ic('chevron-left') !!}</button>
            <div class="sugg-dots" data-dots>
                @foreach ($suggestion['items'] as $cat => $s)<button type="button" aria-label="{{ __(config('cantine.categories')[$cat]) }}"><span></span></button>@endforeach
            </div>
            <button type="button" class="sugg-arrow" data-next aria-label="{{ __('Suggestion suivante') }}">{!! ic('chevron-right') !!}</button>
        </div>
    @endif
    </div>
    @if (count($suggestion['items']) > 1)
        <a class="btn block mt" href="{{ route('reservations.create', [$sMenu, 'plats' => $sIds]) }}">{{ __('Réserver ces :n suggestions', ['n' => count($suggestion['items'])]) }}</a>
    @endif
@endif

@if ($chefs->count())
    <div class="section-title"><h3>{!! ic('graduation') !!} {{ __('Chef de la semaine') }}</h3></div>
    <div class="card flat">
        @foreach ($chefs as $i => $c)
            <div class="summary-line small"><span>{{ ['🥇', '🥈', '🥉'][$i] ?? '' }} <b>{{ $c->name }}</b><br><span class="xs muted">{{ $c->creator_name ?? __('Équipe cuisine') }}</span></span><span>{!! stars($c->avg, $c->n) !!}</span></div>
        @endforeach
    </div>
@endif
@endsection
