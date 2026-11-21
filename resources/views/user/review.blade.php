@extends('layouts.mobile')
@section('title', __('Votre avis'))
@section('back', route('user.home'))
@section('no_tabs', 1)
@section('content')
<div class="card sky mb"><b>{{ __('Menu du :d', ['d' => mb_strtolower($menu->label())]) }}</b></div>
@if (! $res)
    <div class="empty"><div class="ico">{!! ic('lock') !!}</div>{{ __('Seuls les convives ayant réservé ce menu peuvent le noter.') }}</div>
@elseif ($tooEarly)
    <div class="empty"><div class="ico">{!! ic('clock') !!}</div>{{ __('Vous pourrez donner votre avis le jour du repas.') }}</div>
@elseif ($mine->count())
    <h3>{{ __('Mes avis') }}</h3>
    @foreach ($mine as $v)
        <div class="card flat mt"><div class="row between"><b class="small">{{ $v->dish ? $v->dish->name : __('Le menu dans son ensemble') }}</b>{!! stars($v->rating) !!}</div>@if ($v->comment)<p class="small mt">{{ $v->comment }}</p>@endif
            <span class="badge {{ $v->status === 'visible' ? 'green' : ($v->status === 'flagged' ? 'orange' : 'gray') }} mt">{{ $v->status === 'visible' ? __('Publié') : ($v->status === 'flagged' ? __('En attente de vérification') : __('Masqué')) }}</span></div>
    @endforeach
@else
<form method="POST" action="{{ route('reviews.store', $menu) }}" id="rv">
    @csrf
    <p class="small muted">{{ __('Notez les plats que vous avez pris, le menu dans son ensemble, ou les deux.') }}</p>
    <div class="cat-label">{{ __('Les plats') }}</div>
    @foreach ($res->items as $i)
        <div class="card flat mb"><div class="row"><x-dish-img :dish="$i->dish" size="sm" /><div class="grow"><b class="small">{{ $i->dish->name }}</b><div class="xs muted">{{ __($i->dish->categoryLabel()) }}</div></div></div>
            @include('partials.rate', ['name' => 'dish['.$i->dish_id.'][rating]'])
            <textarea class="input mt hidden" name="dish[{{ $i->dish_id }}][comment]" rows="2" placeholder="{{ __('Commentaire (facultatif)') }}"></textarea>
        </div>
    @endforeach
    <div class="cat-label">{{ __('Le menu dans son ensemble') }}</div>
    <div class="card flat mb">
        @include('partials.rate', ['name' => 'menu_rating'])
        <textarea class="input mt" name="menu_comment" rows="2" placeholder="{{ __('Commentaire (facultatif)') }}"></textarea>
        <div class="chips mt">@foreach (['Délicieux', 'Trop salé', 'Portion petite', 'Bien chaud', 'Trop pimenté'] as $t)<label class="chip"><input type="checkbox" name="tags[]" value="{{ $t }}" class="hidden" onchange="this.parentNode.classList.toggle('active',this.checked)">{{ __($t) }}</label>@endforeach</div>
    </div>
    <div class="cat-label">{{ __('Avez-vous tout mangé ?') }}</div>
    <div class="slots" style="grid-template-columns:repeat(3,1fr)">
        @foreach (['tout' => ['smile', 'Tout'], 'moitie' => ['meh', 'La moitié'], 'peu' => ['frown', 'Peu']] as $k => [$icon, $l])
            <label class="slot"><input type="radio" name="ate_all" value="{{ $k }}">{!! ic($icon) !!}<span class="xs">{{ __($l) }}</span></label>
        @endforeach
    </div>
    <div class="sticky-cta row"><a class="btn ghost" href="{{ route('user.home') }}">{{ __('Plus tard') }}</a><button class="btn grow" id="rv-btn" disabled>{{ __('Publier mon avis') }}</button></div>
</form>
@push('scripts')
<script>
document.querySelectorAll('.rate-stars').forEach((box) => {
  const input = box.querySelector('input');
  box.querySelectorAll('button').forEach((b) => b.addEventListener('click', (e) => {
    e.preventDefault();
    const v = +b.dataset.v; input.value = +input.value === v ? '' : v;
    box.querySelectorAll('button').forEach((x) => x.classList.toggle('on', +x.dataset.v <= +input.value));
    const ta = box.parentNode.querySelector('textarea.hidden, textarea[data-was-hidden]');
    if (ta) { ta.dataset.wasHidden = 1; ta.classList.toggle('hidden', !input.value); }
    const n = [...document.querySelectorAll('.rate-stars input')].filter((i) => i.value).length;
    const btn = document.getElementById('rv-btn'); btn.disabled = !n; btn.textContent = n > 1 ? @json(__('Publier')) + ' ' + n + ' ' + @json(__('avis')) : @json(__('Publier mon avis'));
  }));
});
</script>
@endpush
@endif
@endsection
