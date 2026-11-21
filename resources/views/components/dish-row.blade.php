@props(['dish', 'user' => null, 'left' => null, 'href' => null, 'selected' => false, 'qty' => null, 'price' => null])
@php
    $conf = $user ? $user->conflictsWith($dish) : [];
    $soldOut = $left !== null && $left <= 0;
    $tag = $href ? 'a' : 'div';
@endphp
<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => 'dish-row'.($soldOut ? ' disabled' : '').($selected ? ' selected' : '')]) }} style="color:inherit">
    <x-dish-img :dish="$dish" size="md" />
    <div class="info">
        <h4>{{ $dish->name }}</h4>
        <div class="xs">{!! stars($dish->avg_rating ?? null) !!}</div>
        <div class="row wrap mt" style="gap:4px">
            @foreach ($dish->allergens ?? [] as $a)
                <span class="badge {{ in_array($a, $conf, true) ? 'red' : 'gray' }} xs">@if (in_array($a, $conf, true)){!! ic('alert') !!}@endif {{ allergen_label($a) }}</span>
            @endforeach
            @if (in_array('vegetarien', $dish->diets ?? [], true))<span class="badge green xs">{!! ic('leaf') !!} {{ __('Végétarien') }}</span>@endif
        </div>
        @if ($conf)<div class="xs mt" style="color:var(--red);font-weight:600">{{ __('Incompatible avec votre profil') }}</div>@endif
        @if ($soldOut)<div class="xs mt" style="color:var(--red);font-weight:700">{{ __('Épuisé') }}</div>
        @elseif ($left !== null && $left <= 15)<div class="xs mt" style="color:var(--orange);font-weight:700">{{ __('Plus que :n portions', ['n' => $left]) }}</div>@endif
    </div>
    <div class="price">
        @if ($price && $price < $dish->price)<s class="xs muted">{{ fcfa($dish->price) }}</s><br><span style="color:var(--green)">{{ fcfa($price) }}</span>
        @else {{ fcfa($dish->price) }} @endif
        {{ $slot }}
    </div>
</{{ $tag }}>
