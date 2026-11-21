@props(['dish', 'size' => ''])
@if ($dish?->image_path)
    <div {{ $attributes->merge(['class' => 'dish-img '.$size]) }}><img src="{{ $dish->imageUrl() }}" alt="{{ $dish->name }}" loading="lazy"></div>
@else
    <div {{ $attributes->merge(['class' => 'dish-img ph '.$size]) }} title="{{ $dish?->categoryLabel() }}">{!! ic($dish?->icon() ?? 'utensils') !!}</div>
@endif
