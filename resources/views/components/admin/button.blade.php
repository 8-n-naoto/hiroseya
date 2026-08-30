@props(['variant' => 'primary', 'as' => 'button', 'href' => null])
@php
    $classes = [
        'inline-flex items-center justify-center gap-2 rounded-md px-4 py-2 text-sm font-medium transition',
        'primary' => 'bg-stone-800 text-white hover:bg-stone-700',
        'secondary' => 'border border-stone-300 bg-white text-stone-700 hover:bg-stone-50',
        'danger' => 'border border-red-300 bg-white text-red-700 hover:bg-red-50',
    ];
    $class = $classes[0].' '.($classes[$variant] ?? $classes['primary']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($class) }}>{{ $slot }}</a>
@else
    <button type="{{ $attributes->get('type', 'submit') }}" {{ $attributes->class($class) }}>{{ $slot }}</button>
@endif
