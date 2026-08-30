@props(['options' => [], 'selected' => null, 'placeholder' => null])
<select {{ $attributes->class([
    'block w-full rounded-md border border-stone-300 bg-white px-3 py-2 text-sm shadow-sm',
    'focus:border-stone-500 focus:ring-stone-500',
]) }}>
    @if ($placeholder !== null)
        <option value="">{{ $placeholder }}</option>
    @endif
    @foreach ($options as $value => $label)
        <option value="{{ $value }}" @selected((string) $value === (string) $selected)>{{ $label }}</option>
    @endforeach
</select>
