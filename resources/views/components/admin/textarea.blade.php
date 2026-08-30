<textarea {{ $attributes->class([
    'block w-full rounded-md border border-stone-300 bg-white px-3 py-2 text-sm leading-relaxed shadow-sm',
    'focus:border-stone-500 focus:ring-stone-500',
])->merge(['rows' => 5]) }}>{{ $slot }}</textarea>
