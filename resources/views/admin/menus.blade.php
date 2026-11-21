@extends('layouts.admin')
@section('title', 'Menus')
@section('nav', 'menus')
@section('content')
@php
    $published = $menus->every(fn ($m) => $m->is_published || $m->items->isEmpty()) && $menus->contains(fn ($m) => $m->is_published);
    $cats = config('cantine.categories');
@endphp
<div class="page-head">
    <div><h1>Planification des menus</h1><p>Glissez un plat du catalogue dans une case, ou utilisez « + Ajouter ».</p></div>
    <div class="row wrap">
        <a class="btn sm ghost" href="?semaine={{ $offset - 1 }}">{!! ic('chevron-left') !!}</a>
        <b>Semaine du {{ $monday->locale('fr')->translatedFormat('j M') }}</b>
        <a class="btn sm ghost" href="?semaine={{ $offset + 1 }}">{!! ic('chevron-right') !!}</a>
        <span class="badge {{ $published ? 'green' : 'orange' }}">{{ $published ? 'Publiée' : 'Brouillon' }}</span>
        <form method="POST" action="{{ route('admin.menus.duplicate') }}">@csrf<input type="hidden" name="semaine" value="{{ $offset }}"><button class="btn sm ghost">{!! ic('copy') !!} Dupliquer la semaine précédente</button></form>
        <form method="POST" action="{{ route('admin.menus.publish') }}" data-confirm="Publier le menu ?" data-confirm-text="Les convives et les parents seront notifiés.">@csrf<input type="hidden" name="semaine" value="{{ $offset }}"><button class="btn sm">{!! ic('send') !!} Publier le menu</button></form>
    </div>
</div>
<p class="small muted mb">{!! ic('clock') !!} Heure limite de réservation : <b>{{ slot_label(\App\Support\Settings::get('cutoff', '10:00')) }}</b> le jour même (<a href="{{ route('admin.settings') }}">modifier</a>)</p>
<div class="planner">
    <div class="plan-grid">
        <div class="ph"></div>
        @foreach ($menus as $m)<div class="ph">{{ ucfirst($m->date->locale('fr')->translatedFormat('D')) }}<small>{{ $m->date->format('d/m') }} @if ($m->is_published)<span class="badge green xs">publié</span>@endif</small></div>@endforeach
        @foreach ($cats as $c => $label)
            <div class="rh">{{ $label }}</div>
            @foreach ($menus as $m)
                <div class="plan-cell" data-menu="{{ $m->id }}" data-cat="{{ $c }}" ondragover="event.preventDefault();this.classList.add('drop')" ondragleave="this.classList.remove('drop')" ondrop="dropDish(event,this)">
                    @foreach ($m->items->filter(fn ($i) => $i->dish?->category === $c) as $it)
                        <div class="plan-item mb" style="margin-bottom:6px">
                            <b>{{ $it->dish->name }}</b>
                            <form method="POST" action="{{ route('admin.menus.item', $it) }}" class="q row" style="gap:4px">@csrf @method('PUT')<input type="number" name="planned_portions" value="{{ $it->planned_portions }}" min="0" style="width:52px;font-size:11px;border:1px solid var(--line);border-radius:4px;padding:1px 3px" onchange="this.form.submit()"> portions · {{ $reserved[$m->id][$it->dish_id] ?? 0 }} rés.</form>
                            <form method="POST" action="{{ route('admin.menus.remove', $it) }}" data-confirm="Retirer {{ $it->dish->name }} ?" data-confirm-text="{{ ($reserved[$m->id][$it->dish_id] ?? 0) ? 'Des convives ont réservé ce plat : ils seront remboursés et notifiés.' : '' }}" data-danger>@csrf @method('DELETE')<button class="rm" title="Retirer">{!! ic('x') !!}</button></form>
                        </div>
                    @endforeach
                    <button type="button" class="plan-add" onclick="openAdd({{ $m->id }}, '{{ $c }}', '{{ $m->label('l j M') }}')">+ Ajouter</button>
                </div>
            @endforeach
        @endforeach
    </div>
    <aside class="catalog">
        <div class="row between mb"><h3 style="margin:0">Catalogue</h3><a class="small" href="{{ route('admin.dishes.create') }}">+ Nouveau</a></div>
        <input class="input mb" placeholder="Rechercher un plat" oninput="const v=this.value.toLowerCase();document.querySelectorAll('.cat-item').forEach(i=>i.style.display=i.dataset.name.includes(v)?'':'none')">
        @foreach ($catalog as $d)
            <div class="cat-item" draggable="true" data-name="{{ mb_strtolower($d->name) }}" ondragstart="event.dataTransfer.setData('text/plain','{{ $d->id }}')"><x-dish-img :dish="$d" size="sm" /><div class="grow small"><b>{{ $d->name }}</b><div class="xs muted">{{ $d->categoryLabel() }} · {{ fcfa($d->price) }}</div></div></div>
        @endforeach
    </aside>
</div>
@push('modals')
<div class="modal-bg hidden" id="m-add"><div class="modal">
    <h2>Ajouter un plat</h2><p class="small muted mb" id="add-day"></p>
    <form method="POST" id="add-form">@csrf
        <div class="field"><label>Plat</label><select class="input" name="dish_id" id="add-dish">@foreach ($catalog as $d)<option value="{{ $d->id }}" data-cat="{{ $d->category }}">{{ $d->name }} ({{ $d->categoryLabel() }})</option>@endforeach</select></div>
        <div class="field"><label>Portions prévues</label><input class="input" type="number" name="planned_portions" value="60" min="1"></div>
        <div class="actions"><button type="button" class="btn ghost" data-close>Annuler</button><button class="btn">Ajouter</button></div>
    </form>
</div></div>
@endpush
@push('scripts')
<script>
const addUrl = @json(route('admin.menus.add', ['menu' => '__M__']));
function openAdd(menu, cat, label) {
  document.getElementById('add-form').action = addUrl.replace('__M__', menu);
  document.getElementById('add-day').textContent = label + ' · ' + cat;
  const sel = document.getElementById('add-dish'); let first = null;
  [...sel.options].forEach(o => { o.hidden = o.dataset.cat !== cat; if (!o.hidden && !first) first = o; });
  if (first) sel.value = first.value;
  App.open('m-add');
}
async function dropDish(e, cell) {
  e.preventDefault(); cell.classList.remove('drop');
  const id = e.dataTransfer.getData('text/plain');
  const res = await fetch(addUrl.replace('__M__', cell.dataset.menu), { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json', 'Content-Type': 'application/json' }, body: JSON.stringify({ dish_id: id, category: cell.dataset.cat }) });
  const j = await res.json();
  if (j.ok) location.reload(); else App.toast(j.message, 'red');
}
</script>
@endpush
@endsection
