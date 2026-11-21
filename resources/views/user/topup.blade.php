@extends('layouts.mobile')
@section('title', __('Recharger'))
@section('back', route('wallet'))
@section('no_tabs', 1)
@section('content')
<div class="card mb"><div class="row between"><span class="muted small">{{ __('Solde actuel') }}</span><b>{{ fcfa($u->balance) }}</b></div></div>
@include('partials.topup-form', ['action' => route('wallet.topup'), 'amounts' => [1000, 2500, 5000, 10000], 'phone' => $u->phone])
@endsection
