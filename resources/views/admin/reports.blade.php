@extends('layouts.admin')
@section('title', 'Rapports')
@section('nav', 'reports')
@section('content')
<div class="page-head">
    <div><h1>Rapports</h1><p>4 dernières semaines</p></div>
    <div class="row no-print"><button class="btn ghost" onclick="window.print()">{!! ic('printer') !!} Exporter PDF</button><a class="btn ghost" href="{{ route('admin.reports.export', ['onglet' => $tab]) }}">{!! ic('download') !!} Exporter Excel (CSV)</a></div>
</div>
<div class="report-tabs chips mb">@foreach ($tabs as $k => $l)<a class="chip {{ $tab === $k ? 'active' : '' }}" href="?onglet={{ $k }}">{{ $l }}</a>@endforeach</div>
@php
    $sumR = $daily->sum('reserved'); $sumS = $daily->sum('served'); $sumM = $daily->sum('missed'); $rev = $daily->sum('revenue');
@endphp
@if (in_array($tab, ['frequentation', 'recettes', 'gaspillage', 'satisfaction'], true))
<div class="kpis" style="grid-template-columns:repeat(3,1fr)">
    @if ($tab === 'frequentation')
        <div class="kpi"><div class="label">Repas réservés</div><div class="value">{{ num($sumR) }}</div></div>
        <div class="kpi"><div class="label">Moyenne par jour</div><div class="value">{{ $daily->count() ? round($sumR / $daily->count()) : 0 }}</div></div>
        <div class="kpi"><div class="label">Taux de retrait</div><div class="value">{{ $sumS + $sumM ? round($sumS / ($sumS + $sumM) * 100, 1) : 0 }} %</div></div>
    @elseif ($tab === 'recettes')
        <div class="kpi"><div class="label">Recettes</div><div class="value">{{ num($rev) }}</div><div class="delta muted">FCFA</div></div>
        <div class="kpi"><div class="label">Ticket moyen</div><div class="value">{{ $sumR ? num($rev / $sumR) : 0 }}</div><div class="delta muted">FCFA</div></div>
        <div class="kpi"><div class="label">Recette moyenne / jour</div><div class="value">{{ $daily->count() ? num($rev / $daily->count()) : 0 }}</div></div>
    @elseif ($tab === 'gaspillage')
        <div class="kpi"><div class="label">Repas non retirés</div><div class="value" style="color:var(--orange)">{{ $sumM }}</div></div>
        <div class="kpi"><div class="label">Taux de non-retrait</div><div class="value">{{ $sumS + $sumM ? round($sumM / ($sumS + $sumM) * 100, 1) : 0 }} %</div></div>
        <div class="kpi"><div class="label">« Pas tout mangé » (déclaré)</div><div class="value">{{ $daily->sum('ate_known') ? round($daily->sum('ate_part') / $daily->sum('ate_known') * 100) : 0 }} %</div></div>
    @else
        <div class="kpi"><div class="label">Note moyenne</div><div class="value">{{ number_format($daily->where('reviews', '>', 0)->avg('rating') ?? 0, 1, ',', '') }}/5</div></div>
        <div class="kpi"><div class="label">Avis publiés</div><div class="value">{{ $daily->sum('reviews') }}</div></div>
        <div class="kpi"><div class="label">Plats notés</div><div class="value">{{ $dishRatings->count() }}</div></div>
    @endif
</div>
<div class="card"><canvas id="ch" height="90"></canvas></div>
<div class="table-wrap card mt"><table class="tbl"><tr><th>Date</th><th class="num">Réservés</th><th class="num">Servis</th><th class="num">Non retirés</th><th class="num">Recettes</th><th class="num">Note</th></tr>
    @foreach ($daily->reverse() as $d)<tr><td>{{ $d['day'] }} {{ $d['date'] }}</td><td class="num">{{ $d['reserved'] }}</td><td class="num">{{ $d['served'] }}</td><td class="num">{{ $d['missed'] }}</td><td class="num">{{ num($d['revenue']) }}</td><td class="num">{{ $d['rating'] ?: '—' }}</td></tr>@endforeach
</table></div>
@if ($tab === 'satisfaction')
    <div class="card mt"><h3>Par plat</h3>@foreach ($dishRatings as $r)<div class="summary-line small"><span>{{ $r->name }}</span><span>{!! stars($r->avg, $r->n) !!}</span></div>@endforeach</div>
@endif
@push('scripts')
<script src="{{ asset('vendor/chart.umd.js') }}"></script>
<script>
const D = @json($daily->values()), tab = @json($tab);
const sets = { frequentation: [['Réservés', 'reserved', '#87c1e9'], ['Servis', 'served', '#1d1d1b']], recettes: [['Recettes (FCFA)', 'revenue', '#1e8e5a']], gaspillage: [['Non retirés', 'missed', '#c46a00']], satisfaction: [['Note moyenne', 'rating', '#e3a008']] }[tab];
new Chart(document.getElementById('ch'), { type: tab === 'satisfaction' ? 'line' : 'bar', data: { labels: D.map(d => d.day + ' ' + d.date), datasets: sets.map(([l, k, c]) => ({ label: l, data: D.map(d => d[k]), backgroundColor: c, borderColor: c, borderRadius: 4 })) }, options: { plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } } });
</script>
@endpush
@elseif ($tab === 'classes')
    @include('admin.partials.class-table', ['stats' => $classes])
@else
    <div class="table-wrap card"><table class="tbl"><tr><th>Ingrédient</th><th class="num">Stock</th><th class="num">Seuil</th><th class="num">Besoin semaine</th></tr>
        @foreach ($ingredients as $i)<tr><td>{{ $i->name }}</td><td class="num">{{ $i->stock + 0 }} {{ $i->unit }}</td><td class="num">{{ $i->threshold + 0 }}</td><td class="num">{{ round($needs[$i->id] ?? 0, 1) }}</td></tr>@endforeach
    </table></div>
@endif
@endsection
