@extends('layouts.base')
@section('title', 'Comptoir')
@section('body')
@php
    $r = $result && $result['id'] ? \App\Models\Reservation::with('user', 'items.dish', 'checker')->find($result['id']) : null;
    $me = auth()->user();
@endphp
<div class="kiosk">
    <div class="kiosk-head">
        <div class="brand" style="color:#fff"><img src="{{ asset('img/vatel_logo.png') }}" alt="Vatel" style="background:#fff;border-radius:4px"><span>Comptoir<small style="color:#aaa">Créneau en cours : {{ slot_label($slot) }}</small></span></div>
        <div class="counter">{{ $served }} / {{ $total }}<small>repas servis</small></div>
        @if ($me->isCounter())
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn sm ghost" style="color:#fff;border-color:#444">{!! ic('logout') !!} Quitter</button></form>
        @else
            <a class="btn sm ghost" style="color:#fff;border-color:#444" href="{{ route('admin.dashboard') }}">Quitter</a>
        @endif
    </div>
    <div class="kiosk-body">
        <div>
            <div class="scan-zone">
                <div id="reader" style="width:100%;max-width:360px;margin:0 auto;border-radius:16px;overflow:hidden;background:#111"></div>
                <div class="row mt" style="justify-content:center">
                    <button type="button" class="btn sky" id="cam-btn" onclick="startCam()">{!! ic('camera') !!} Scanner le QR code</button>                </div>
                <p class="xs mt center" style="color:#aaa">La caméra nécessite HTTPS (ou localhost).</p>
            </div>
            <form method="POST" action="{{ route('admin.counter.scan') }}" id="scan-form" class="mt">@csrf
                <label class="small" style="color:#aaa">Code à 6 chiffres</label>
                <div class="row mt"><input class="code-input grow" name="code" id="code" inputmode="numeric" autocomplete="off" maxlength="64" placeholder="000000" autofocus><button class="btn sky">Valider le code</button></div>
            </form>
            <div class="recent mt">
                <b class="small" style="color:#aaa">Derniers servis</b>
                @foreach ($recent as $x)<div class="r small"><span>{{ $x->served_at->format('H\hi') }}</span> <b>{{ $x->user?->name }}</b> · {{ $x->mainDishName() }}</div>@endforeach
            </div>
        </div>
        <div>
            @if ($result)
                <div class="result {{ $result['state'] }}">
                    <div class="status">{!! ic($result['state'] === 'ok' ? 'check-circle' : 'alert') !!} {{ $result['title'] }}</div>
                    @if ($r)
                        <div class="row mt"><x-avatar :user="$r->user" size="lg" /><div><h2>{{ $r->user?->name ?? 'Utilisateur supprimé' }}</h2><div class="small">{{ $r->class_snapshot ?? $r->user?->groupLabel() }} · créneau {{ $r->slotLabel() }}</div></div></div>
                        @if ($r->allergy_conflict)
                            <div class="alert red mt" style="font-size:16px">{!! ic('alert') !!} <span><b>ALLERGIE : {{ mb_strtoupper(collect($r->allergy_conflict)->flatMap(fn ($c) => array_map('allergen_label', $c['allergens']))->unique()->join(', ')) }}</b><br>Le plateau contient {{ collect($r->allergy_conflict)->pluck('name')->join(', ') }} — vérifier avant de servir.@if ($r->checker)<br><span class="small">Vérifié par {{ $r->checker->name }}</span>@endif</span></div>
                        @elseif ($r->user?->allergyList())
                            <div class="alert orange mt">{!! ic('alert') !!} Allergie déclarée : {{ implode(', ', array_map('allergen_label', $r->user->allergyList())) }}</div>
                        @endif
                        <div class="serve-list mt">@foreach ($r->items as $i)<div><b>{{ $i->quantity }}×</b> {{ $i->dish->name }}</div>@endforeach</div>
                        @if (! empty($result['can_force']))
                            <form method="POST" action="{{ route('admin.counter.scan') }}" class="mt">@csrf<input type="hidden" name="code" value="{{ $r->code }}"><input type="hidden" name="force" value="1"><button class="btn">Servir quand même</button></form>
                        @endif
                    @endif
                </div>
            @else
                <div class="result" style="text-align:center;color:#aaa"><div class="ico" style="font-size:48px">{!! ic('qr') !!}</div><p>Scannez un QR code ou saisissez le code de secours.</p></div>
            @endif
        </div>
    </div>
</div>
@push('scripts')
<script src="{{ asset('vendor/html5-qrcode.min.js') }}"></script>
<script>
let scanner = null;
function startCam() {
  if (!window.Html5Qrcode) { App.toast('Scanner indisponible', 'red'); return; }
  scanner = new Html5Qrcode('reader');
  scanner.start({ facingMode: 'environment' }, { fps: 10, qrbox: 220 }, (text) => {
    scanner.stop(); document.getElementById('code').value = text; document.getElementById('scan-form').submit();
  }).catch(() => App.toast('Caméra inaccessible (autorisation ou HTTPS requis). Saisissez le code.', 'red'));
}
</script>
@endpush
@endsection
