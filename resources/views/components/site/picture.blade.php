@props([
    'media' => null,
    'alt' => null,
    'size' => 'md',
    'sizes' => '100vw',
    'loading' => 'lazy',
    'ratio' => null,
    'placeholder' => true,
])
{{--
    公開サイトの画像。

    ImageService が lg/md/sm の WebP と md の JPEG を作っているので、
    ここでは <picture> で WebP を優先し、非対応の環境には JPEG を返す。
    幅と高さを必ず出して、読み込み中にレイアウトがずれない（CLS）ようにする。

    画像が未設定でも崩れないよう、同じ比率の空枠を出す。
    公開前は写真が入っていない項目が必ずあるため。
--}}
@php
    $altText = $alt ?? $media?->alt ?? '';
    $style = $ratio ? "aspect-ratio: {$ratio};" : null;
@endphp

@if ($media)
    <picture>
        <source
            type="image/webp"
            srcset="{{ $media->variantUrl('sm') }} 400w, {{ $media->variantUrl('md') }} 800w, {{ $media->variantUrl('lg') }} 1600w"
            sizes="{{ $sizes }}">
        <img
            src="{{ $media->variantUrl('md', 'jpg') }}"
            alt="{{ $altText }}"
            @if ($media->width) width="{{ $media->width }}" @endif
            @if ($media->height) height="{{ $media->height }}" @endif
            loading="{{ $loading }}"
            decoding="{{ $loading === 'eager' ? 'sync' : 'async' }}"
            @if ($loading === 'eager') fetchpriority="high" @endif
            @if ($style) style="{{ $style }}" @endif
            {{ $attributes }}>
    </picture>
@elseif ($placeholder)
    <div
        {{ $attributes->merge(['class' => 'hero__media--empty']) }}
        style="{{ $style ?: 'aspect-ratio: 4 / 3;' }}"
        role="presentation"
        aria-hidden="true"></div>
@endif
