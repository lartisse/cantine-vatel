@extends('layouts.admin')
@section('title', 'Paramètres de l’application')
@section('nav', 'app-settings')
@section('content')
<div class="page-head"><div><h1>Paramètres de l’application</h1><p>Réglages réservés au super administrateur.</p></div></div>
<div class="grid-2-eq">
    <div>
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

    <form method="POST" action="{{ route('super.settings.app') }}" class="card mt" id="application">@csrf @method('PUT')
        <div class="row between"><h3>{!! ic('download') !!} Application mobile</h3><span class="badge {{ $appLinks ? 'green' : 'gray' }}">{{ $appLinks ? 'Bouton affiché' : 'Bouton masqué' }}</span></div>
        <p class="small muted mt">Liens des boutons « Télécharger l’application » de la page d’accueil. Laissez un champ vide pour masquer le bouton correspondant.</p>
        <div class="field mt"><label for="app-android">Android (Google Play ou fichier APK)</label>
            <input class="input" type="url" id="app-android" name="android_url" value="{{ old('android_url', $appLinks['android'] ?? '') }}" placeholder="https://play.google.com/store/apps/details?id=…" maxlength="500">
            @error('android_url')<span class="error">{{ $message }}</span>@enderror</div>
        <div class="field"><label for="app-ios">iPhone / iPad (App Store ou TestFlight)</label>
            <input class="input" type="url" id="app-ios" name="ios_url" value="{{ old('ios_url', $appLinks['ios'] ?? '') }}" placeholder="https://apps.apple.com/app/…" maxlength="500">
            @error('ios_url')<span class="error">{{ $message }}</span>@enderror</div>
        <button class="btn">Enregistrer</button>
    </form>

    <div class="card mt" id="classes">
        <div class="row between"><h3>{!! ic('graduation') !!} Classes de l’école</h3><span class="badge">{{ count($classes) }}</span></div>
        <p class="small muted mt">Liste proposée aux élèves (inscription, profil, confirmation de classe). Un élève dont la classe n’y figure pas peut choisir « Autre » et la saisir. Retirer une classe ne modifie pas les élèves qui l’ont déjà.</p>
        <div class="chips mt">
            @forelse ($classes as $c)
                <form method="POST" action="{{ route('super.settings.classes.remove') }}" class="inline" data-confirm="Retirer « {{ $c }} » de la liste ?" data-confirm-text="{{ ($classCounts[$c] ?? 0) ? ($classCounts[$c].' élève(s) la conservent sur leur profil.') : 'Aucun élève dans cette classe.' }}" data-confirm-ok="Retirer" data-danger>@csrf @method('DELETE')<input type="hidden" name="name" value="{{ $c }}">
                    <span class="chip class-chip">{{ $c }}@if ($classCounts[$c] ?? 0)<span class="xs muted"> · {{ $classCounts[$c] }}</span>@endif<button class="class-x" title="Retirer {{ $c }}" aria-label="Retirer {{ $c }}">{!! ic('x') !!}</button></span>
                </form>
            @empty
                <span class="small muted">Aucune classe : les élèves saisiront la leur.</span>
            @endforelse
        </div>
        <form method="POST" action="{{ route('super.settings.classes.add') }}" class="mt">@csrf
            <label class="small bold" for="new-classes">Ajouter des classes</label>
            <div class="row mt" style="align-items:flex-start"><textarea class="input grow" id="new-classes" name="names" rows="2" placeholder="Ex. 2nde B, Tle D, BTS 1 Hôtellerie (séparées par des virgules ou une par ligne)">{{ old('names') }}</textarea><button class="btn">{!! ic('plus') !!} Ajouter</button></div>
            @error('names')<span class="error">{{ $message }}</span>@enderror
        </form>
        @if ($proposed)
            <div class="cat-label">Classes saisies par des élèves</div>
            <p class="xs muted">Absentes de la liste officielle. Ajoutez-les pour les proposer aux autres élèves.</p>
            <div class="stack mt">
                @foreach ($proposed as $name => $n)
                    <div class="row between"><span class="small"><b>{{ $name }}</b> <span class="muted">· {{ $n }} élève(s)</span></span>
                        <form method="POST" action="{{ route('super.settings.classes.add') }}">@csrf<input type="hidden" name="names" value="{{ $name }}"><button class="btn sm light">{!! ic('plus') !!} Ajouter à la liste</button></form></div>
                @endforeach
            </div>
        @endif
    </div>
    </div>

    <div>
        <form method="POST" action="{{ route('super.settings.smtp') }}" class="card" autocomplete="off" novalidate>@csrf @method('PUT')
            <div class="row between"><h3>{!! ic('mail') !!} Envoi des e-mails (SMTP)</h3>
                <span class="badge {{ $smtp['enabled'] ? 'green' : 'gray' }}">{{ $smtp['enabled'] ? 'Activé' : 'Désactivé' }}</span></div>
            <p class="small muted mt">Serveur utilisé pour envoyer les e-mails de l’application. Une fois activée, cette configuration remplace celle du fichier <code>.env</code>. Le mot de passe est chiffré et n’est jamais réaffiché.</p>
            <div class="row wrap mt" style="gap:6px">
                @if ($smtp['tested_at'])
                    <span class="badge {{ $smtp['test_ok'] ? 'green' : 'red' }}">{{ $smtp['test_ok'] ? 'Test réussi' : 'Test échoué' }}</span>
                    <span class="xs muted">{{ \Illuminate\Support\Carbon::parse($smtp['tested_at'])->format('d/m/Y H:i') }} — {{ $smtp['test_message'] }}</span>
                @else
                    <span class="xs muted">Jamais testée</span>
                @endif
            </div>

            <label class="check mt" style="border:0;padding:0"><input type="checkbox" name="enabled" value="1" {{ old('enabled', $smtp['enabled']) ? 'checked' : '' }}> <b>Activer l’envoi par ce serveur SMTP</b></label>
            <div class="grid-2-eq mt">
                <div class="field"><label for="smtp-host">Serveur SMTP</label><input class="input" id="smtp-host" name="host" value="{{ old('host', $smtp['host']) }}" placeholder="smtp.exemple.ci">@error('host')<span class="error">{{ $message }}</span>@enderror</div>
                <div class="field"><label for="smtp-port">Port</label><input class="input" type="number" id="smtp-port" name="port" min="1" max="65535" value="{{ old('port', $smtp['port']) }}" required>@error('port')<span class="error">{{ $message }}</span>@enderror</div>
            </div>
            <div class="field"><label for="smtp-enc">Chiffrement</label>
                <select class="input" id="smtp-enc" name="encryption" onchange="const p=document.getElementById('smtp-port');if(this.value==='ssl')p.value=465;else if(this.value==='tls')p.value=587;">
                    @foreach (\App\Support\MailSettings::ENCRYPTIONS as $k => $l)<option value="{{ $k }}" {{ old('encryption', $smtp['encryption']) === $k ? 'selected' : '' }}>{{ $l }}</option>@endforeach
                </select></div>
            <div class="grid-2-eq">
                <div class="field"><label for="smtp-user">Identifiant</label><input class="input" id="smtp-user" name="username" value="{{ old('username', $smtp['username']) }}" autocomplete="off"></div>
                <div class="field"><label for="smtp-pass">Mot de passe</label><input class="input" type="password" id="smtp-pass" name="password" placeholder="{{ $smtp['has_password'] ? 'Laisser vide pour conserver '.$smtpPassword : 'Non configuré' }}" autocomplete="new-password"></div>
            </div>
            @if ($smtp['has_password'])<label class="check" style="border:0;padding:0"><input type="checkbox" name="clear_password" value="1"> <span class="small">Supprimer le mot de passe enregistré ({{ $smtpPassword }})</span></label>@endif
            <div class="grid-2-eq mt">
                <div class="field"><label for="smtp-from">Adresse d’expédition</label><input class="input" type="email" id="smtp-from" name="from_address" value="{{ old('from_address', $smtp['from_address']) }}" placeholder="no-reply@cantine-vatel.ci">@error('from_address')<span class="error">{{ $message }}</span>@enderror</div>
                <div class="field"><label for="smtp-name">Nom d’expéditeur</label><input class="input" id="smtp-name" name="from_name" value="{{ old('from_name', $smtp['from_name']) }}"></div>
            </div>
            <div class="secure-note mt">{!! ic('lock') !!} <span>Ressaisissez <b>votre</b> mot de passe pour confirmer la modification.</span></div>
            <div class="field mt"><label for="smtp-confirm">Votre mot de passe</label><input class="input" type="password" id="smtp-confirm" name="current_password" required autocomplete="current-password">@error('current_password')<span class="error">{{ $message }}</span>@enderror</div>
            <button class="btn">Enregistrer la configuration</button>
        </form>

        <form method="POST" action="{{ route('super.settings.smtp.test') }}" class="card mt">@csrf
            <h3>Envoyer un e-mail de test</h3>
            <p class="small muted mt">Utilise la configuration enregistrée, même si elle n’est pas encore activée.</p>
            <div class="row wrap mt"><input class="input grow" type="email" name="to" value="{{ old('to', auth()->user()->email) }}" required style="min-width:200px">@error('to')<span class="error">{{ $message }}</span>@enderror
                <button class="btn ghost" {{ $smtp['host'] ? '' : 'disabled' }}>{!! ic('send') !!} Envoyer le test</button></div>
        </form>
    </div>
</div>
@endsection
