@extends('layouts.admin')
@section('title', $u->name)
@section('nav', 'users')
@section('content')
<div class="page-head"><div><a class="small" href="{{ route('admin.users') }}">‹ Utilisateurs</a><h1>{{ $u->name }}</h1><p>{{ $u->typeLabel() }} · {{ $u->email }}</p></div></div>
<div class="grid-2">
    <div>
        <div class="card info-list">
            <div class="row mb"><x-avatar :user="$u" size="lg" /><div><b>{{ $u->name }}</b><div class="small muted">{{ $u->role === 'parent' ? 'Parent' : $u->groupLabel() }}</div></div></div>
            <div class="li"><span>Téléphone</span><span>{{ $u->phone ?: '—' }}</span></div>
            @if ($u->role === 'user')
                <div class="li"><span>Matricule</span><span>{{ $u->matricule ?: '—' }}</span></div>
                <div class="li"><span>Allergies</span><span>{{ $u->allergyList() ? implode(', ', array_map('allergen_label', $u->allergyList())) : 'Aucune' }}</span></div>
                <div class="li"><span>Parent(s) lié(s)</span><span>{{ $u->parents->pluck('name')->join(', ') ?: '—' }}</span></div>
            @else
                <div class="li"><span>Enfants liés</span><span>{{ $u->children->pluck('name')->join(', ') ?: '—' }}</span></div>
            @endif
        </div>
        <div class="card mt"><h3>Dernières réservations</h3>@forelse ($res as $r)<div class="summary-line small"><span>{{ $r->menu->label('D j M') }} · {{ $r->slotLabel() }}</span><span class="badge {{ $r->statusBadge() }}">{{ $r->statusLabel() }}</span></div>@empty<p class="small muted">—</p>@endforelse</div>
    </div>
    @if ($u->role === 'user')
    <div>
        <div class="balance-card"><div class="small" style="color:#bbb">Solde</div><div class="amount">{{ num($u->balance) }} <small>FCFA</small></div></div>
        <form method="POST" action="{{ route('admin.users.adjust', $u) }}" class="card mt">@csrf
            <h3>Ajustement manuel (paiement au guichet)</h3>
            <div class="field mt"><label>Montant (négatif pour débiter)</label><input class="input" type="number" name="amount" step="50" required></div>
            <div class="field"><label>Motif *</label><input class="input" name="reason" placeholder="Paiement en espèces au guichet" required></div>
            <button class="btn">Ajuster le solde</button>
        </form>
        <div class="card mt"><h3>Transactions</h3>@foreach ($txs as $t)<div class="summary-line small"><span>{{ $t->created_at->format('d/m') }} · {{ $t->label }}</span><b style="color:{{ $t->amount > 0 ? 'var(--green)' : 'inherit' }}">{{ $t->amount > 0 ? '+' : '' }}{{ fcfa($t->amount) }}</b></div>@endforeach</div>
    </div>
    @endif
</div>
@endsection
