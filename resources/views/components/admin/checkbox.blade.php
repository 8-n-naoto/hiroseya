@props(['label', 'name', 'checked' => false, 'value' => 1, 'help' => null])
{{--
    チェックボックス。未チェックだと何も送られないので、
    直前に同名の hidden を置いて「0」を必ず送る。
    これが無いと「チェックを外して保存」ができない。
--}}
<label class="flex items-start gap-3">
    <input type="hidden" name="{{ $name }}" value="0">
    <input type="checkbox" name="{{ $name }}" value="{{ $value }}" @checked($checked)
        {{ $attributes->class(['mt-0.5 h-4 w-4 rounded border-stone-300 text-stone-800 focus:ring-stone-500']) }}>
    <span>
        <span class="text-sm text-stone-700">{{ $label }}</span>
        @if ($help)
            <span class="mt-0.5 block text-xs leading-relaxed text-stone-500">{{ $help }}</span>
        @endif
    </span>
</label>
