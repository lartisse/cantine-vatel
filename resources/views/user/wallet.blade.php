@extends('layouts.mobile')
@section('title', __('Portefeuille'))
@section('tab', 'wallet')
@section('content')
<div class="balance-card center"><div class="small" style="color:#bbb">{{ __('Solde disponible') }}</div><div class="amount">{{ num($u->balance) }} <small>FCFA</small></div><a class="btn sky block" href="{{ route('wallet.topup') }}">+ {{ __('Recharger mon compte') }}</a></div>
<div class="chips mt">
    @foreach ($months as $m)<a class="chip {{ $m === $month ? 'active' : '' }}" href="?mois={{ $m }}">{{ ucfirst(\Illuminate\Support\Carbon::parse($m.'-01')->locale(app()->getLocale())->translatedFormat('F Y')) }}</a>@endforeach
</div>
<div class="card mt"><div class="row between"><span class="muted small">{{ __('Dépensé ce mois') }}</span><b>{{ fcfa($spent) }}</b></div><div class="row between mt"><span class="muted small">{{ __('Repas pris') }}</span><b>{{ $meals }}</b></div></div>
@forelse ($txs as $day => $list)
    <div class="cat-label">{{ ucfirst(\Illuminate\Support\Carbon::parse($day)->locale(app()->getLocale())->translatedFormat('l j F')) }}</div>
    <div class="card" style="padding:4px 14px">
        @foreach ($list as $t)
            <a class="tx" href="{{ route('wallet.receipt', $t) }}" style="color:inherit"><div class="ico">{!! ic(\App\Models\Transaction::ICONS[$t->type] ?? 'coins') !!}</div><div class="grow small"><b>{{ $t->label }}</b><div class="xs muted">{{ $t->reference ? __('Réf.').' '.$t->reference : __(\App\Models\Transaction::TYPES[$t->type] ?? $t->type) }}</div></div><div class="amt {{ $t->amount > 0 ? 'plus' : '' }}">{{ $t->amount > 0 ? '+' : '' }}{{ fcfa($t->amount) }}</div></a>
        @endforeach
    </div>
@empty
    <div class="empty"><div class="ico">{!! ic('receipt') !!}</div>{{ __('Aucune transaction ce mois-ci.') }}</div>
@endforelse
@endsection
