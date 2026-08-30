@props([
    'reading' => null,
    'title',
    'lead' => null,
    'center' => false,
    'level' => 'h2',
])
{{--
    節の見出し。和文の見出しに小さな欧文の読みを添える、和食店のサイトで
    よく使われる形。欧文は装飾なので aria-hidden にして読み上げから外す。
--}}
<div {{ $attributes->class(['heading', 'heading--center' => $center]) }} data-reveal>
    @if ($reading)
        <span class="heading__reading" aria-hidden="true">{{ $reading }}</span>
    @endif

    <{{ $level }} class="heading__title">{{ $title }}</{{ $level }}>

    @if ($lead)
        <p class="heading__lead">{{ $lead }}</p>
    @endif
</div>
