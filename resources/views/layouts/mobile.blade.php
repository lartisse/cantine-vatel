@extends('layouts.base')
@section('body')
@php
    $u = auth()->user();
    $tab = trim($__env->yieldContent('tab'));
    $tabs = $u->role === 'parent'
        ? [['home', route('parent.home'), 'home', __('Accueil')], ['child', route('parent.children'), 'users', __('Enfants')], ['topup', route('parent.topup.choose'), 'card', __('Recharger')], ['alerts', route('parent.alerts'), 'bell', __('Alertes')], ['profile', route('profile'), 'user', __('Profil')]]
        : [['home', route('user.home'), 'home', __('Accueil')], ['menu', route('menu'), 'calendar', __('Menu')], ['resa', route('reservations.index'), 'ticket', __('Réservations')], ['wallet', route('wallet'), 'card', __('Portefeuille')], ['profile', route('profile'), 'user', __('Profil')]];
@endphp
<div class="mobile-stage"><div class="phone">
    <div class="phone-top">
        @hasSection('back')<a class="back" href="@yield('back')" aria-label="{{ __('Retour') }}">{!! ic('chevron-left') !!}</a>@endif
        <h2>@yield('title')</h2>
        @yield('right')
    </div>
    <div class="phone-body">
        @if ($errors->any() && ! View::hasSection('no_errors'))
            <div class="alert red mb">{!! ic('alert') !!} <span>{{ $errors->first() }}</span></div>
        @endif
        @yield('content')
    </div>
    @unless (View::hasSection('no_tabs'))
    <nav class="tabbar">
        @foreach ($tabs as [$k, $href, $icon, $label])
            <a href="{{ $href }}" class="{{ $tab === $k ? 'active' : '' }}"><span class="ti">{!! ic($icon) !!}@if ($k === 'profile' && $u->missingFields())<i class="tab-dot" title="{{ __('Profil incomplet') }}"></i>@endif</span>{{ $label }}</a>
        @endforeach
    </nav>
    @endunless
</div></div>
@endsection
