@extends('layouts.mobile')
@section('title', __('Lier un parent'))
@section('back', route('profile'))
@section('tab', 'profile')
@section('content')
<div class="qr-box">
    <div data-qr="CVLINK:{{ $token->token }}" data-size="200"></div>
    <p class="small mt">{{ __('Votre parent scanne ce QR code depuis son application (Enfants › Ajouter un enfant).') }}</p>
    <div class="cat-label">{{ __('Code de secours') }}</div>
    <div class="qr-code-text">{{ $token->code }}</div>
    <p class="xs muted">{{ __('Valable jusqu’à :h, usage unique.', ['h' => $token->expires_at->format('H\hi')]) }}</p>
    <p class="xs muted mt">{{ __('La liaison n’utilise pas le matricule.') }}</p>
</div>
<div class="card mt"><b class="small">{{ __('Parent(s) lié(s)') }}</b>
    @forelse ($parents as $p)<div class="row mt"><x-avatar :user="$p" style="width:30px;height:30px;font-size:12px" /><span class="small">{{ $p->name }}</span></div>@empty<p class="small muted mt">{{ __('Aucun parent lié pour le moment.') }}</p>@endforelse
</div>
<form method="POST" action="{{ route('profile.qr.new') }}" data-confirm="{{ __('Générer un nouveau QR code ?') }}" data-confirm-text="{{ __('L’ancien QR code ne fonctionnera plus. Les parents déjà liés restent liés.') }}" data-confirm-ok="{{ __('Générer') }}">@csrf<button class="btn ghost block mt">{!! ic('refresh') !!} {{ __('Générer un nouveau QR code') }}</button></form>
@endsection
