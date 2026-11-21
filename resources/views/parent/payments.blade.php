@extends('layouts.mobile')
@section('title', __('Historique des paiements'))
@section('tab', 'topup')
@section('back', route('parent.home'))
@section('content')
<div class="chips mb"><a class="chip {{ ! $child ? 'active' : '' }}" href="?">{{ __('Tous') }}</a>@foreach ($kids as $k)<a class="chip {{ (int) $child === $k->id ? 'active' : '' }}" href="?enfant={{ $k->id }}">{{ $k->first_name }}</a>@endforeach</div>
<div class="card"><div class="row between"><span class="muted small">{{ __('Total rechargé') }}</span><b>{{ fcfa($list->sum('amount')) }}</b></div></div>
<div class="card mt" style="padding:4px 14px">
    @forelse ($list as $pay)
        <div class="tx"><div class="ico">{!! ic('arrow-up') !!}</div><div class="grow small"><b>{{ $pay->beneficiary?->first_name ?? __('Utilisateur supprimé') }} · {{ $pay->method?->name }}</b><div class="xs muted">{{ $pay->created_at->format('d/m/Y') }} · {{ __('Réf.') }} {{ $pay->provider_reference }}</div></div><div class="amt plus">+{{ fcfa($pay->amount) }}</div></div>
    @empty
        <p class="small muted" style="padding:10px 0">{{ __('Aucun paiement.') }}</p>
    @endforelse
</div>
@endsection
