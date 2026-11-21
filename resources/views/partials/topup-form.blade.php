@if ($methods->isEmpty())
    <div class="alert red">{!! ic('alert') !!} {{ __('Aucun moyen de paiement n’est activé pour le moment.') }}</div>
@else
<form method="POST" action="{{ $action }}" id="topup-form">
    @csrf
    <h3>{{ __('Montant') }}</h3>
    <div class="amounts mt">
        @foreach ($amounts as $i => $a)<label class="amount-btn"><input type="radio" name="preset" value="{{ $a }}" {{ $i === 2 ? 'checked' : '' }} onchange="document.getElementById('amount').value=this.value;tpRefresh()">{{ num($a) }}</label>@endforeach
    </div>
    <div class="field mt"><label>{{ __('Montant libre (FCFA)') }}</label><input class="input" type="number" min="100" step="50" name="amount" id="amount" value="{{ old('amount', $amounts[2]) }}" oninput="document.querySelectorAll('input[name=preset]').forEach(p=>p.checked=false);tpRefresh()" required></div>
    <h3>{{ __('Moyen de paiement') }}</h3>
    <div class="pay-methods mt">
        @foreach ($methods as $i => $m)
            <label class="pay-method"><input type="radio" name="method" value="{{ $m->id }}" {{ $i === 0 ? 'checked' : '' }}><span class="logo" style="background:{{ $m->color }};color:{{ $m->code === 'momo' ? '#1d1d1b' : '#fff' }}">{{ $m->logo }}</span><span class="grow"><b>{{ $m->name }}</b><br><span class="xs muted">{{ $m->kind }}</span></span></label>
        @endforeach
    </div>
    <div class="field mt"><label>{{ __('Numéro Mobile Money') }}</label><input class="input" name="phone" value="{{ $phone }}"></div>
    @if (config('cantine.payment_env') === 'test')<p class="xs muted">{!! ic('lock') !!} {{ __('Environnement de test : aucun débit réel.') }}</p>@endif
    {{ $slot ?? '' }}
    <div class="sticky-cta"><button class="btn block" id="tp-btn" onclick="this.disabled=true;this.innerHTML='<span class=spinner style=width:18px;height:18px;margin:0></span> {{ __('Validation sur votre téléphone…') }}';this.form.submit()">{{ __('Payer') }}</button></div>
</form>
<script>function tpRefresh(){var v=+document.getElementById('amount').value||0;document.getElementById('tp-btn').textContent=@json(__('Payer'))+' '+new Intl.NumberFormat('fr-FR').format(v)+' FCFA';}tpRefresh();</script>
@endif
