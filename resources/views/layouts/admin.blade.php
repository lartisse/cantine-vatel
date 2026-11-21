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
        ['payments', 'super.payments', 'key', 'Moyens de paiement', 0], ['app-settings', 'super.settings', 'sparkles', 'Paramètres de l’application', 0],
        ['audit', 'super.audit', 'file', 'Journal d’audit', 0],
    ];
    // Mobile / tablette : 4 raccourcis principaux en bas + « Plus » pour le reste du menu
    $primary = ['dashboard' => 'Tableau', 'orders' => 'Commandes', 'counter' => 'Comptoir', 'alerts' => 'Alertes'];
    $all = collect($links)->merge($u->isSuper() ? $sup : []);
    $others = $all->reject(fn ($l) => isset($primary[$l[0]]));
    $moreCount = $others->sum(fn ($l) => $l[4]);
    $moreActive = $nav !== '' && ! isset($primary[$nav]);
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

    {{-- Barre d'onglets en bas (≤ 860 px) --}}
    <nav class="admin-tabbar" aria-label="Navigation principale">
        @foreach ($all->filter(fn ($l) => isset($primary[$l[0]])) as [$k, $route, $icon, $label, $count])
            <a href="{{ route($route) }}" class="{{ $nav === $k ? 'active' : '' }}"><span class="ti">{!! ic($icon) !!}@if ($count)<i class="tab-badge">{{ $count }}</i>@endif</span>{{ $primary[$k] }}</a>
        @endforeach
        <button type="button" class="{{ $moreActive ? 'active' : '' }}" data-open="m-admin-more" aria-haspopup="dialog"><span class="ti">{!! ic('plus') !!}@if ($moreCount)<i class="tab-badge">{{ $moreCount }}</i>@endif</span>Plus</button>
    </nav>
</div>

@push('modals')
<div class="modal-bg sheet hidden" id="m-admin-more" role="dialog" aria-label="Menu">
    <div class="modal">
        <div class="sheet-grip"></div>
        <div class="row between mb"><div class="row"><x-avatar :user="$u" style="width:36px;height:36px;font-size:13px" /><div><b>{{ $u->name }}</b><div class="xs muted">{{ $u->position }}</div></div></div><button type="button" class="btn sm ghost" data-close aria-label="Fermer">{!! ic('x') !!}</button></div>
        <div class="cat-label" style="margin-top:0">Administration</div>
        <div class="sheet-grid">
            @foreach ($links as [$k, $route, $icon, $label, $count])
                @continue(isset($primary[$k]))
                <a href="{{ route($route) }}" class="{{ $nav === $k ? 'active' : '' }}"><span class="ti">{!! ic($icon) !!}@if ($count)<i class="tab-badge">{{ $count }}</i>@endif</span>{{ $label }}</a>
            @endforeach
        </div>
        @if ($u->isSuper())
            <div class="cat-label">Super admin</div>
            <div class="sheet-grid">
                @foreach ($sup as [$k, $route, $icon, $label, $count])
                    <a href="{{ route($route) }}" class="{{ $nav === $k ? 'active' : '' }}"><span class="ti">{!! ic($icon) !!}@if ($count)<i class="tab-badge">{{ $count }}</i>@endif</span>{{ $label }}</a>
                @endforeach
            </div>
        @endif
        <div class="btn-pair mt">
            <a class="btn ghost" href="{{ route('profile') }}">{!! ic('user') !!} Mon profil</a>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn danger block">{!! ic('logout') !!} Se déconnecter</button></form>
        </div>
    </div>
</div>
@endpush
@endsection
