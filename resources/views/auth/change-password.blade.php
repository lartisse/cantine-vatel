@extends('layouts.public')
@section('title', 'Nouveau mot de passe')
@section('content')
<div class="auth-wrap">
    <div class="card">
        <h2>{!! ic('lock') !!} Choisissez votre mot de passe</h2>
        @if ($forced)
            <p class="small muted mt mb">Votre compte a été créé (ou réinitialisé) avec un mot de passe provisoire. Remplacez-le par un mot de passe personnel pour continuer.</p>
        @endif
        @if ($errors->any())<div class="alert red mb">{!! ic('alert') !!} <span>{{ $errors->first() }}</span></div>@endif
        <form method="POST" action="{{ route('password.change.update') }}">
            @csrf @method('PUT')
            <div class="field"><label>Mot de passe provisoire</label><input class="input" type="password" name="current_password" required autocomplete="current-password"></div>
            <div class="field"><label>Nouveau mot de passe <span class="muted xs">(10 caractères minimum, lettres et chiffres)</span></label><input class="input" type="password" name="password" minlength="10" required autocomplete="new-password"></div>
            <div class="field"><label>Confirmation</label><input class="input" type="password" name="password_confirmation" required autocomplete="new-password"></div>
            <button class="btn block">Enregistrer et continuer</button>
        </form>
        <form method="POST" action="{{ route('logout') }}" class="mt center">@csrf<button class="btn ghost sm">Se déconnecter</button></form>
    </div>
</div>
@endsection
