@props(['title', 'description' => null])
<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
        <h2 class="text-lg font-semibold text-stone-900">{{ $title }}</h2>
        @if ($description)
            <p class="mt-1 max-w-3xl text-sm leading-relaxed text-stone-500">{{ $description }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
