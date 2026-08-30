@props(['title' => null, 'description' => null, 'padding' => true])
<section {{ $attributes->class(['rounded-lg border border-stone-200 bg-white']) }}>
    @if ($title)
        <header class="border-b border-stone-200 px-5 py-4">
            <h2 class="text-sm font-semibold text-stone-800">{{ $title }}</h2>
            @if ($description)
                <p class="mt-1 text-xs leading-relaxed text-stone-500">{{ $description }}</p>
            @endif
            @isset($actions)
                <div class="mt-3">{{ $actions }}</div>
            @endisset
        </header>
    @endif

    <div @class(['px-5 py-5' => $padding])>{{ $slot }}</div>
</section>
