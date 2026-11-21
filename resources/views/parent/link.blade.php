@extends('layouts.mobile')
@section('title', __('Ajouter un enfant'))
@section('back', route('parent.home'))
@section('no_tabs', 1)
@section('no_errors', 1)
@section('content')
@if ($found)
    <div class="card center">
        <p class="small muted">{{ __('Élève trouvé') }}</p>
        <x-avatar :user="$found" size="lg" style="margin:10px auto" />
        <h2>{{ $found->name }}</h2>
        <p class="muted small">{{ $found->class_name ?: __('Classe non renseignée') }}</p>
        @if ($found->parents()->count())<p class="xs muted mt">{{ __('Déjà lié à un autre parent : les deux comptes pourront suivre l’élève.') }}</p>@endif
        <form method="POST" action="{{ route('parent.link.store') }}">@csrf<input type="hidden" name="code" value="{{ $token }}"><button class="btn block mt">{{ __('Confirmer le lien') }}</button></form>
        <a class="btn ghost block mt" href="{{ route('parent.link') }}">{{ __('Ce n’est pas mon enfant') }}</a>
    </div>
@else
    <div class="card center">
        <div id="reader" style="max-width:260px;margin:0 auto;border-radius:14px;overflow:hidden;background:#111;min-height:40px"></div>
        <p class="small mt">{!! __('Demandez à votre enfant d’ouvrir <b>Profil › Lier un parent</b> et scannez le QR code affiché.') !!}</p>
        <button class="btn block mt" type="button" onclick="startScan()">{!! ic('camera') !!} {{ __('Scanner le QR code') }}</button>
    </div>
    <form method="POST" action="{{ route('parent.link.find') }}" id="link-form">@csrf
        <details class="mt" {{ $errors->any() ? 'open' : '' }}><summary class="small" style="cursor:pointer">{{ __('Le scan ne fonctionne pas ? Saisir le code de secours') }}</summary>
            <div class="field mt"><input class="input" name="code" id="lk-code" placeholder="Ex. INE-5Q1" style="text-transform:uppercase;letter-spacing:.1em" value="{{ old('code') }}"></div>
            <button class="btn ghost block">{{ __('Valider le code') }}</button>
        </details>
    </form>
    @error('code')<div class="alert red mt">{{ $message }}</div>@enderror
    <p class="xs muted mt">{{ __('La liaison se fait uniquement par le QR code de l’élève (le matricule n’est pas utilisé). Vous pouvez lier plusieurs enfants.') }}</p>
@endif
@push('scripts')
<script src="{{ asset('vendor/html5-qrcode.min.js') }}"></script>
<script>
function startScan() {
  if (!window.Html5Qrcode) return;
  const s = new Html5Qrcode('reader');
  s.start({ facingMode: 'environment' }, { fps: 10, qrbox: 200 }, (text) => { s.stop(); document.getElementById('lk-code').value = text; document.getElementById('link-form').submit(); })
   .catch(() => App.toast(@json(__('Caméra inaccessible (autorisation ou HTTPS requis). Utilisez le code de secours.')), 'red'));
}
</script>
@endpush
@endsection
