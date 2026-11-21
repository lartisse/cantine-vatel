@extends('layouts.mobile')
@section('title', __('Notifications'))
@section('tab', auth()->user()->role === 'parent' ? 'alerts' : 'home')
@section('back', auth()->user()->homeRoute())
@section('content')
@php $unread = $items->whereNull('read_at')->count(); @endphp
<div class="row between mb"><span class="small muted">{{ trans_choice(':n non lue|:n non lues', $unread, ['n' => $unread]) }}</span>
    @if ($unread)<form method="POST" action="{{ route('notifications.readAll') }}">@csrf<button class="btn sm ghost">{{ __('Tout marquer comme lu') }}</button></form>@endif
</div>
<div class="stack">
    @forelse ($items as $n)
        <a href="{{ route('notifications.open', $n) }}" class="notif {{ $n->read_at ? '' : 'unread' }}" style="color:inherit">
            <div class="ico">{!! ic($n->icon) !!}</div>
            <div class="grow"><b class="small">{{ __($n->title) }}</b><div class="small muted">{{ $n->body }}</div><div class="xs muted mt">{{ $n->created_at->format('d/m · H:i') }}</div></div>
        </a>
    @empty
        <div class="empty"><div class="ico">{!! ic('bell') !!}</div>{{ __('Aucune notification.') }}</div>
    @endforelse
</div>
@endsection
