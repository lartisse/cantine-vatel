@extends('layouts.public')
@section('title', 'Inscription')
@section('content')
@php $type = old('type', 'eleve'); @endphp
<div class="auth-wrap">
    <h1>Créer un compte</h1>
    <p class="muted">En 2 minutes, réservez votre premier repas.</p>
    <div class="steps"><span class="on" id="st1"></span><span id="st2"></span><span id="st3"></span></div>
    @if ($errors->any())<div class="alert red mb">{!! ic('alert') !!} <span>{{ $errors->first() }}</span></div>@endif
    <form method="POST" action="{{ route('register') }}" id="reg" novalidate>
        @csrf
        <div data-step="1" @if ($errors->any()) class="hidden" @endif>
            <h3 class="mb">Vous êtes…</h3>
            <div class="choice-cards">
                @foreach (['eleve' => ['graduation', 'Élève', 'Je réserve mes repas, mes parents peuvent recharger mon compte.'], 'personnel' => ['briefcase', 'Personnel', 'Enseignant ou personnel de l’établissement.'], 'parent' => ['users', 'Parent', 'Je suis et recharge le compte de mes enfants.']] as $k => [$icon, $label, $text])
                    <label class="choice"><input type="radio" name="type" value="{{ $k }}" {{ $type === $k ? 'checked' : '' }}><span class="ico">{!! ic($icon) !!}</span><span><b>{{ $label }}</b><br><span class="small muted">{{ $text }}</span></span></label>
                @endforeach
            </div>
            <button type="button" class="btn block mt" onclick="regGo(2)">Continuer</button>
        </div>
        <div data-step="2" class="{{ $errors->any() ? '' : 'hidden' }}">
            <div class="field"><label>Prénom *</label><input class="input" name="first_name" value="{{ old('first_name') }}" required>@error('first_name')<span class="error">{{ $message }}</span>@enderror</div>
            <div class="field"><label>Nom *</label><input class="input" name="last_name" value="{{ old('last_name') }}" required>@error('last_name')<span class="error">{{ $message }}</span>@enderror</div>
            <div class="field"><label>Téléphone</label><input class="input" name="phone" value="{{ old('phone') }}" placeholder="07 00 00 00 00"></div>
            <div class="field"><label>E-mail *</label><input class="input" type="email" name="email" value="{{ old('email') }}" required>@error('email')<span class="error">{{ $message }}</span>@enderror</div>
            <div class="field"><label>Mot de passe * <span class="muted xs">(8 caractères minimum)</span></label><input class="input" type="password" name="password" minlength="8" required>@error('password')<span class="error">{{ $message }}</span>@enderror</div>
            <div data-for="eleve">
                <div class="field"><label>Classe <span class="muted xs">(facultatif)</span></label><select class="input" name="class_name"><option value="">—</option>@foreach (config('cantine.classes') as $c)<option {{ old('class_name') === $c ? 'selected' : '' }}>{{ $c }}</option>@endforeach</select></div>
                <div class="field"><label>Matricule <span class="muted xs">(facultatif)</span></label><input class="input" name="matricule" value="{{ old('matricule') }}"></div>
            </div>
            <div data-for="personnel"><div class="field"><label>Poste <span class="muted xs">(facultatif)</span></label><input class="input" name="position" value="{{ old('position') }}"></div></div>
            <div data-for="parent" class="alert sky">{!! ic('qr') !!} <span>Après l’inscription, vous lierez le compte de votre enfant en scannant le QR code affiché dans son profil.</span></div>
            <div class="row mt"><button type="button" class="btn ghost" onclick="regGo(1)">Retour</button><button type="button" class="btn grow" id="next2" onclick="regNext2()">Continuer</button></div>
        </div>
        <div data-step="3" class="hidden">
            <h3>Régime et allergies</h3>
            <p class="small muted mb">Les plats incompatibles seront signalés dans le menu.</p>
            <div class="cat-label">Régimes</div>
            <div class="check-grid">@foreach (config('cantine.diets') as $k => $l)<label class="check"><input type="checkbox" name="diets[]" value="{{ $k }}"> {{ $l }}</label>@endforeach</div>
            <div class="cat-label">Allergies</div>
            <div class="check-grid">@foreach (config('cantine.allergens') as $k => $l)<label class="check"><input type="checkbox" name="allergies[]" value="{{ $k }}"> {{ $l }}</label>@endforeach</div>
            <div class="row mt"><button type="button" class="btn ghost" onclick="regGo(2)">Retour</button><button class="btn grow">Créer mon compte</button></div>
        </div>
    </form>
    <p class="center small mt">Déjà inscrit ? <a href="{{ route('login') }}">Se connecter</a></p>
</div>
@push('scripts')
<script>
function regType() { return document.querySelector('input[name=type]:checked').value; }
function regGo(n) {
  document.querySelectorAll('#reg [data-step]').forEach(s => s.classList.toggle('hidden', +s.dataset.step !== n));
  [1, 2, 3].forEach(i => document.getElementById('st' + i).classList.toggle('on', i <= n));
  document.getElementById('st3').classList.toggle('hidden', regType() === 'parent');
  document.querySelectorAll('#reg [data-for]').forEach(b => b.classList.toggle('hidden', b.dataset.for !== regType()));
  document.getElementById('next2').textContent = regType() === 'parent' ? 'Créer mon compte' : 'Continuer';
}
function regNext2() {
  const f = document.getElementById('reg');
  for (const n of ['first_name', 'last_name', 'email', 'password']) { const i = f.elements[n]; if (!i.checkValidity()) { i.reportValidity(); return; } }
  if (regType() === 'parent') f.submit(); else regGo(3);
}
@if ($errors->any()) regGo(2); @endif
</script>
@endpush
@endsection
