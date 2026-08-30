@if ($recommended->isNotEmpty())
    <section class="section">
        <div class="wrap">
            <x-site.heading reading="Recommend" :title="$section->title ?: 'おすすめのお品書き'"
                :lead="$section->body" center />

            <div class="card-grid card-grid--3">
                @foreach ($recommended as $dish)
                    @php $variant = $dish->defaultVariant(); @endphp
                    <article class="card" data-reveal>
                        @if ($dish->has_detail_page)
                            <a href="{{ route('menu.show', $dish->slug) }}">
                        @endif

                        <div class="card__media">
                            <x-site.picture :media="$dish->mainImage" :alt="$dish->mainImage?->alt ?: $dish->name"
                                sizes="(min-width: 940px) 32vw, (min-width: 640px) 48vw, 100vw" />
                        </div>

                        <h3 class="card__title">{{ $dish->name }}</h3>

                        @if ($variant)
                            <p class="card__price">{{ $variant->formattedPrice() }}<span
                                style="font-size:11px;color:var(--ink-faint);letter-spacing:.1em;">（税込）</span></p>
                        @endif

                        @if ($dish->description)
                            <p class="card__excerpt">{{ \Illuminate\Support\Str::limit($dish->description, 60) }}</p>
                        @endif

                        @if ($dish->has_detail_page)
                            </a>
                        @endif
                    </article>
                @endforeach
            </div>

            <p style="margin-top:48px;text-align:center;">
                <a href="{{ route('menu.index') }}" class="btn">お品書きをすべて見る</a>
            </p>
        </div>
    </section>
@endif
