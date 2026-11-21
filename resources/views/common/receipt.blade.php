@extends('layouts.base')
@section('title', 'Reçu')
@section('body')
<div class="auth-wrap">
    <div class="card">
        <div class="row between"><div class="brand"><img src="{{ asset('img/vatel_logo.png') }}" alt="Vatel"><span>Cantine de Vatel<small>Reçu</small></span></div><span class="xs muted">N° {{ str_pad($t->id, 6, '0', STR_PAD_LEFT) }}</span></div>
        <div class="divider"></div>
        <div class="summary-line"><span>{{ __('Date') }}</span><span>{{ $t->created_at->format('d/m/Y H:i') }}</span></div>
        <div class="summary-line"><span>{{ __('Titulaire') }}</span><span>{{ $u->name }}</span></div>
        <div class="summary-line"><span>{{ __('Opération') }}</span><span>{{ $t->label }}</span></div>
        @if ($t->reference)<div class="summary-line"><span>{{ __('Référence') }}</span><span>{{ $t->reference }}</span></div>@endif
        <div class="summary-line total"><span>{{ __('Montant') }}</span><span>{{ $t->amount > 0 ? '+' : '' }}{{ fcfa($t->amount) }}</span></div>
        <div class="row mt no-print"><a class="btn ghost" href="{{ url()->previous() }}">{{ __('Retour') }}</a><button class="btn grow" onclick="window.print()">{!! ic('download') !!} {{ __('Télécharger en PDF') }}</button></div>
    </div>
</div>
@endsection
