@extends('layouts.base')
@section('title', 'Bon de commande')
@section('body')
<div style="max-width:800px;margin:0 auto;padding:24px;background:#fff">
    <div class="row between"><div class="brand"><img src="{{ asset('img/vatel_logo.png') }}" alt="Vatel"><span>Cantine de Vatel<small>Bon de commande</small></span></div><button class="btn sm no-print" onclick="window.print()">{!! ic('printer') !!} Imprimer / PDF</button></div>
    <p class="mt small muted">Établi le {{ now()->locale('fr')->translatedFormat('l j F Y') }} · semaine du {{ today()->startOfWeek()->format('d/m') }}</p>
    <table class="tbl mt"><tr><th>Ingrédient</th><th class="num">Stock</th><th class="num">Besoin semaine</th><th class="num">Seuil</th><th class="num">Quantité à commander</th></tr>
        @forelse ($lines as $l)<tr><td>{{ $l['ing']->name }}</td><td class="num">{{ $l['ing']->stock + 0 }} {{ $l['ing']->unit }}</td><td class="num">{{ round($l['need'], 1) }}</td><td class="num">{{ $l['ing']->threshold + 0 }}</td><td class="num"><b>{{ $l['qty'] }} {{ $l['ing']->unit }}</b></td></tr>
        @empty<tr><td colspan="5" class="center muted">Aucun achat nécessaire.</td></tr>@endforelse
    </table>
    <p class="xs muted mt">Quantité = besoin de la semaine + seuil d’alerte − stock actuel.</p>
</div>
@endsection
