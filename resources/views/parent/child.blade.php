@extends('layouts.mobile')
@section('title', __('Suivi de :n', ['n' => $c->first_name]))
@section('tab', 'child')
@section('back', route('parent.home'))
@section('content')
@if ($kids->count() > 1)<div class="chips mb">@foreach ($kids as $k)<a class="chip {{ $k->id === $c->id ? 'active' : '' }}" href="{{ route('parent.child', $k) }}">{{ $k->first_name }}</a>@endforeach</div>@endif
<div class="balance-card"><div class="row between"><div><div class="small" style="color:#bbb">{{ __('Solde de :n', ['n' => $c->first_name]) }}</div><div class="amount" style="margin-bottom:0">{{ num($c->balance) }} <small>FCFA</small></div></div><a class="btn sky sm" href="{{ route('parent.topup', $c) }}">{{ __('Recharger') }}</a></div>
    @if ($c->daily_cap)<div class="xs mt" style="color:#bbb">{{ __('Plafond journalier') }} : {{ fcfa($c->daily_cap) }}</div>@endif</div>

<div class="section-title"><h3>{{ __('Repas de la semaine') }}</h3>@if ($open->count())<button class="btn sm light" data-open="m-book">{{ __('Réserver pour :n', ['n' => $c->first_name]) }}</button>@endif</div>
<div class="card week-list">
    @foreach ($week as $m)
        @php $r = $weekRes[$m->id] ?? null; @endphp
        <div class="wl"><div class="d">{{ ucfirst($m->date->locale(app()->getLocale())->translatedFormat('D')) }}<small>{{ $m->date->format('d/m') }}</small></div>
            <div class="grow small">@if ($r)<b>{{ $r->mainDishName() }}</b><div class="xs muted">{{ $r->slotLabel() }}{{ $r->served_at ? ' · '.__('servi à').' '.$r->served_at->format('H\hi') : '' }}</div>@else<span class="muted">—</span>@endif</div>
            @if ($r)<span class="badge {{ $r->statusBadge() }}">{{ $r->statusLabel() }}</span>@else<span class="badge gray">{{ $m->isPast() ? __('Aucun repas') : __('Pas réservé') }}</span>@endif
        </div>
    @endforeach
</div>

<div class="section-title"><h3>{{ __('Réservations à venir') }}</h3></div>
@forelse ($upcoming as $r)
    <div class="card flat mb"><div class="row between"><b class="small">{{ $r->menu->label() }}</b><span class="small">{!! ic('clock') !!} {{ $r->slotLabel() }}</span></div><div class="small muted mt">{{ $r->items->map(fn ($i) => ($i->quantity > 1 ? $i->quantity.'× ' : '').$i->dish->name)->join(', ') }}</div><div class="row between mt"><b>{{ fcfa($r->total) }}</b><span class="badge">{{ $r->statusLabel() }}</span></div></div>
@empty
    <p class="small muted">{{ __('Aucune réservation à venir.') }}</p>
@endforelse

<div class="section-title"><h3>{{ __('Équilibre alimentaire') }}</h3><span class="xs muted">{{ $served->count() }} {{ __('repas servis') }}</span></div>
<div class="card">
    @if ($served->count())
        @php $max = max(1, max($cats)); @endphp
        <div class="bars-h">@foreach (['plat' => ['Plats', 'var(--sky)'], 'entree' => ['Entrées', '#7fc8a9'], 'dessert' => ['Desserts', '#f2c879'], 'boisson' => ['Boissons', '#c9b6f2']] as $k => [$l, $col])<div class="bh"><span>{{ __($l) }}</span><div class="track"><div class="fill" style="width:{{ $cats[$k] / $max * 100 }}%;background:{{ $col }}"></div></div><b>{{ $cats[$k] }}</b></div>@endforeach</div>
        <div class="row between small mt"><span class="muted">{{ __('Apport moyen par repas') }}</span><b>{{ $kcal }} kcal</b></div>
    @else<p class="small muted">{{ __('Pas encore de repas servi.') }}</p>@endif
</div>

<div class="section-title"><h3>{{ __('Allergies déclarées') }}</h3><button class="btn sm ghost" data-open="m-all">{{ __('Modifier') }}</button></div>
<div class="chips">@forelse ($c->allergyList() as $a)<span class="badge red">{{ allergen_label($a) }}</span>@empty<span class="badge green">{{ __('Aucune') }}</span>@endforelse</div>

<div class="section-title"><h3>{{ __('Dernières dépenses') }}</h3></div>
<div class="card" style="padding:4px 14px">@forelse ($txs as $t)<div class="tx"><div class="grow small"><b>{{ $t->label }}</b><div class="xs muted">{{ $t->created_at->format('d/m') }}</div></div><div class="amt {{ $t->amount > 0 ? 'plus' : '' }}">{{ $t->amount > 0 ? '+' : '' }}{{ fcfa($t->amount) }}</div></div>@empty<p class="small muted" style="padding:10px 0">{{ __('Aucune opération.') }}</p>@endforelse</div>
<button class="btn ghost block mt" data-open="m-cap">{{ __('Définir un plafond journalier') }}</button>

@push('modals')
<div class="modal-bg hidden" id="m-all"><div class="modal"><h2>{{ __('Allergies de :n', ['n' => $c->first_name]) }}</h2>
    <form method="POST" action="{{ route('parent.child.update', $c) }}">@csrf @method('PUT')<input type="hidden" name="field" value="allergies">
        <div class="check-grid">@foreach (config('cantine.allergens') as $k => $l)<label class="check"><input type="checkbox" name="allergies[]" value="{{ $k }}" {{ in_array($k, $c->allergyList(), true) ? 'checked' : '' }}> {{ __($l) }}</label>@endforeach</div>
        <div class="actions"><button type="button" class="btn ghost" data-close>{{ __('Annuler') }}</button><button class="btn">{{ __('Enregistrer') }}</button></div></form></div></div>
<div class="modal-bg hidden" id="m-cap"><div class="modal"><h2>{{ __('Plafond journalier') }}</h2><p class="muted small mb">{{ __(':n ne pourra pas dépenser plus que ce montant par jour.', ['n' => $c->first_name]) }}</p>
    <form method="POST" action="{{ route('parent.child.update', $c) }}">@csrf @method('PUT')<input type="hidden" name="field" value="cap">
        <select class="input" name="daily_cap">@foreach ([0, 1500, 2000, 2500, 3000] as $v)<option value="{{ $v }}" {{ (int) $c->daily_cap === $v ? 'selected' : '' }}>{{ $v ? fcfa($v) : __('Aucun plafond') }}</option>@endforeach</select>
        <div class="actions"><button type="button" class="btn ghost" data-close>{{ __('Annuler') }}</button><button class="btn">{{ __('Enregistrer') }}</button></div></form></div></div>
@if ($open->count())
<div class="modal-bg hidden" id="m-book"><div class="modal"><h2>{{ __('Réserver pour :n', ['n' => $c->first_name]) }}</h2>
    <form method="POST" action="{{ route('parent.child.book', $c) }}">@csrf
        <div class="field"><label>{{ __('Jour') }}</label><select class="input" name="menu_id" id="pb-day" onchange="pbDay()">@foreach ($open as $m)<option value="{{ $m->id }}">{{ $m->label() }}</option>@endforeach</select></div>
        @foreach ($open as $m)
            <div class="pb-menu" data-menu="{{ $m->id }}">
                @foreach (['plat' => 'Plat', 'boisson' => 'Boisson', 'dessert' => 'Dessert'] as $cat => $lbl)
                    <div class="field"><label>{{ __($lbl) }}</label><select class="input" data-name="qty">
                        <option value="">—</option>
                        @foreach ($m->items->filter(fn ($i) => $i->dish->category === $cat) as $i)
                            @php $conf = $c->conflictsWith($i->dish); @endphp
                            <option value="{{ $i->dish_id }}" {{ $conf ? 'disabled' : '' }}>{{ $i->dish->name }} · {{ fcfa($i->dish->price) }}{{ $conf ? ' — '.__('incompatible') : '' }}</option>
                        @endforeach
                    </select></div>
                @endforeach
            </div>
        @endforeach
        <div class="field"><label>{{ __('Créneau') }}</label><select class="input" name="slot">@foreach ($slots as $s => $info)<option value="{{ $s }}" {{ $info['left'] <= 0 ? 'disabled' : '' }}>{{ slot_label($s) }} ({{ $info['left'] }} {{ __('places') }})</option>@endforeach</select></div>
        <p class="xs muted">{{ __('Payé avec le solde de :n. Les plats incompatibles avec ses allergies sont désactivés.', ['n' => $c->first_name]) }}</p>
        <div id="pb-hidden"></div>
        <div class="actions"><button type="button" class="btn ghost" data-close>{{ __('Annuler') }}</button><button class="btn" onclick="pbPrepare()">{{ __('Réserver') }}</button></div>
    </form></div></div>
@endif
@endpush
@push('scripts')
<script>
function pbDay() { const v = document.getElementById('pb-day').value; document.querySelectorAll('.pb-menu').forEach(m => m.classList.toggle('hidden', m.dataset.menu !== v)); }
function pbPrepare() { const v = document.getElementById('pb-day').value, h = document.getElementById('pb-hidden'); h.innerHTML = ''; document.querySelectorAll('.pb-menu[data-menu="' + v + '"] select').forEach(s => { if (s.value) h.insertAdjacentHTML('beforeend', '<input type="hidden" name="qty[' + s.value + ']" value="1">'); }); }
if (document.getElementById('pb-day')) pbDay();
</script>
@endpush
@endsection
