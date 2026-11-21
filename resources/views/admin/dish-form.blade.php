@extends('layouts.admin')
@section('title', $d->exists ? $d->name : 'Nouveau plat')
@section('nav', 'dishes')
@section('content')
@php $recipe = $d->exists ? $d->ingredients : collect(); @endphp
<div class="page-head">
    <div><a class="small" href="{{ route('admin.dishes') }}">‹ Catalogue</a><h1>{{ $d->exists ? $d->name : 'Nouveau plat' }}</h1></div>
    @if ($d->exists)
    <div class="row">
        <button class="btn ghost" data-open="m-preview">{!! ic('search') !!} Aperçu</button>
        <form method="POST" action="{{ route('admin.dishes.archive', $d) }}">@csrf<button class="btn ghost">{{ $d->is_archived ? 'Réactiver' : 'Archiver' }}</button></form>
        <form method="POST" action="{{ route('admin.dishes.destroy', $d) }}" data-confirm="Supprimer {{ $d->name }} ?" data-confirm-text="Le plat sera retiré du catalogue (suppression douce, l’historique est conservé)." data-danger>@csrf @method('DELETE')<button class="btn danger">{!! ic('trash') !!} Supprimer</button></form>
    </div>
    @endif
</div>
<div class="grid-2">
    <form method="POST" action="{{ $d->exists ? route('admin.dishes.update', $d) : route('admin.dishes.store') }}" class="card" id="dish-form">
        @csrf @if ($d->exists) @method('PUT') @endif
        <div class="grid-2-eq">
            <div class="field"><label>Nom *</label><input class="input" name="name" value="{{ old('name', $d->name) }}" required></div>
            <div class="field"><label>Prix (FCFA) *</label><input class="input" type="number" min="0" step="50" name="price" value="{{ old('price', $d->price) }}" required></div>
            <div class="field"><label>Catégorie</label><select class="input" name="category">@foreach (config('cantine.categories') as $k => $l)<option value="{{ $k }}" {{ old('category', $d->category) === $k ? 'selected' : '' }}>{{ $l }}</option>@endforeach</select></div>
            <div class="field"><label>Calories (kcal)</label><input class="input" type="number" min="0" name="kcal" value="{{ old('kcal', $d->kcal) }}"></div>
        </div>
        <div class="field"><label>Description</label><textarea class="input" name="description" rows="3">{{ old('description', $d->description) }}</textarea></div>
        <div class="field"><label>Créé par (étudiant · Chef de la semaine)</label><input class="input" name="creator_name" value="{{ old('creator_name', $d->creator_name) }}" placeholder="Ex. Koffi B. (BTS Cuisine)"></div>
        <div class="cat-label">Allergènes</div>
        <div class="check-grid">@foreach (config('cantine.allergens') as $k => $l)<label class="check"><input type="checkbox" name="allergens[]" value="{{ $k }}" {{ in_array($k, $d->allergens ?? [], true) ? 'checked' : '' }}> {{ $l }}</label>@endforeach</div>
        <div class="cat-label">Régimes</div>
        <div class="check-grid">@foreach (config('cantine.diets') as $k => $l)<label class="check"><input type="checkbox" name="diets[]" value="{{ $k }}" {{ in_array($k, $d->diets ?? [], true) ? 'checked' : '' }}> {{ $l }}</label>@endforeach</div>
        <div class="cat-label">Recette (quantité par portion)</div>
        @for ($i = 0; $i < max(4, $recipe->count() + 1); $i++)
            @php $ing = $recipe[$i] ?? null; @endphp
            <div class="row mb"><select class="input grow" name="recipe[{{ $i }}][ingredient_id]"><option value="">—</option>@foreach ($ingredients as $g)<option value="{{ $g->id }}" {{ $ing?->id === $g->id ? 'selected' : '' }}>{{ $g->name }} ({{ $g->unit }})</option>@endforeach</select><input class="input" style="max-width:110px" type="number" step="0.001" min="0" name="recipe[{{ $i }}][quantity]" value="{{ $ing?->pivot->quantity + 0 ?: '' }}" placeholder="0,000"></div>
        @endfor
        <button class="btn mt">{{ $d->exists ? 'Enregistrer' : 'Publier dans le catalogue' }}</button>
    </form>
    <div>
        <div class="card">
            <h3>Photo</h3>
            <div class="upload-zone mt"><x-dish-img :dish="$d->exists ? $d : null" size="lg" style="height:180px;border-radius:12px" /></div>
            @if ($d->exists)
                <div class="row wrap mt"><x-image-upload :action="route('admin.dishes.photo', $d)" shape="square" id="dishphoto" />
                    @if ($d->image_path)<form method="POST" action="{{ route('admin.dishes.photo.delete', $d) }}" data-confirm="Supprimer la photo ?" data-confirm-text="L’image par défaut de la catégorie sera affichée." data-danger>@csrf @method('DELETE')<button class="btn sm danger">Supprimer</button></form>@endif</div>
                <p class="xs muted mt">Depuis l’ordinateur ou le téléphone · JPG, PNG, WebP · 5 Mo max · recadrage carré et compression WebP.</p>
            @else
                <p class="xs muted mt">Enregistrez le plat pour ajouter une photo. Sans photo, l’image par défaut de la catégorie est affichée.</p>
            @endif
        </div>
        @if ($d->exists)<div class="card mt"><h3>Utilisation</h3><p class="small mt">{{ $usage }} portion(s) réservée(s) au total.</p></div>@endif
    </div>
</div>
@if ($d->exists)
@push('modals')
<div class="modal-bg hidden" id="m-preview"><div class="modal" style="max-width:400px;padding:0;overflow:hidden">
    <x-dish-img :dish="$d" size="lg" />
    <div style="padding:16px"><div class="row between"><h2>{{ $d->name }}</h2><b>{{ fcfa($d->price) }}</b></div><p class="small muted">{{ $d->categoryLabel() }} · {{ $d->kcal }} kcal</p><p class="mt">{{ $d->description }}</p>
        <div class="row wrap mt">@foreach ($d->allergens ?? [] as $a)<span class="badge gray">{{ allergen_label($a) }}</span>@endforeach</div>
        <div class="actions"><button class="btn ghost" data-close>Fermer</button><button class="btn" onclick="document.getElementById('dish-form').submit()">Publier</button></div></div>
</div></div>
@endpush
@endif
@endsection
