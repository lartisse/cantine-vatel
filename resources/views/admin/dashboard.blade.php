@extends('layouts.admin')
@section('title', 'Tableau de bord')
@section('nav', 'dashboard')
@section('content')
@php
    $served = $todayRes->where('status', 'served')->count();
    $revenue = $todayRes->sum('total');
    $totalMissed = $history->sum('missed'); $totalDone = $history->sum('served') + $totalMissed;
@endphp
<div class="page-head">
    <div><h1>Tableau de bord</h1><p>{{ ucfirst(now()->locale('fr')->translatedFormat('l j F Y · H\hi')) }}</p></div>
    <div class="chips"><a class="chip {{ $days === 7 ? 'active' : '' }}" href="?jours=7">7 jours</a><a class="chip {{ $days === 30 ? 'active' : '' }}" href="?jours=30">30 jours</a></div>
</div>
@if ($risk)
    <a class="alert red mb" href="{{ route('admin.alerts') }}" style="display:flex">{!! ic('alert') !!} <span><b>{{ $risk }} réservation(s) à risque allergique à vérifier</b> aujourd’hui — voir les alertes ›</span></a>
@endif
<div class="kpis">
    <a class="kpi" href="{{ route('admin.orders') }}" style="color:inherit"><div class="label">Repas réservés aujourd’hui</div><div class="value">{{ $todayRes->count() }}</div><div class="delta muted">{{ $served }} servis · {{ $todayRes->where('status', 'paid')->count() }} à servir</div></a>
    <a class="kpi" href="{{ route('admin.reports', ['onglet' => 'recettes']) }}" style="color:inherit"><div class="label">Recettes du jour</div><div class="value">{{ num($revenue) }}</div><div class="delta muted">FCFA</div></a>
    <a class="kpi" href="{{ route('admin.reports', ['onglet' => 'gaspillage']) }}" style="color:inherit"><div class="label">Taux de retrait (dernier jour)</div><div class="value">{{ $pickupRate !== null ? $pickupRate.' %' : '—' }}</div><div class="delta" style="color:var(--green)">Objectif 95 %</div></a>
    <a class="kpi" href="{{ route('admin.reviews') }}" style="color:inherit"><div class="label">Note moyenne (30 j)</div><div class="value">{{ $avg ? number_format($avg, 1, ',', '') : '—' }}<small style="font-size:14px">/5</small></div><div class="delta">{!! stars($avg) !!}</div></a>
</div>

<div class="grid-2">
    <div class="card">
        <div class="row between"><h3>Réservés et servis par jour</h3><span class="xs muted">{{ $days }} derniers jours</span></div>
        <div class="chart-box"><canvas id="ch-days"></canvas></div>
    </div>
    <div class="card">
        <h3>{!! ic('leaf') !!} Prévision anti-gaspi</h3>
        @if ($forecast)
            <p class="xs muted">Taux d’absence observé : <b>{{ number_format($forecast['rate'] * 100, 1, ',', '') }} %</b> (4 dernières semaines)</p>
            <table class="tbl mt"><tr><th>Plat</th><th class="num">Réservés</th><th class="num">Préparez</th></tr>
                @foreach (collect($forecast['rows'])->sortByDesc('reserved')->take(7) as $row)
                    <tr><td class="small">{{ $row['dish']->name }}</td><td class="num">{{ $row['reserved'] }}</td><td class="num"><b>{{ $row['prepare'] }}</b></td></tr>
                @endforeach
            </table>
        @else<p class="muted small">Pas de menu aujourd’hui.</p>@endif
        <div class="impact mt">
            <div><b>{{ num($saved) }}</b><span class="xs">repas sauvés (30 j)</span></div>
            <div><b>{{ num($saved * 0.4) }} kg</b><span class="xs">de nourriture évités</span></div>
            <div><b>{{ num($saved * 600) }}</b><span class="xs">FCFA économisés</span></div>
        </div>
        <p class="xs muted mt">Repas sauvés = portions prévues − portions réservées (la cuisine prépare selon les réservations). 0,4 kg et 600 FCFA de coût matière par portion.</p>
    </div>
</div>

<div class="grid-3 mt">
    <div class="card"><h3>Affluence par créneau</h3>
        <div class="bars-h mt">@foreach ($slots as $s => $v)<div class="bh"><span>{{ slot_label($s) }}</span><div class="track"><div class="fill" style="width:{{ $v['cap'] ? min(100, $v['n'] / $v['cap'] * 100) : 0 }}%"></div></div><b>{{ $v['n'] }}</b></div>@endforeach</div>
        <p class="small muted mt">Déjà réservés pour {{ $tomorrow ? $tomorrow->label('l j') : 'demain' }} : <b>{{ $tomorrowCount }}</b></p>
    </div>
    <div class="card"><h3>Réservations par classe</h3>
        @php $maxC = max(1, $byClass->max() ?? 1); @endphp
        <div class="bars-h mt">@forelse ($byClass as $c => $n)<div class="bh"><span>{{ $c }}</span><div class="track"><div class="fill" style="width:{{ $n / $maxC * 100 }}%"></div></div><b>{{ $n }}</b></div>@empty<p class="small muted">—</p>@endforelse</div>
        <a class="small" href="{{ route('admin.classes') }}">Statistiques par classe ›</a>
    </div>
    <div class="card"><h3>Gaspillage</h3>
        <div class="chart-box sm"><canvas id="ch-waste"></canvas></div>
        <p class="small muted mt">{{ $totalMissed }} repas non retirés sur {{ $totalDone }} ({{ $totalDone ? round($totalMissed / $totalDone * 100, 1) : 0 }} %)</p>
    </div>
</div>

<div class="grid-3 mt">
    <div class="card"><h3>Mieux notés</h3>@foreach ($best as $id => [$r, $n])<div class="summary-line small"><span>{{ $dishNames[$id] ?? '?' }}</span><span>{!! stars($r, $n) !!}</span></div>@endforeach
        <div class="cat-label">Moins bien notés</div>@foreach ($worst as $id => [$r, $n])<div class="summary-line small"><span>{{ $dishNames[$id] ?? '?' }}</span><span>{!! stars($r, $n) !!}</span></div>@endforeach</div>
    <div class="card"><h3>{!! ic('sparkles') !!} Chef de la semaine</h3>
        @forelse ($chefs as $i => $c)<div class="summary-line small"><span>{{ $i + 1 }}. <b>{{ $c->name }}</b><br><span class="xs muted">{{ $c->creator_name ?? 'Équipe cuisine' }}</span></span><span>{!! stars($c->avg, $c->n) !!}</span></div>@empty<p class="small muted">Pas encore assez d’avis.</p>@endforelse</div>
    <div class="card"><h3>Alertes de stock</h3>
        @forelse ($lowStock as $i)<div class="summary-line small"><span>{{ $i->name }}</span><span class="badge {{ $i->stock <= 0 ? 'red' : 'orange' }}">{{ $i->stock <= 0 ? 'Rupture' : 'Bas' }} · {{ rtrim(rtrim(number_format($i->stock, 1, ',', ''), '0'), ',') }} {{ $i->unit }}</span></div>@empty<p class="small muted">Tous les stocks sont au-dessus du seuil.</p>@endforelse
        <a class="btn sm light mt" href="{{ route('admin.stocks.order') }}">{!! ic('file') !!} Générer le bon de commande</a></div>
</div>
@push('scripts')
<script src="{{ asset('vendor/chart.umd.js') }}"></script>
<script>
const H = @json($history);
Chart.defaults.maintainAspectRatio = false;
new Chart(document.getElementById('ch-days'), { type: 'bar', data: { labels: H.map(h => h.label), datasets: [{ label: 'Réservés', data: H.map(h => h.reserved), backgroundColor: '#87c1e9', borderRadius: 4 }, { label: 'Servis', data: H.map(h => h.served), backgroundColor: '#1d1d1b', borderRadius: 4 }] }, options: { plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } } });
new Chart(document.getElementById('ch-waste'), { type: 'doughnut', data: { labels: ['Servis', 'Non retirés'], datasets: [{ data: [{{ $history->sum('served') }}, {{ $totalMissed }}], backgroundColor: ['#1e8e5a', '#c46a00'] }] }, options: { plugins: { legend: { position: 'bottom' } } } });
</script>
@endpush
@endsection
