@extends('layouts.public')
@section('title', 'Connexion')
@section('content')
<div class="auth-wrap">
    <h1>Connexion</h1>
    <p class="muted mb">Heureux de vous revoir.</p>
    @if ($errors->any())<div class="alert red mb">{!! ic('alert') !!} <span>{{ $errors->first() }}</span></div>@endif
    <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf
        <div class="field"><label for="email">E-mail ou téléphone</label><input class="input" id="email" name="email" value="{{ old('email') }}" required autocomplete="username"></div>
        <div class="field"><label for="password">Mot de passe</label><input class="input" type="password" id="password" name="password" required autocomplete="current-password"></div>
        <div class="row between mb"><label class="check" style="border:0;padding:0"><input type="checkbox" name="remember" value="1"> Rester connecté</label><a class="small" href="#" onclick="App.toast('Contactez l’administration de la cantine pour réinitialiser votre mot de passe.');return false">Mot de passe oublié</a></div>
        <button class="btn block">Se connecter</button>
    </form>
    <p class="center small mt">Pas encore de compte ? <a href="{{ route('register') }}">Créer un compte</a></p>
</div>
@endsection
