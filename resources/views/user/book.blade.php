@extends('layouts.mobile')
@section('title', $existing ? __('Modifier ma réservation') : __('Réserver'))
@section('back', route('menu', ['jour' => $menu->date->toDateString()]))
@section('no_tabs', 1)
@section('no_errors', 1)
@section('content')
@php
    $cutoff = slot_label(\App\Support\Settings::get('cutoff', '10:00'));
    $max = config('cantine.max_quantity');
    $prices = $menu->items->mapWithKeys(fn ($i) => [$i->dish_id => $i->antigaspi_price ?? $i->dish->price]);
    $refund = $existing?->total ?? 0;
@endphp
@if (! $menu->isOpen())
    <div class="empty"><div class="ico">{!! ic('clock') !!}</div><b>{{ __('Réservations closes') }}</b><p class="small">{{ __('Les réservations pour :d ont fermé à :h.', ['d' => mb_strtolower($menu->label()), 'h' => $cutoff]) }}</p></div>
@elseif ($already)
    <div class="empty"><div class="ico">{!! ic('ticket') !!}</div><b>{{ __('Déjà réservé') }}</b><p class="small">{{ __('Vous avez déjà une réservation ce jour-là. Vous pouvez la modifier pour ajouter des plats.') }}</p><a class="btn mt" href="{{ route('reservations.show', $already) }}">{{ __('Voir ma réservation') }}</a></div>
@else
<form method="POST" action="{{ $existing ? route('reservations.update', $existing) : route('reservations.store', $menu) }}" id="book">
    @csrf @if ($existing) @method('PUT') @endif
    <input type="hidden" name="confirm_allergy" id="confirm_allergy" value="0">
    <div class="card sky mb"><b>{{ $menu->label() }}</b><div class="small">{{ __('Modifiable ou annulable jusqu’à :h le jour même', ['h' => $cutoff]) }}</div></div>
    @if ($errors->any() && ! $errors->has('allergy'))<div class="alert red mb">{!! ic('alert') !!} <span>{{ $errors->first() }}</span></div>@endif

    <h3>1. {{ __('Composez votre commande') }}</h3>
    <p class="xs muted">{{ __('Choisissez un ou plusieurs plats, puis ajustez les quantités (:n max par article).', ['n' => $max]) }}</p>
    @foreach (['plat', 'entree', 'dessert', 'boisson'] as $c)
        @php $items = $menu->items->filter(fn ($i) => $i->dish?->category === $c); @endphp
        @if ($items->count())
        <div class="cat-label">{{ __(config('cantine.categories')[$c].'s') }}</div>
        <div class="stack">
            @foreach ($items as $item)
                @php $d = $item->dish; $l = $left[$d->id] ?? 0; $q = old('qty.'.$d->id, $selected[$d->id] ?? 0); @endphp
                <x-dish-row :dish="$d" :user="$u" :left="$l" :price="$item->antigaspi_price" data-dish="{{ $d->id }}" data-price="{{ $prices[$d->id] }}" data-name="{{ $d->name }}" data-conflict="{{ implode(', ', array_map(fn ($a) => mb_strtolower(allergen_label($a)), $u->conflictsWith($d))) }}" onclick="bookPick(event, {{ $d->id }})">
                    <div class="stepper mt" onclick="event.stopPropagation()">
                        <button type="button" data-step="-1" data-target="q{{ $d->id }}" aria-label="moins">−</button>
                        <input type="number" class="hidden" min="0" max="{{ min($max, $l) }}" id="q{{ $d->id }}" name="qty[{{ $d->id }}]" value="{{ $q }}">
                        <b id="qv{{ $d->id }}">{{ $q }}</b>
                        <button type="button" data-step="1" data-target="q{{ $d->id }}" aria-label="plus" @if ($l <= 0) disabled @endif>+</button>
                    </div>
                </x-dish-row>
            @endforeach
        </div>
        @endif
    @endforeach

    <h3 class="mt">2. {{ __('Choisissez votre créneau') }}</h3><p class="xs muted mb">{{ __('Places restantes par créneau') }}</p>
    <div class="slots">
        @foreach ($slots as $s => $info)
            <label class="slot"><input type="radio" name="slot" value="{{ $s }}" {{ old('slot', $existing?->slot) === $s ? 'checked' : '' }} {{ $info['left'] <= 0 ? 'disabled' : '' }}><b>{{ slot_label($s) }}</b><span class="xs {{ $info['left'] < 10 ? '' : 'muted' }}" style="{{ $info['left'] < 10 ? 'color:var(--orange);font-weight:700' : '' }}">{{ $info['left'] <= 0 ? __('Complet') : __(':n places', ['n' => $info['left']]) }}</span></label>
        @endforeach
    </div>

    <h3 class="mt">3. {{ __('Récapitulatif') }}</h3>
    <div class="card mt">
        <div id="sum-lines"><p class="muted small">{{ __('Aucun plat sélectionné.') }}</p></div>
        <div class="summary-line total"><span id="sum-count">Total</span><span id="sum-total">0 FCFA</span></div>
        <div class="summary-line small muted"><span>{{ __('Solde actuel') }}@if ($refund) (+ {{ __('remboursement') }} {{ fcfa($refund) }})@endif</span><span>{{ fcfa($u->balance + $refund) }}</span></div>
        <div class="summary-line small"><span>{{ __('Solde après paiement') }}</span><b id="sum-after">{{ fcfa($u->balance + $refund) }}</b></div>
    </div>
    <div class="alert red mt hidden" id="warn-allergy">{!! ic('alert') !!} <span id="warn-text"></span></div>
    <div id="topup-box" class="hidden">
        <div class="alert orange mt" id="topup-text"></div>
        <div class="pay-methods mt">
            @forelse ($methods as $i => $m)
                <label class="pay-method"><input type="radio" name="method" value="{{ $m->id }}" {{ $i === 0 ? 'checked' : '' }} disabled><span class="logo" style="background:{{ $m->color }};color:{{ $m->code === 'momo' ? '#1d1d1b' : '#fff' }}">{{ $m->logo }}</span><span class="grow"><b>{{ $m->name }}</b></span></label>
            @empty
                <div class="alert red">{{ __('Aucun moyen de paiement n’est activé pour le moment.') }}</div>
            @endforelse
        </div>
        <div class="field mt"><label>{{ __('Numéro Mobile Money') }}</label><input class="input" name="phone" value="{{ $u->phone }}"></div>
    </div>
    <div class="sticky-cta"><button type="button" class="btn block" id="book-btn" onclick="bookSubmit()" disabled>{{ __('Choisissez au moins un plat') }}</button></div>
</form>

@push('modals')
<div class="modal-bg {{ $errors->has('allergy') ? '' : 'hidden' }}" id="m-allergy"><div class="modal">
    <h2>{!! ic('alert') !!} {{ __('Plat incompatible') }}</h2>
    <p id="m-allergy-text">{{ __('Un plat choisi contient un allergène déclaré dans votre profil.') }}</p>
    <p class="small muted mt">{{ __('Si vous confirmez, l’équipe de la cantine sera prévenue et vérifiera votre plateau au comptoir.') }}</p>
    <div class="actions"><button class="btn ghost" data-close>{{ __('Modifier ma commande') }}</button><button class="btn danger" onclick="document.getElementById('confirm_allergy').value=1;document.getElementById('book').submit()">{{ __('Je confirme malgré tout') }}</button></div>
</div></div>
@endpush
@push('scripts')
<script>
(function () {
  const balance = {{ $u->balance + $refund }}, fmt = (n) => new Intl.NumberFormat('fr-FR').format(n) + ' FCFA';
  const T = { choose: @json(__('Choisissez au moins un plat')), slot: @json(__('Choisissez un créneau')), pay: @json(__('Confirmer et payer')), save: @json(__('Enregistrer les modifications ·')), payDiff: @json(__('Payer :m et réserver')), missing: @json(__('Solde insuffisant : il manque :m. Payez la différence par Mobile Money.')), incompatible: @json(__('Attention : :d — incompatible avec votre profil.')), items: @json(__('articles')) };
  const edit = {{ $existing ? 'true' : 'false' }};
  window.bookPick = (e, id) => { const i = document.getElementById('q' + id); if (+i.max <= 0) return; i.value = +i.value > 0 ? 0 : 1; i.dispatchEvent(new Event('change', { bubbles: true })); };
  window.bookRefresh = () => {
    let total = 0, count = 0, lines = '', warn = [];
    document.querySelectorAll('[data-dish]').forEach((row) => {
      const id = row.dataset.dish, q = +document.getElementById('q' + id).value;
      document.getElementById('qv' + id).textContent = q;
      row.classList.toggle('selected', q > 0);
      if (q > 0) { total += q * row.dataset.price; count += q; lines += `<div class="summary-line"><span>${q > 1 ? '<b>' + q + '×</b> ' : ''}${row.dataset.name}</span><span>${fmt(q * row.dataset.price)}</span></div>`; if (row.dataset.conflict) warn.push(row.dataset.name + ' (' + row.dataset.conflict + ')'); }
    });
    const slot = document.querySelector('input[name=slot]:checked'), after = balance - total, btn = document.getElementById('book-btn');
    document.getElementById('sum-lines').innerHTML = lines || '<p class="muted small">—</p>';
    document.getElementById('sum-count').textContent = 'Total (' + count + ' ' + T.items + ')';
    document.getElementById('sum-total').textContent = fmt(total);
    const a = document.getElementById('sum-after'); a.textContent = fmt(after); a.style.color = after < 0 ? 'var(--red)' : '';
    const w = document.getElementById('warn-allergy'); w.classList.toggle('hidden', !warn.length); document.getElementById('warn-text').textContent = T.incompatible.replace(':d', warn.join(', '));
    document.getElementById('m-allergy-text').textContent = T.incompatible.replace(':d', warn.join(', '));
    const tb = document.getElementById('topup-box'); tb.classList.toggle('hidden', !(after < 0 && total)); tb.querySelectorAll('input[name=method]').forEach((i) => { i.disabled = !(after < 0 && total); });
    document.getElementById('topup-text').textContent = T.missing.replace(':m', fmt(-after));
    btn.disabled = !(count && slot);
    btn.textContent = !count ? T.choose : !slot ? T.slot : after < 0 ? T.payDiff.replace(':m', fmt(-after)) : (edit ? T.save + ' ' : T.pay + ' ') + fmt(total);
    btn.dataset.warn = warn.length ? '1' : '';
  };
  window.bookSubmit = () => { const b = document.getElementById('book-btn'); if (b.dataset.warn) App.open('m-allergy'); else document.getElementById('book').submit(); };
  document.getElementById('book').addEventListener('change', bookRefresh);
  bookRefresh();
})();
</script>
@endpush
@endif
@endsection
