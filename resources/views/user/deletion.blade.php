@extends('layouts.mobile')
@section('title', __('Supprimer mon compte'))
@section('back', route('profile'))
@section('tab', 'profile')
@section('content')
<div class="card">
    <p class="small">{{ __('Votre demande sera examinée par le super administrateur. Le compte est d’abord placé en corbeille (restaurable), puis effacé définitivement. Vos statistiques de repas sont conservées de façon anonyme.') }}</p>
    <p class="small mt">{{ __('Solde à régulariser avec l’administration :') }} <b>{{ fcfa($u->balance) }}</b></p>
</div>
@if ($req && $req->status === 'pending')
    <div class="alert orange mt">{!! ic('clock') !!} {{ __('Demande en attente depuis le :d.', ['d' => $req->created_at->format('d/m/Y')]) }}</div>
    <form method="POST" action="{{ route('profile.deletion.cancel') }}">@csrf @method('DELETE')<button class="btn ghost block mt">{{ __('Annuler ma demande') }}</button></form>
@else
    @if ($req && $req->status === 'refused')<div class="alert red mt">{!! ic('mail') !!} <span>{{ __('Demande refusée :') }} {{ $req->response_message }}</span></div>@endif
    <form method="POST" action="{{ route('profile.deletion') }}" class="mt">@csrf
        <div class="field"><label>{{ __('Motif (facultatif)') }}</label><textarea class="input" name="reason" rows="3" maxlength="500"></textarea></div>
        <button class="btn danger block">{{ __('Envoyer la demande') }}</button>
    </form>
@endif
@endsection
