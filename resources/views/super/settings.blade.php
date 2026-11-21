@extends('layouts.admin')
@section('title', 'Paramètres de l’application')
@section('nav', 'app-settings')
@section('content')
<div class="page-head"><div><h1>Paramètres de l’application</h1><p>Réglages réservés au super administrateur.</p></div></div>
<div class="grid-2-eq">
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
