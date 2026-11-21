@if (config('cantine.demo'))
@php
    $me = auth()->user();
    $accounts = [['awa.kone@vatel.ci', 'graduation', 'Élève'], ['jm.aka@vatel.ci', 'briefcase', 'Personnel'], ['i.kone@gmail.com', 'users', 'Parent'],
        ['gestion@cantine-vatel.ci', 'briefcase', 'Admin'], ['superadmin@cantine-vatel.ci', 'shield', 'Super admin'], ['comptoir1@cantine-vatel.ci', 'scan', 'Comptoir']];
@endphp
<div class="demo-bar" id="demo-bar">
    <strong>DÉMO</strong>
    <form method="POST" action="{{ route('logout') }}" style="display:inline">@csrf<button class="{{ $me ? '' : 'active' }}">Visiteur</button></form>
    @foreach ($accounts as [$email, $icon, $label])
        <form method="POST" action="{{ route('demo.login') }}" style="display:inline">@csrf<input type="hidden" name="email" value="{{ $email }}"><button class="{{ $me?->email === $email ? 'active' : '' }}">{!! ic($icon) !!} {{ $label }}</button></form>
    @endforeach
    <span class="spacer"></span>
    <span class="clock">{{ now()->locale('fr')->translatedFormat('D j M') }} ·</span>
    <form method="POST" action="{{ route('demo.time') }}" style="display:inline">@csrf
        <select name="time" onchange="this.form.submit()" style="background:#2b2b28;color:#fff;border:1px solid #444;border-radius:6px;padding:2px 6px">
            @foreach (['09:40', '11:40', '12:10', '12:40', '13:05', '15:00'] as $t)
                <option {{ now()->format('H:i') === $t ? 'selected' : '' }}>{{ $t }}</option>
            @endforeach
        </select>
    </form>
</div>
@endif
