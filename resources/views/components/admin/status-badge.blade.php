@props(['tone' => 'muted', 'label'])
@php
    $tones = [
        'alert' => 'bg-red-100 text-red-700',
        'notice' => 'bg-amber-100 text-amber-800',
        'ok' => 'bg-green-100 text-green-700',
        'muted' => 'bg-stone-100 text-stone-600',
    ];
@endphp
<span {{ $attributes->class(['inline-block rounded px-2 py-0.5 text-xs font-medium', $tones[$tone] ?? $tones['muted']]) }}>
    {{ $label }}
</span>
