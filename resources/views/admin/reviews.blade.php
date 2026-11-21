@extends('layouts.admin')
@section('title', 'Avis')
@section('nav', 'reviews')
@section('content')
@php $mod = app(\App\Services\ModerationService::class); $qs = fn ($o) => '?'.http_build_query(array_merge(['statut' => $status, 'type' => $type, 'tri' => $sort], $o)); @endphp
<div class="page-head">
    <div><h1>Modération des avis</h1><p>Un avis contenant un mot interdit est signalé et masqué jusqu’à décision.</p></div>
    <button class="btn ghost" data-open="m-words">{!! ic('pencil') !!} Mots interdits</button>
</div>
<div class="row wrap mb">
    <div class="chips">@foreach (['flagged' => 'Signalés', 'visible' => 'Publiés', 'hidden' => 'Masqués', 'all' => 'Tous'] as $k => $l)<a class="chip {{ $status === $k ? 'active' : '' }}" href="{{ $qs(['statut' => $k]) }}">{{ $l }} ({{ $k === 'all' ? $counts->sum() : ($counts[$k] ?? 0) }})</a>@endforeach</div>
    <span class="grow"></span>
    <select class="input" style="max-width:200px" onchange="location=this.value">@foreach (['all' => 'Plats et menus', 'dish' => 'Avis sur un plat', 'menu' => 'Avis sur le menu'] as $k => $l)<option value="{{ $qs(['type' => $k]) }}" {{ $type === $k ? 'selected' : '' }}>{{ $l }}</option>@endforeach</select>
    <select class="input" style="max-width:180px" onchange="location=this.value">@foreach (['recent' => 'Plus récents', 'asc' => 'Note croissante', 'desc' => 'Note décroissante'] as $k => $l)<option value="{{ $qs(['tri' => $k]) }}" {{ $sort === $k ? 'selected' : '' }}>{{ $l }}</option>@endforeach</select>
</div>
<div class="stack">
@forelse ($reviews as $v)
    <div class="review-card card {{ $v->status === 'flagged' ? 'flag' : ($v->status === 'hidden' ? 'hidden-r' : '') }}">
        <div class="row between"><div class="row"><x-avatar :user="$v->user" style="width:32px;height:32px;font-size:12px" /><div><b class="small">{{ $v->authorName() }}</b><div class="xs muted">{{ $v->user?->groupLabel() }} · {{ $v->dish ? 'Plat : '.$v->dish->name : 'Menu du '.$v->menu->label('l j F') }}</div></div></div>{!! stars($v->rating) !!}</div>
        <p class="mt">{!! $mod->highlight($v->comment, $v->flag_words) !!}</p>
        <div class="row wrap mt">
            <span class="badge {{ ['visible' => 'green', 'flagged' => 'orange', 'hidden' => 'gray'][$v->status] }}">{{ ['visible' => 'Publié', 'flagged' => 'Signalé', 'hidden' => 'Masqué'][$v->status] }}</span>
            @if ($v->flag_reason)<span class="xs muted">{{ $v->flag_reason }}</span>@endif
            <span class="xs muted">{{ $v->created_at->format('d/m · H:i') }}</span>
            <span class="grow"></span>
            @if ($v->status === 'flagged')<form method="POST" action="{{ route('admin.reviews.moderate', $v) }}">@csrf<input type="hidden" name="action" value="approve"><button class="btn sm green">Approuver et publier</button></form>@endif
            @if ($v->status === 'hidden')<form method="POST" action="{{ route('admin.reviews.moderate', $v) }}">@csrf<input type="hidden" name="action" value="republish"><button class="btn sm ghost">Republier</button></form>
            @else
                <form method="POST" action="{{ route('admin.reviews.moderate', $v) }}" class="row" style="gap:4px">@csrf<input type="hidden" name="action" value="hide"><select class="input" name="reason" style="padding:6px;font-size:13px">@foreach (['Insulte', 'Spam', 'Hors sujet', 'Informations personnelles'] as $r)<option>{{ $r }}</option>@endforeach</select><button class="btn sm danger">Masquer</button></form>
            @endif
        </div>
    </div>
@empty
    <div class="empty"><div class="ico">{!! ic('message') !!}</div>Aucun avis dans cette catégorie.</div>
@endforelse
</div>
{{ $reviews->links('partials.pager') }}
@push('modals')
<div class="modal-bg hidden" id="m-words"><div class="modal"><h2>Mots interdits</h2>
    <form method="POST" action="{{ route('admin.reviews.words') }}">@csrf @method('PUT')
        <p class="small muted mb">Un mot ou une expression par ligne. Les avis publiés seront réanalysés.</p>
        <textarea class="input" name="words" rows="10">{{ implode("\n", $words) }}</textarea>
        <div class="actions"><button type="button" class="btn ghost" data-close>Annuler</button><button class="btn">Enregistrer et réanalyser</button></div>
    </form></div></div>
@endpush
@endsection
