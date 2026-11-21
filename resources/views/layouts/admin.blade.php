@extends('layouts.base')
@section('body')
@php
    $u = auth()->user();
    $nav = trim($__env->yieldContent('nav'));
    $low = \App\Models\Ingredient::whereColumn('stock', '<', 'threshold')->count();
    $flagged = \App\Models\Review::where('status', 'flagged')->count();
    $alerts = $u->appNotifications()->whereNull('read_at')->count();
    $pending = $u->isSuper() ? \App\Models\DeletionRequest::where('status', 'pending')->count() : 0;
    $links = [
        ['dashboard', 'admin.dashboard', 'chart', 'Tableau de bord', 0], ['alerts', 'admin.alerts', 'bell', 'Alertes', $alerts],
        ['menus', 'admin.menus', 'calendar', 'Menus', 0], ['orders', 'admin.orders', 'scan', 'Commandes du jour', 0],
        ['counter', 'admin.counter', 'qr', 'Comptoir', 0], ['stocks', 'admin.stocks', 'box', 'Stocks', $low],
        ['dishes', 'admin.dishes', 'utensils', 'Plats', 0], ['reviews', 'admin.reviews', 'message', 'Avis', $flagged],
        ['users', 'admin.users', 'users', 'Utilisateurs', 0], ['classes', 'admin.classes', 'graduation', 'Par classe', 0],
        ['reports', 'admin.reports', 'trending', 'Rapports', 0], ['settings', 'admin.settings', 'wrench', 'Paramètres', 0],
    ];
    $sup = [
        ['admins', 'super.admins', 'shield', 'Administrateurs', 0], ['accounts', 'super.accounts', 'users', 'Comptes', 0],
        ['trash', 'super.trash', 'trash', 'Corbeille', 0], ['requests', 'super.requests', 'mail', 'Demandes de suppression', $pending],
        ['payments', 'super.payments', 'key', 'Moyens de paiement', 0], ['audit', 'super.audit', 'file', 'Journal d’audit', 0],
    ];
@endphp
<div class="admin">
    <aside class="sidebar">
        <div class="brand"><img src="{{ asset('img/vatel_logo.png') }}" alt="Vatel"><span>Cantine de Vatel<small>{{ $u->isSuper() ? 'Super administration' : 'Administration' }}</small></span></div>
        <nav>
            @foreach ($links as [$k, $route, $icon, $label, $count])
                <a href="{{ route($route) }}" class="{{ $nav === $k ? 'active' : '' }}"><span>{!! ic($icon) !!}</span>{{ $label }}@if ($count)<span class="count">{{ $count }}</span>@endif</a>
            @endforeach
            @if ($u->isSuper())
                <div class="nav-sep">Super admin</div>
                @foreach ($sup as [$k, $route, $icon, $label, $count])
                    <a href="{{ route($route) }}" class="{{ $nav === $k ? 'active' : '' }}"><span>{!! ic($icon) !!}</span>{{ $label }}@if ($count)<span class="count">{{ $count }}</span>@endif</a>
                @endforeach
            @endif
        </nav>
        <div class="me">
            <x-avatar :user="$u" style="width:34px;height:34px;font-size:13px" />
            <div class="grow"><b>{{ $u->name }}</b><div class="xs" style="color:#aaa">{{ $u->position }}</div></div>
            <a class="btn sm ghost" style="color:#fff;border-color:#444" href="{{ route('profile') }}" title="Profil">{!! ic('user') !!}</a>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn sm ghost" style="color:#fff;border-color:#444" title="Se déconnecter">{!! ic('logout') !!}</button></form>
        </div>
    </aside>
    <main class="main">
        @if ($errors->any())
            <div class="alert red mb">{!! ic('alert') !!} <span>{{ $errors->first() }}</span></div>
        @endif
        @yield('content')
    </main>
</div>
@endsection
