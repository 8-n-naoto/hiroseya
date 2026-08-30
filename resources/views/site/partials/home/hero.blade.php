@php
    $site = app(\App\Support\SiteContext::class);
    $store = $site->store();
    $catch = $section->title ?: $store->catch_copy;
@endphp

<section class="hero" aria-label="{{ $site->siteName() }}">
    <div class="hero__media">
        {{-- スマートフォンでは縦長の写真に差し替える。横長を切り抜くと料理が切れる。 --}}
        @if ($section->image || $section->imageSp)
            <picture>
                @if ($section->imageSp)
                    <source media="(max-width: 699px)" type="image/webp"
                        srcset="{{ $section->imageSp->variantUrl('md') }} 800w, {{ $section->imageSp->variantUrl('lg') }} 1600w"
                        sizes="100vw">
                @endif
                @if ($section->image)
                    <source type="image/webp"
                        srcset="{{ $section->image->variantUrl('md') }} 800w, {{ $section->image->variantUrl('lg') }} 1600w"
                        sizes="100vw">
                @endif
                <img src="{{ ($section->image ?? $section->imageSp)->variantUrl('md', 'jpg') }}"
                    alt="{{ ($section->image ?? $section->imageSp)->alt ?: $store->name.'の店内' }}"
                    loading="eager" decoding="sync" fetchpriority="high">
            </picture>
        @else
            <div class="hero__media--empty" style="width:100%;height:100%;" aria-hidden="true"></div>
        @endif

        <div class="hero__veil" aria-hidden="true"></div>

        <div class="hero__inner">
            <div class="wrap">
                <div class="hero__lockup">
                    <h1 class="hero__catch">{!! nl2br(e($catch)) !!}</h1>
                    @if ($section->subtitle)
                        <p class="hero__sub">{{ $section->subtitle }}</p>
                    @else
                        <p class="hero__sub">{{ $store->prefecture }}{{ $store->city }}　うどん・そば処</p>
                    @endif
                </div>
            </div>
        </div>

        <p class="hero__vertical" aria-hidden="true">{{ $site->siteName() }}</p>
    </div>
</section>
