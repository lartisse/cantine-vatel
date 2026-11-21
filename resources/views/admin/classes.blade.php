@extends('layouts.admin')
@section('title', 'Statistiques par classe')
@section('nav', 'classes')
@section('content')
<div class="page-head"><div><h1>Statistiques par classe</h1><p>Basées sur la classe enregistrée au moment de chaque réservation.</p></div><a class="btn ghost" href="{{ route('admin.reports.export', ['onglet' => 'classes']) }}">{!! ic('download') !!} CSV</a></div>
<div class="kpis" style="grid-template-columns:repeat(3,1fr)">
    <div class="kpi"><div class="label">Classes représentées</div><div class="value">{{ $stats->count() }}</div></div>
    <div class="kpi"><div class="label">Élèves avec classe renseignée</div><div class="value">{{ $withClass }} <small style="font-size:14px">/ {{ $students }}</small></div></div>
    <div class="kpi"><div class="label">Classe la plus active</div><div class="value">{{ $stats->sortByDesc('per')->first()['class'] ?? '—' }}</div></div>
</div>
@include('admin.partials.class-table', ['stats' => $stats])
@endsection
