@extends('layouts.admin')
@section('title', 'Journal d’audit')
@section('nav', 'audit')
@section('content')
<div class="page-head"><div><h1>Journal d’audit</h1><p>Non modifiable · qui, quand, quoi, IP.</p></div><a class="btn ghost" href="{{ route('super.audit.export', request()->query()) }}">{!! ic('download') !!} Exporter CSV</a></div>
<form class="toolbar row wrap mb" method="GET">
    <select class="input" name="action" style="max-width:240px"><option value="">Toutes les actions</option>@foreach ($labels as $k => $l)<option value="{{ $k }}" {{ request('action') === $k ? 'selected' : '' }}>{{ $l }}</option>@endforeach</select>
    <input class="input" name="q" value="{{ request('q') }}" placeholder="Rechercher" style="max-width:240px"><button class="btn sm">{!! ic('search') !!} Filtrer</button>
</form>
<div class="table-wrap card"><table class="tbl">
    <tr><th>Quand</th><th>Qui</th><th>Action</th><th>Cible</th><th>Détail</th><th>IP</th></tr>
    @forelse ($logs as $l)
        @php $red = in_array($l->action, ['trash', 'delete', 'purge', 'admin_disable', 'dish_delete', 'api_key_revoke', 'api_key_delete'], true); $orange = in_array($l->action, ['api_keys', 'payment_toggle', 'role_change', 'api_key_create', 'api_key_rotate', 'api_key_update', 'api_key_suspend', 'api_topup'], true); @endphp
        <tr><td class="small">{{ $l->created_at?->format('d/m · H:i') }}</td><td class="small"><b>{{ $l->actor_name }}</b><br><span class="xs muted">{{ \App\Models\User::ROLE_LABELS[$l->actor_role] ?? $l->actor_role }}</span></td>
            <td><span class="badge {{ $red ? 'red' : ($orange ? 'orange' : '') }}">{{ $labels[$l->action] ?? $l->action }}</span></td><td class="small">{{ $l->target_label }}</td><td class="small">{{ $l->details }}</td><td class="xs muted">{{ $l->ip }}</td></tr>
    @empty
        <tr><td colspan="6" class="center muted">Aucune entrée.</td></tr>
    @endforelse
</table></div>
{{ $logs->links('partials.pager') }}
@endsection
