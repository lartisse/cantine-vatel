@extends('layouts.admin')
@section('title', 'Commandes du jour')
@section('nav', 'orders')
@section('content')
@php $jour = $menu?->date->toDateString() ?? today()->toDateString(); @endphp
<div class="page-head">
    <div><h1>Commandes {{ $menu && $menu->date->isToday() ? 'du jour' : '' }}</h1><p>{{ $menu ? $menu->label() : 'Aucun menu ce jour' }}</p></div>
    <div class="row wrap no-print">
        <form method="GET"><select class="input" name="jour" onchange="this.form.submit()" style="padding:8px">@foreach ($days as $d)<option value="{{ $d->date->toDateString() }}" {{ $d->date->toDateString() === $jour ? 'selected' : '' }}>{{ $d->label('D j M') }}{{ $d->date->isToday() ? ' (aujourd’hui)' : '' }}</option>@endforeach</select></form>
        @if ($menu)
            <a class="btn sm ghost" href="{{ route('admin.orders.production', ['jour' => $jour]) }}" target="_blank">{!! ic('printer') !!} Fiche de production</a>
            <form method="POST" action="{{ route('admin.orders.antigaspi', ['jour' => $jour]) }}" data-confirm="Mettre en vente les portions restantes à -50 % ?" data-confirm-text="Les convives verront le panier anti-gaspi sur leur accueil.">@csrf<button class="btn sm light">{!! ic('leaf') !!} Panier anti-gaspi</button></form>
            @unless ($menu->is_closed)<form method="POST" action="{{ route('admin.orders.close', ['jour' => $jour]) }}" data-confirm="Clôturer la journée ?" data-confirm-text="Les repas non retirés passeront en « Non retiré ».">@csrf<button class="btn sm">{!! ic('lock') !!} Clôturer la journée</button></form>@endunless
        @endif
    </div>
</div>
@if ($menu)
<div class="kpis">
    <div class="kpi"><div class="label">Réservés</div><div class="value">{{ $counts['reserved'] }}</div></div>
    <div class="kpi"><div class="label">Servis</div><div class="value" style="color:var(--green)">{{ $counts['served'] }}</div></div>
    <div class="kpi"><div class="label">Restant à servir</div><div class="value">{{ $counts['toServe'] }}</div></div>
    <a class="kpi" href="?jour={{ $jour }}&statut=allergie" style="color:inherit"><div class="label">Plats avec allergène déclaré</div><div class="value" style="color:var(--red)">{{ $counts['allergy'] }}</div></a>
</div>
<div class="report-tabs chips mb"><a class="chip {{ $tab === 'jour' ? 'active' : '' }}" href="?jour={{ $jour }}&onglet=jour">Jour : par créneau</a><a class="chip {{ $tab === 'semaine' ? 'active' : '' }}" href="?jour={{ $jour }}&onglet=semaine">Semaine : par jour</a></div>
@if ($tab === 'semaine')
    <div class="table-wrap card"><table class="tbl"><tr><th>Plat</th>@foreach ($week as $m)<th class="num">{{ $m->label('D d/m') }}</th>@endforeach</tr>
        @foreach ($weekGrid as $row)<tr><td>{{ $row['dish']->name }}</td>@foreach ($week as $m)@php $c = $row['days'][$m->id] ?? null; @endphp<td class="num">@if ($c)<b style="{{ $c[0] > $c[1] ? 'color:var(--red)' : '' }}">{{ $c[0] }}</b> <span class="xs muted">/ {{ $c[1] }}</span>@else — @endif</td>@endforeach</tr>@endforeach
    </table><p class="xs muted mt">Réservés / portions prévues. En rouge : prévision dépassée.</p></div>
@else
    <div class="grid-2">
    <div class="table-wrap card"><table class="tbl"><tr><th>Plat</th>@foreach ($slots as $s)<th class="num">{{ slot_label($s) }}</th>@endforeach<th class="num">Total</th><th class="num">Prévu</th></tr>
        @foreach ($menu->items as $it)@php $tot = array_sum($grid[$it->dish_id] ?? []); @endphp
            <tr><td>{{ $it->dish->name }}</td>@foreach ($slots as $s)<td class="num">{{ $grid[$it->dish_id][$s] ?? 0 }}</td>@endforeach<td class="num"><b>{{ $tot }}</b></td><td class="num" style="{{ $tot > $it->planned_portions ? 'color:var(--red);font-weight:700' : '' }}">{{ $it->planned_portions }}</td></tr>
        @endforeach
    </table></div>
    <div class="card"><h3>{!! ic('leaf') !!} Prévision</h3><p class="xs muted">Absence : {{ number_format($forecast['rate'] * 100, 1, ',', '') }} %</p>
        @foreach ($forecast['rows'] as $row)<div class="summary-line small"><span>{{ $row['dish']->name }}</span><span>{{ $row['reserved'] }} → <b>{{ $row['prepare'] }}</b></span></div>@endforeach
    </div>
    </div>
@endif

<div class="card mt">
    <form class="toolbar row wrap mb no-print" method="GET"><input type="hidden" name="jour" value="{{ $jour }}">
        <input class="input" name="q" value="{{ request('q') }}" placeholder="Nom ou code" style="max-width:220px">
        <select class="input" name="creneau" style="max-width:150px"><option value="">Tous créneaux</option>@foreach ($slots as $s)<option value="{{ $s }}" {{ request('creneau') === $s ? 'selected' : '' }}>{{ slot_label($s) }}</option>@endforeach</select>
        <select class="input" name="statut" style="max-width:190px"><option value="">Tous statuts</option>@foreach (['paid' => 'Payée', 'served' => 'Servie', 'missed' => 'Non retirée', 'allergie' => '⚠ Allergène réservé'] as $k => $l)<option value="{{ $k }}" {{ request('statut') === $k ? 'selected' : '' }}>{{ $l }}</option>@endforeach</select>
        <button class="btn sm">{!! ic('search') !!} Filtrer</button>
        <a class="btn sm ghost" href="{{ route('admin.orders.export', request()->query() + ['jour' => $jour]) }}">{!! ic('download') !!} CSV</a>
        <button type="button" class="btn sm ghost" onclick="window.print()">{!! ic('printer') !!} Imprimer</button>
    </form>
    <div class="table-wrap"><table class="tbl">
        <tr><th>Convive</th><th>Classe / poste</th><th>Plateau</th><th>Créneau</th><th>Code</th><th>Statut</th><th class="no-print"></th></tr>
        @forelse ($list as $r)
            <tr>
                <td><div class="row"><x-avatar :user="$r->user" style="width:28px;height:28px;font-size:11px" /><b class="small">{{ $r->user?->name ?? 'Utilisateur supprimé' }}</b></div></td>
                <td class="small">{{ $r->class_snapshot ?? $r->user?->groupLabel() }}</td>
                <td class="small">{{ $r->items->map(fn ($i) => ($i->quantity > 1 ? $i->quantity.'× ' : '').$i->dish->name)->join(', ') }}
                    @if ($r->allergy_conflict)<br>@foreach ($r->allergy_conflict as $c)<span class="badge red xs">{!! ic('alert') !!} {{ $c['name'] }} : {{ implode(', ', array_map('allergen_label', $c['allergens'])) }}</span>@endforeach @if ($r->checker)<span class="badge green xs">vérifié par {{ $r->checker->first_name }}</span>@endif @endif</td>
                <td>{{ $r->slotLabel() }}</td><td><code>{{ $r->code }}</code></td>
                <td><span class="badge {{ $r->statusBadge() }}">{{ $r->statusLabel() }}</span>@if ($r->served_at)<div class="xs muted">{{ $r->served_at->format('H\hi') }}</div>@endif</td>
                <td class="no-print">@if ($r->status === 'paid')<form method="POST" action="{{ route('admin.orders.serve', $r) }}">@csrf<button class="btn sm light">Marquer servi</button></form>@endif</td>
            </tr>
        @empty
            <tr><td colspan="7" class="muted center">Aucune commande pour ces filtres.</td></tr>
        @endforelse
    </table></div>
    {{ $list->links('partials.pager') }}
</div>
@else
    <div class="empty"><div class="ico">{!! ic('calendar') !!}</div>Aucun menu pour ce jour.</div>
@endif
@endsection
