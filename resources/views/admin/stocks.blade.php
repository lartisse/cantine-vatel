@extends('layouts.admin')
@section('title', 'Stocks')
@section('nav', 'stocks')
@section('content')
@php $fmt = fn ($n) => rtrim(rtrim(number_format($n, 2, ',', ' '), '0'), ','); @endphp
<div class="page-head">
    <div><h1>Stocks</h1><p>Besoins de la semaine calculés à partir des menus (portions prévues) et des recettes.</p></div>
    <div class="row"><button class="btn ghost" data-open="m-ing">{!! ic('plus') !!} Ingrédient</button><a class="btn" href="{{ route('admin.stocks.order') }}" target="_blank">{!! ic('file') !!} Générer le bon de commande</a></div>
</div>
<div class="grid-2">
    <div class="table-wrap card"><table class="tbl">
        <tr><th>Ingrédient</th><th class="num">Stock</th><th class="num">Seuil</th><th class="num">Besoin semaine</th><th>Niveau</th><th>Statut</th><th></th></tr>
        @foreach ($ingredients as $i)
            @php $need = $needs[$i->id] ?? 0; $status = $i->stock <= 0 ? ['red', 'Rupture'] : ($i->stock < $i->threshold ? ['orange', 'Bas'] : ['green', 'OK']); $pct = min(100, $i->threshold > 0 ? $i->stock / ($i->threshold * 2) * 100 : 100); @endphp
            <tr><td><b>{{ $i->name }}</b></td><td class="num">{{ $fmt($i->stock) }} {{ $i->unit }}</td><td class="num">{{ $fmt($i->threshold) }}</td><td class="num" style="{{ $need > $i->stock ? 'color:var(--red);font-weight:700' : '' }}">{{ $fmt($need) }}</td>
                <td style="min-width:90px"><div class="stock-bar"><span style="width:{{ $pct }}%;background:var(--{{ $status[0] }})"></span></div></td>
                <td><span class="badge {{ $status[0] }}">{{ $status[1] }}</span></td>
                <td><button class="btn sm ghost" data-open="m-mv{{ $i->id }}">Mouvement</button></td></tr>
            @push('modals')
            <div class="modal-bg hidden" id="m-mv{{ $i->id }}"><div class="modal"><h2>{{ $i->name }}</h2><p class="small muted mb">Stock actuel : {{ $fmt($i->stock) }} {{ $i->unit }}</p>
                <form method="POST" action="{{ route('admin.stocks.move', $i) }}">@csrf
                    <div class="field"><label>Type</label><select class="input" name="type"><option value="entree">Entrée de stock</option><option value="perte">Déclarer une perte</option></select></div>
                    <div class="field"><label>Quantité ({{ $i->unit }})</label><input class="input" type="number" step="0.01" min="0.01" name="quantity" required></div>
                    <div class="field"><label>Motif (obligatoire pour une perte)</label><input class="input" name="reason"></div>
                    <div class="actions"><button type="button" class="btn ghost" data-close>Annuler</button><button class="btn">Enregistrer</button></div>
                </form></div></div>
            @endpush
        @endforeach
    </table></div>
    <div>
        <div class="card"><h3>À commander</h3>
            @php $low = $ingredients->filter(fn ($i) => $i->stock < $i->threshold || ($needs[$i->id] ?? 0) > $i->stock); @endphp
            @forelse ($low as $i)<div class="summary-line small"><span>{{ $i->name }}</span><b>{{ $fmt(max(0, ($needs[$i->id] ?? 0) + $i->threshold - $i->stock)) }} {{ $i->unit }}</b></div>@empty<p class="small muted">Rien à commander.</p>@endforelse
        </div>
        <div class="card mt"><h3>Derniers mouvements</h3>
            @foreach ($moves as $m)<div class="summary-line small"><span>{{ $m->created_at->format('d/m') }} · {{ $m->ingredient->name }}<br><span class="xs muted">{{ ['entree' => 'Entrée', 'sortie' => 'Sortie', 'perte' => 'Perte'][$m->type] ?? $m->type }}{{ $m->reason ? ' · '.$m->reason : '' }} · {{ $m->author }}</span></span><b style="color:{{ $m->type === 'entree' ? 'var(--green)' : 'var(--red)' }}">{{ $m->type === 'entree' ? '+' : '−' }}{{ $fmt($m->quantity) }} {{ $m->ingredient->unit }}</b></div>@endforeach
        </div>
    </div>
</div>
@push('modals')
<div class="modal-bg hidden" id="m-ing"><div class="modal"><h2>Nouvel ingrédient</h2>
    <form method="POST" action="{{ route('admin.stocks.store') }}">@csrf
        <div class="field"><label>Nom</label><input class="input" name="name" required></div>
        <div class="grid-3"><div class="field"><label>Unité</label><input class="input" name="unit" value="kg" required></div><div class="field"><label>Stock</label><input class="input" type="number" step="0.01" name="stock" value="0" required></div><div class="field"><label>Seuil</label><input class="input" type="number" step="0.01" name="threshold" value="0" required></div></div>
        <div class="actions"><button type="button" class="btn ghost" data-close>Annuler</button><button class="btn">Ajouter</button></div>
    </form></div></div>
@endpush
@endsection
