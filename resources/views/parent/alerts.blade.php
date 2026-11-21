@extends('layouts.mobile')
@section('title', __('Alertes'))
@section('tab', 'alerts')
@section('content')
<form method="POST" action="{{ route('parent.alerts.save') }}" class="card">@csrf @method('PUT')
    <b>{{ __('Seuil de solde bas par enfant') }}</b>
    @foreach ($kids as $k)<div class="row mt"><span class="grow small">{{ $k->first_name }}</span><input class="input" type="number" min="0" step="500" name="threshold[{{ $k->id }}]" value="{{ $k->low_balance_threshold }}" style="max-width:140px"></div>@endforeach
    <div class="cat-label">{{ __('Canaux') }}</div>
    <div class="check-grid">@foreach (['push' => 'Push', 'sms' => 'SMS', 'email' => 'E-mail'] as $k => $l)<label class="check"><input type="checkbox" name="notify[{{ $k }}]" value="1" {{ ($p->notify[$k] ?? false) ? 'checked' : '' }}> {{ $l }}</label>@endforeach</div>
    <button class="btn block mt">{{ __('Enregistrer') }}</button>
</form>
<div class="row between mt mb"><h3>{{ __('Alertes reçues') }}</h3><form method="POST" action="{{ route('notifications.readAll') }}">@csrf<button class="btn sm ghost">{{ __('Tout lire') }}</button></form></div>
<div class="stack">
    @forelse ($items as $n)
        <a href="{{ route('notifications.open', $n) }}" class="notif {{ $n->read_at ? '' : 'unread' }}" style="color:inherit"><div class="ico" style="{{ $n->kind === 'allergy' ? 'color:var(--red)' : '' }}">{!! ic($n->icon) !!}</div><div class="grow"><b class="small">{{ __($n->title) }}</b><div class="small muted">{{ $n->body }}</div><div class="xs muted mt">{{ $n->created_at->format('d/m · H:i') }}</div></div></a>
    @empty
        <div class="empty"><div class="ico">{!! ic('bell') !!}</div>{{ __('Aucune alerte.') }}</div>
    @endforelse
</div>
@endsection
