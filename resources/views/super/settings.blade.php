@extends('layouts.admin')
@section('title', 'Paramètres de l’application')
@section('nav', 'app-settings')
@section('content')
<div class="page-head"><div><h1>Paramètres de l’application</h1><p>Réglages d’affichage réservés au super administrateur.</p></div></div>
<div class="grid-2">
    <form method="POST" action="{{ route('super.settings.update') }}" class="card">@csrf @method('PUT')
        <h3>{!! ic('sparkles') !!} Carrousel « Suggestions pour vous »</h3>
        <p class="small muted mt">Sur l’accueil des élèves et du personnel, les suggestions (un plat par catégorie) défilent automatiquement. Le défilement s’interrompt quand l’utilisateur touche, survole ou fait défiler le carrousel.</p>
        <div class="field mt"><label for="interval">Délai entre deux suggestions (secondes)</label>
            <div class="row"><input class="input" type="number" id="interval" name="suggestion_interval" min="0" max="60" step="1" value="{{ old('suggestion_interval', $interval) }}" style="max-width:120px" required><span class="small muted">0 = défilement automatique désactivé · par défaut : 5 s</span></div>
            @error('suggestion_interval')<span class="error">{{ $message }}</span>@enderror
        </div>
        <div class="chips mb">@foreach ([3, 5, 8, 10] as $s)<button type="button" class="chip {{ (int) $interval === $s ? 'active' : '' }}" onclick="document.getElementById('interval').value={{ $s }}">{{ $s }} s</button>@endforeach</div>
        <button class="btn">Enregistrer</button>
    </form>
</div>
@endsection
