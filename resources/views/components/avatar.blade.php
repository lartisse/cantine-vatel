@props(['user', 'size' => ''])
@if ($user?->avatar_path)
    <div {{ $attributes->merge(['class' => 'avatar '.$size]) }}><img src="{{ $user->avatarUrl() }}" alt=""></div>
@else
    <div {{ $attributes->merge(['class' => 'avatar '.$size]) }}>{{ $user ? $user->initials() : '?' }}</div>
@endif
