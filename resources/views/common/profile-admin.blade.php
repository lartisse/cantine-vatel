@extends('layouts.admin')
@section('title', 'Mon profil')
@section('content')
<div class="page-head"><div><h1>Mon profil</h1><p>{{ $u->typeLabel() }} · {{ $u->email }}</p></div></div>
<div class="grid-2-eq">
    <div class="card">
        <div class="row"><x-avatar :user="$u" size="lg" /><div><b>{{ $u->name }}</b><div class="small muted">{{ $u->position }}</div></div></div>
        <div class="row mt"><x-image-upload :action="route('profile.photo')" id="avatar" />
            @if ($u->avatar_path)<form method="POST" action="{{ route('profile.photo.delete') }}" data-confirm="Supprimer la photo ?" data-danger>@csrf @method('DELETE')<button class="btn sm danger">Supprimer</button></form>@endif</div>
        <form method="POST" action="{{ route('profile.update') }}" class="mt">@csrf @method('PUT')
            <div class="field"><label>Prénom *</label><input class="input" name="first_name" value="{{ $u->first_name }}" required></div>
            <div class="field"><label>Nom *</label><input class="input" name="last_name" value="{{ $u->last_name }}" required></div>
            <div class="field"><label>Téléphone</label><input class="input" name="phone" value="{{ $u->phone }}"></div>
            <div class="field"><label>Fonction</label><input class="input" name="position" value="{{ $u->position }}"></div>
            <button class="btn">Enregistrer</button>
        </form>
    </div>
    <div class="card">
        <h3>Mot de passe</h3>
        <form method="POST" action="{{ route('profile.password') }}" class="mt">@csrf @method('PUT')
            <div class="field"><label>Mot de passe actuel</label><input class="input" type="password" name="current_password" required></div>
            <div class="field"><label>Nouveau mot de passe</label><input class="input" type="password" name="password" minlength="8" required></div>
            <div class="field"><label>Confirmation</label><input class="input" type="password" name="password_confirmation" required></div>
            <button class="btn">Changer le mot de passe</button>
        </form>
    </div>
</div>
@endsection
