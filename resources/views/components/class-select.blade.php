@props(['value' => null, 'emptyLabel' => '—', 'id' => 'cls'.uniqid()])
{{-- Classe : liste gérée par le super admin + « Autre » pour saisir sa propre classe --}}
@php
    $classes = \App\Support\SchoolClasses::all();
    $current = old('class_name', $value);
    $isOther = $current === \App\Support\SchoolClasses::OTHER || ($current && ! \App\Support\SchoolClasses::match($current));
    $otherValue = old('class_other', $isOther && $current !== \App\Support\SchoolClasses::OTHER ? $current : '');
@endphp
<select class="input" name="class_name" id="{{ $id }}" onchange="document.getElementById('{{ $id }}-other').classList.toggle('hidden', this.value !== '{{ \App\Support\SchoolClasses::OTHER }}'); if (this.value === '{{ \App\Support\SchoolClasses::OTHER }}') document.getElementById('{{ $id }}-input').focus();">
    <option value="">{{ $emptyLabel }}</option>
    @foreach ($classes as $c)<option value="{{ $c }}" {{ ! $isOther && $current === $c ? 'selected' : '' }}>{{ $c }}</option>@endforeach
    <option value="{{ \App\Support\SchoolClasses::OTHER }}" {{ $isOther ? 'selected' : '' }}>{{ __('Autre (saisir ma classe)…') }}</option>
</select>
<div id="{{ $id }}-other" class="{{ $isOther ? '' : 'hidden' }}" style="margin-top:8px">
    <input class="input" id="{{ $id }}-input" name="class_other" value="{{ $otherValue }}" maxlength="30" placeholder="{{ __('Ex. 2nde B, BTS 1 Hôtellerie') }}" autocomplete="off">
    <span class="xs muted">{{ __('Votre classe n’est pas dans la liste ? Saisissez-la : la cantine pourra l’ajouter à la liste officielle.') }}</span>
</div>
@error('class_name')<span class="error">{{ $message }}</span>@enderror
@error('class_other')<span class="error">{{ $message }}</span>@enderror
