@extends('layouts.public')
@section('title', 'Confirmez votre identité')
@section('content')
<div class="auth-wrap">
    <div class="card">
        <h2>{!! ic('lock') !!} Confirmez votre identité</h2>
        <p class="small muted mt mb">Cette action est sensible (moyens de paiement, suppression définitive). Ressaisissez votre mot de passe. La confirmation reste valable 5 minutes.</p>
        <form method="POST" action="{{ route('password.confirm') }}">
            @csrf
            <div class="field"><label>Mot de passe</label><input class="input" type="password" name="password" required autofocus>@error('password')<span class="error">{{ $message }}</span>@enderror</div>
            <div class="row"><a class="btn ghost" href="{{ url()->previous() }}">Annuler</a><button class="btn grow">Confirmer</button></div>
        </form>
    </div>
</div>
@endsection
