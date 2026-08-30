@php $store = app(\App\Support\SiteContext::class)->store(); @endphp

<section class="section section--tint">
    <div class="wrap">
        <div class="about" data-reveal>
            <div class="about__media">
                <x-site.picture :media="$section->image" :alt="$section->image?->alt ?: $store->name.'の店内'"
                    sizes="(min-width: 900px) 52vw, 100vw" ratio="4 / 3" />
            </div>

            <div>
                <x-site.heading reading="About" :title="$section->title ?: 'お店について'" :lead="$section->subtitle" />

                <div class="about__body stack">
                    @if ($section->body)
                        {!! nl2br(e($section->body)) !!}
                    @elseif ($store->description)
                        {!! nl2br(e($store->description)) !!}
                    @endif
                </div>

                <p style="margin-top:32px;">
                    <a href="{{ route('access') }}" class="textlink">アクセス・営業時間</a>
                </p>
            </div>
        </div>
    </div>
</section>
