@extends('layouts.mobile')
@section('title', __('Recharger'))
@section('tab', 'topup')
@section('back', route('parent.home'))
@section('content')
<div class="cat-label" style="margin-top:0">{{ __('Enfant') }}</div>
<div class="chips mb">@foreach ($kids as $k)<a class="chip {{ $k->id === $c->id ? 'active' : '' }}" href="{{ route('parent.topup', $k) }}">{{ $k->first_name }} · {{ fcfa($k->balance) }}</a>@endforeach</div>
@component('partials.topup-form', ['action' => route('parent.topup', $c), 'amounts' => [2500, 5000, 10000, 20000], 'phone' => $p->phone, 'methods' => $methods])
    <div class="toggle mt"><span class="grow small"><b>{{ __('Recharge automatique') }}</b><br><span class="muted xs">{{ __('Recharger automatiquement quand le solde passe sous :s', ['s' => fcfa($c->low_balance_threshold)]) }}</span></span>
        <input type="checkbox" name="auto_topup" value="1" id="auto" class="hidden" {{ $c->auto_topup ? 'checked' : '' }}><button type="button" class="switch {{ $c->auto_topup ? 'on' : '' }}" data-toggle="auto" aria-label="Recharge automatique"></button></div>
    <div class="field mt"><label>{{ __('Montant de la recharge automatique') }}</label><select class="input" name="auto_topup_amount">@foreach ([2500, 5000, 10000] as $v)<option value="{{ $v }}" {{ $c->auto_topup_amount == $v ? 'selected' : '' }}>{{ fcfa($v) }}</option>@endforeach</select></div>
@endcomponent
@endsection
