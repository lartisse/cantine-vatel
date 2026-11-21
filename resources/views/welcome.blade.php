@extends('layouts.public')
@section('title', 'Accueil')
@section('content')
<section class="hero">
    <div>
        <span class="badge">Cantine scolaire &amp; d’entreprise</span>
        <h1 style="margin-top:12px">Réservez, payez,<br><em>mangez sans attendre.</em></h1>
        <p class="lead">Consultez le menu de la semaine, réservez votre repas depuis votre téléphone, payez avec Wave ou Orange Money et récupérez votre plateau en scannant un QR code.</p>
        <div class="row wrap">
            <a class="btn" href="{{ route('register') }}">Créer un compte</a>
            <a class="btn ghost" href="{{ route('login') }}">Se connecter</a>
            <a class="btn light" href="{{ route('menu.public') }}">Voir le menu de la semaine</a>
        </div>
    </div>
    <div class="hero-visual">
        <div class="row between mb"><b>{{ $menu ? 'Menu du '.mb_strtolower($menu->label()) : 'Menu à venir' }}</b>@if ($menu?->date->isToday())<span class="badge" style="background:var(--sky);color:var(--ink)">Aujourd’hui</span>@endif</div>
        <div class="today">
            @forelse ($items as $d)
                <div class="item"><x-dish-img :dish="$d" size="sm" /><div class="grow"><b>{{ $d->name }}</b><div class="xs" style="color:#aaa">{{ $d->categoryLabel() }} · {!! stars($d->avgRating()) !!}</div></div><b>{{ fcfa($d->price) }}</b></div>
            @empty
                <p class="small" style="color:#aaa">Le menu n’est pas encore publié.</p>
            @endforelse
        </div>
        <p class="xs mt" style="color:#aaa">Réservations ouvertes jusqu’à {{ slot_label($cutoff) }} le jour même.</p>
    </div>
</section>
<section class="benefits">
    <div class="benefit"><div class="ico">{!! ic('clock') !!}</div><h3>Moins d’attente</h3><p class="muted">Créneaux de passage et QR code : on présente son téléphone, on est servi.</p></div>
    <div class="benefit"><div class="ico">{!! ic('leaf') !!}</div><h3>Moins de gaspillage</h3><p class="muted">La cuisine connaît le nombre exact de repas à préparer chaque jour.</p></div>
    <div class="benefit"><div class="ico">{!! ic('alert') !!}</div><h3>Allergies prises en compte</h3><p class="muted">Les plats incompatibles avec votre profil sont signalés automatiquement.</p></div>
</section>
@endsection
