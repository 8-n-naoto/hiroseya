@props(['label' => null, 'for' => null, 'name' => null, 'help' => null, 'required' => false])
{{--
    入力欄 1 つ分の枠。ラベル・補足・エラーの出し方をここに集約して、
    画面ごとに書き方が変わらないようにしている。
--}}
@php $errorKey = $name ?? $for; @endphp

<div {{ $attributes->class(['space-y-1.5']) }}>
    @if ($label)
        <label @if ($for) for="{{ $for }}" @endif class="flex items-center gap-2 text-sm font-medium text-stone-700">
            {{ $label }}
            @if ($required)
                <span class="rounded bg-red-100 px-1.5 py-0.5 text-[10px] font-semibold text-red-700">必須</span>
            @endif
        </label>
    @endif

    {{ $slot }}

    @if ($help)
        <p class="text-xs leading-relaxed text-stone-500">{!! $help !!}</p>
    @endif

    @if ($errorKey)
        @error($errorKey)
            <p class="text-xs text-red-600">{{ $message }}</p>
        @enderror
    @endif
</div>
