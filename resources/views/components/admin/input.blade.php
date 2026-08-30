@props(['type' => 'text'])
<input type="{{ $type }}"
    {{ $attributes->class([
        'block w-full rounded-md border-stone-300 bg-white px-3 py-2 text-sm shadow-sm',
        'focus:border-stone-500 focus:ring-stone-500',
        'border',
    ]) }}>
