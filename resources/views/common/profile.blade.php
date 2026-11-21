@extends('layouts.mobile')
@section('title', __('Mon profil'))
@section('tab', 'profile')
@section('content')
@php $missing = $u->missingFields(); @endphp
<div class="photo-block center">
    <x-avatar :user="$u" size="xl" style="margin:0 auto" />
    <div class="photo-actions mt" style="justify-content:center">
        <x-image-upload :action="route('profile.photo')" shape="circle" id="avatar" />
        @if ($u->avatar_path)
            <form method="POST" action="{{ route('profile.photo.delete') }}" class="inline" data-confirm="{{ __('Supprimer la photo ?') }}" data-confirm-text="{{ __('Vos initiales seront affichées à la place.') }}" data-confirm-ok="{{ __('Supprimer la photo') }}" data-danger>@csrf @method('DELETE')<button class="btn sm danger">{!! ic('trash') !!} {{ __('Supprimer') }}</button></form>
        @endif
    </div>
    <p class="xs muted mt">JPG, PNG {{ __('ou') }} WebP · 5 Mo max</p>
    <h2 class="mt">{{ $u->name }}</h2>
    <span class="badge">{{ __($u->typeLabel()) }}</span>
    @if ($missing)<div class="incomplete mt">{{ __('Profil incomplet :') }} {{ implode(', ', array_map(fn ($f) => __($f), $missing)) }}</div>@endif
</div>

<div class="card mt info-list">
    <b>{{ __('Mes informations') }}</b>
    <div class="li"><span>{{ __('E-mail') }}</span><span>{{ $u->email }}</span></div>
    <div class="li"><span>{{ __('Téléphone') }}</span><span>{{ $u->phone ?: '—' }}</span></div>
    @if ($u->account_type === 'eleve')
        <div class="li"><span>{{ __('Classe') }}</span><span>{{ $u->class_name ?: '—' }}</span></div>
        <div class="li"><span>{{ __('Matricule') }}</span><span>{{ $u->matricule ?: '—' }}</span></div>
    @elseif ($u->account_type === 'personnel')
        <div class="li"><span>{{ __('Poste') }}</span><span>{{ $u->position ?: '—' }}</span></div>
    @endif
</div>

<form method="POST" action="{{ route('profile.update') }}" class="card mt">
    @csrf @method('PUT')
    <b>{{ __('Modifier') }}</b>
    <div class="field mt"><label>{{ __('Prénom') }} *</label><input class="input" name="first_name" value="{{ old('first_name', $u->first_name) }}" required></div>
    <div class="field"><label>{{ __('Nom') }} *</label><input class="input" name="last_name" value="{{ old('last_name', $u->last_name) }}" required></div>
    <div class="field"><label>{{ __('Téléphone') }}</label><input class="input" name="phone" value="{{ old('phone', $u->phone) }}"></div>
    @if ($u->account_type === 'eleve')
        <div class="field"><label>{{ __('Classe') }}</label><select class="input" name="class_name"><option value="">—</option>@foreach (config('cantine.classes') as $c)<option {{ $u->class_name === $c ? 'selected' : '' }}>{{ $c }}</option>@endforeach</select></div>
        <div class="field"><label>{{ __('Matricule') }}</label><input class="input" name="matricule" value="{{ old('matricule', $u->matricule) }}"></div>
    @elseif ($u->account_type === 'personnel')
        <div class="field"><label>{{ __('Poste') }}</label><input class="input" name="position" value="{{ old('position', $u->position) }}"></div>
    @endif
    @if ($u->role === 'user')
        <div class="cat-label">{{ __('Régimes') }}</div>
        <div class="check-grid">@foreach (config('cantine.diets') as $k => $l)<label class="check"><input type="checkbox" name="diets[]" value="{{ $k }}" {{ in_array($k, $u->diets ?? [], true) ? 'checked' : '' }}> {{ __($l) }}</label>@endforeach</div>
        <div class="cat-label">{{ __('Allergies') }}</div>
        <div class="check-grid">@foreach (config('cantine.allergens') as $k => $l)<label class="check"><input type="checkbox" name="allergies[]" value="{{ $k }}" {{ in_array($k, $u->allergies ?? [], true) ? 'checked' : '' }}> {{ __($l) }}</label>@endforeach</div>
        <div class="field mt"><label>{{ __('Seuil d’alerte de solde (FCFA)') }}</label><input class="input" type="number" min="0" name="low_balance_threshold" value="{{ $u->low_balance_threshold }}"></div>
    @endif
    <div class="cat-label">{{ __('Préférences') }}</div>
    <div class="field"><label>{{ __('Langue de l’interface') }}</label><select class="input" name="locale"><option value="fr" {{ $u->locale === 'fr' ? 'selected' : '' }}>Français</option><option value="en" {{ $u->locale === 'en' ? 'selected' : '' }}>English</option></select></div>
    <div class="check-grid">@foreach (['push' => 'Push', 'sms' => 'SMS', 'email' => 'E-mail'] as $k => $l)<label class="check"><input type="checkbox" name="notify[{{ $k }}]" value="1" {{ ($u->notify[$k] ?? false) ? 'checked' : '' }}> {{ $l }}</label>@endforeach</div>
    <button class="btn block mt">{{ __('Enregistrer') }}</button>
</form>

@if ($u->isStudent())
    <a class="card mt row" href="{{ route('profile.qr') }}" style="color:inherit">{!! ic('qr') !!}<span class="grow"><b>{{ __('Lier un parent') }}</b><br><span class="small muted">{{ __('QR code à faire scanner par votre parent') }}</span></span>{!! ic('chevron-right') !!}</a>
@endif

<div class="btn-pair mt">
    <button class="btn ghost" data-open="m-pwd">{!! ic('lock') !!} {{ __('Changer le mot de passe') }}</button>
    <form method="POST" action="{{ route('logout') }}" style="flex:1">@csrf<button class="btn ghost block">{!! ic('logout') !!} {{ __('Se déconnecter') }}</button></form>
</div>

@if ($u->role === 'user')
<a class="card flat mt row" href="{{ route('profile.deletion') }}" style="color:var(--red)">{!! ic('trash') !!}<span class="grow small"><b>{{ __('Suppression du compte') }}</b>@if ($pending && $pending->status === 'pending')<br><span class="xs">{{ __('Demande en attente') }}</span>@elseif ($pending && $pending->status === 'refused')<br><span class="xs">{{ __('Demande refusée') }}</span>@endif</span>{!! ic('chevron-right') !!}</a>
@endif

@push('modals')
<div class="modal-bg {{ $errors->has('current_password') || $errors->has('password') ? '' : 'hidden' }}" id="m-pwd"><div class="modal">
    <h2>{{ __('Changer le mot de passe') }}</h2>
    <form method="POST" action="{{ route('profile.password') }}">@csrf @method('PUT')
        <div class="field"><label>{{ __('Mot de passe actuel') }}</label><input class="input" type="password" name="current_password" required>@error('current_password')<span class="error">{{ $message }}</span>@enderror</div>
        <div class="field"><label>{{ __('Nouveau mot de passe') }}</label><input class="input" type="password" name="password" minlength="8" required>@error('password')<span class="error">{{ $message }}</span>@enderror</div>
        <div class="field"><label>{{ __('Confirmation') }}</label><input class="input" type="password" name="password_confirmation" required></div>
        <div class="actions"><button type="button" class="btn ghost" data-close>{{ __('Annuler') }}</button><button class="btn">{{ __('Enregistrer') }}</button></div>
    </form>
</div></div>
@endpush
@endsection
