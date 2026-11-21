@extends('layouts.base')
@section('body')
<div class="public">
    <header class="public-header">
        <a href="{{ url('/') }}" class="brand" style="color:inherit"><img src="{{ asset('img/vatel_logo.png') }}" alt="Vatel"><span>Cantine de Vatel<small>Réservez · Payez · Mangez</small></span></a>
        <div class="row">
            @auth
                <a class="btn sm" href="{{ auth()->user()->homeRoute() }}">Mon espace</a>
            @else
                <a class="btn ghost sm" href="{{ route('login') }}">Se connecter</a>
                <a class="btn sm" href="{{ route('register') }}">Créer un compte</a>
            @endauth
        </div>
    </header>
    @yield('content')
</div>
@endsection
