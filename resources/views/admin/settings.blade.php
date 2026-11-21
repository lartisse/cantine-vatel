@extends('layouts.admin')
@section('title', 'Paramètres')
@section('nav', 'settings')
@section('content')
<div class="page-head"><div><h1>Paramètres</h1><p>Règles de réservation de la cantine.</p></div></div>
<div class="grid-2">
    <form method="POST" action="{{ route('admin.settings.update') }}" class="card">@csrf @method('PUT')
        <h3>Créneaux et capacités</h3>
        @php $i = 0; @endphp
        @foreach ($slots as $time => $cap)
            <div class="row mt"><input class="input" type="time" name="slots[{{ $i }}][time]" value="{{ $time }}" style="max-width:140px"><input class="input" type="number" min="0" name="slots[{{ $i }}][capacity]" value="{{ $cap }}" style="max-width:120px"><span class="small muted">places</span></div>
            @php $i++; @endphp
        @endforeach
        <div class="row mt"><input class="input" type="time" name="slots[{{ $i }}][time]" style="max-width:140px"><input class="input" type="number" min="0" name="slots[{{ $i }}][capacity]" placeholder="Capacité" style="max-width:120px"><span class="small muted">nouveau créneau</span></div>
        <div class="grid-2-eq mt">
            <div class="field"><label>Heure limite de réservation (jour même)</label><input class="input" type="time" name="cutoff" value="{{ $cutoff }}" required></div>
            <div class="field"><label>Seuil d’alerte de solde par défaut (FCFA)</label><input class="input" type="number" name="default_threshold" value="{{ $threshold }}" required></div>
        </div>
        <div class="field"><label>Règle de remboursement</label><input class="input" name="refund_rule" value="{{ $refund }}"></div>
        <div class="field"><label>Mots interdits (un par ligne)</label><textarea class="input" name="words" rows="6">{{ implode("\n", $words) }}</textarea></div>
        <button class="btn">Enregistrer</button>
    </form>
    <div class="card">
        <h3>Période scolaire</h3>
        <p class="small mt">Période en cours : <b>{{ $period['label'] }}</b></p>
        <form method="POST" action="{{ route('admin.settings.period') }}" class="mt" data-confirm="Démarrer une nouvelle période ?" data-confirm-text="Chaque élève devra confirmer sa classe à sa prochaine connexion.">@csrf
            <div class="field"><label>Nom de la nouvelle période</label><input class="input" name="label" placeholder="3e trimestre 2026-2027" required></div>
            <button class="btn ghost">{!! ic('refresh') !!} Démarrer une nouvelle période</button>
        </form>
    </div>
</div>
@endsection
