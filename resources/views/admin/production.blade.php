@extends('layouts.base')
@section('title', 'Fiche de production')
@section('body')
<div style="max-width:800px;margin:0 auto;padding:24px;background:#fff">
    <div class="row between"><div class="brand"><img src="{{ asset('img/vatel_logo.png') }}" alt="Vatel"><span>Cantine de Vatel<small>Fiche de production</small></span></div><button class="btn sm no-print" onclick="window.print()">{!! ic('printer') !!} Imprimer</button></div>
    <h2 class="mt">{{ $menu->label() }}</h2>
    <p class="small muted">Taux d’absence pris en compte : {{ number_format($forecast['rate'] * 100, 1, ',', '') }} %</p>
    <table class="tbl mt"><tr><th>Plat</th><th class="num">Prévu</th><th class="num">Réservé</th><th class="num">À préparer</th></tr>
        @foreach ($forecast['rows'] as $row)<tr><td>{{ $row['dish']->name }}</td><td class="num">{{ $row['planned'] }}</td><td class="num">{{ $row['reserved'] }}</td><td class="num"><b>{{ $row['prepare'] }}</b></td></tr>@endforeach
    </table>
    @if ($allergy->count())
        <h3 class="mt">⚠ Plateaux à risque allergique</h3>
        <table class="tbl mt"><tr><th>Convive</th><th>Créneau</th><th>Plat / allergène</th></tr>
            @foreach ($allergy as $r)<tr><td>{{ $r->user?->name }}</td><td>{{ $r->slotLabel() }}</td><td>@foreach ($r->allergy_conflict as $c){{ $c['name'] }} ({{ implode(', ', array_map('allergen_label', $c['allergens'])) }}) @endforeach</td></tr>@endforeach
        </table>
    @endif
</div>
@endsection
