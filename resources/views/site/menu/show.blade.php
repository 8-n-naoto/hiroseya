@php use App\Enums\ServiceType; @endphp

<x-layouts.site>
    <div class="section">
        <div class="wrap wrap--narrow">
            @if ($isPreview)
                <p class="alert alert--error">このページは下書き（または提供期間外）です。ログイン中の方にのみ表示されています。</p>
            @endif

            <article>
                <header class="article-head">
                    <p class="article-head__meta">
                        @if ($dish->category)
                            <a href="{{ route('menu.index') }}#cat-{{ $dish->category->slug }}">{{ $dish->category->name }}</a>
                        @endif
                        @if ($dish->is_limited)<span style="color:var(--accent);">期間限定</span>@endif
                        @if ($dish->is_sold_out)<span>品切れ</span>@endif
                    </p>
                    <h1 class="article-head__title">{{ $dish->name }}</h1>
                    @if ($dish->name_kana)
                        <p style="margin-top:8px;font-size:12px;letter-spacing:.16em;color:var(--ink-faint);">{{ $dish->name_kana }}</p>
                    @endif
                </header>

                @if ($dish->mainImage)
                    <figure class="article-figure">
                        <x-site.picture :media="$dish->mainImage" :alt="$dish->mainImage->alt ?: $dish->name"
                            sizes="(min-width: 760px) 760px, 100vw" loading="eager" />
                        @if ($dish->mainImage->caption)
                            <figcaption style="margin-top:10px;font-size:12.5px;color:var(--ink-faint);">
                                {{ $dish->mainImage->caption }}
                            </figcaption>
                        @endif
                    </figure>
                @endif

                {{-- 価格。提供区分ごとに分けて出す（店内と持ち帰りで価格が違う品がある）。 --}}
                <div style="border-top:1px solid var(--rule);border-bottom:1px solid var(--rule);padding:8px 0;margin-bottom:36px;">
                    @foreach ([ServiceType::DineIn, ServiceType::Takeout] as $type)
                        @php $variants = $dish->variantsFor($type); @endphp
                        @continue($variants->isEmpty())

                        <div style="padding:14px 0;{{ ! $loop->first ? 'border-top:1px dotted var(--rule-strong);' : '' }}">
                            <p style="font-size:11px;letter-spacing:.2em;color:var(--ink-faint);margin-bottom:8px;">
                                {{ $type->label() }}
                            </p>
                            <ul class="menu-row__variants" style="margin-top:0;">
                                @foreach ($variants as $variant)
                                    <li>
                                        <span style="font-family:var(--font-mincho);font-size:16px;color:var(--ink);">
                                            {{ $variant->label ?: $dish->name }}
                                        </span>
                                        <span class="menu-row__leader" aria-hidden="true"></span>
                                        <span class="menu-row__price" style="font-size:17px;color:var(--ink);">
                                            {{ $variant->formattedPrice() }}
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>

                @if ($dish->description)
                    <p class="prose" style="font-size:16px;color:var(--ink);">{{ $dish->description }}</p>
                @endif

                @if ($dish->body)
                    <div class="prose" style="margin-top:24px;">{!! nl2br(e($dish->body)) !!}</div>
                @endif

                @if ($dish->images->isNotEmpty())
                    <div class="card-grid card-grid--3" style="margin-top:44px;">
                        @foreach ($dish->images as $image)
                            <x-site.picture :media="$image" :alt="$image->alt ?: $dish->name"
                                sizes="(min-width: 940px) 240px, 45vw" ratio="4 / 3" />
                        @endforeach
                    </div>
                @endif

                @if ($dish->allergens->isNotEmpty())
                    <div class="menu-note" style="margin-top:40px;">
                        <p><strong>アレルギー（特定原材料8品目）</strong>　{{ $dish->allergens->pluck('name')->implode('・') }}</p>
                        <p style="margin-top:8px;">同一の厨房で調理しているため、微量の混入の可能性がございます。詳しくは店頭でおたずねください。</p>
                    </div>
                @endif

                @if ($dish->available_from || $dish->available_to)
                    <p style="margin-top:24px;font-size:13px;color:var(--ink-faint);">
                        提供期間　{{ $dish->available_from?->format('Y年n月j日') ?: '' }}〜{{ $dish->available_to?->format('Y年n月j日') ?: '' }}
                    </p>
                @endif
            </article>

            @if ($related->isNotEmpty())
                <section style="margin-top:64px;">
                    <x-site.heading reading="Other" title="同じ分類のお品書き" level="h2" />
                    <div class="card-grid card-grid--3">
                        @foreach ($related as $item)
                            @php $variant = $item->defaultVariant(); @endphp
                            <article class="card">
                                @if ($item->has_detail_page)<a href="{{ route('menu.show', $item->slug) }}">@endif
                                <div class="card__media">
                                    <x-site.picture :media="$item->mainImage" :alt="$item->name" sizes="240px" />
                                </div>
                                <h3 class="card__title">{{ $item->name }}</h3>
                                @if ($variant)<p class="card__price">{{ $variant->formattedPrice() }}</p>@endif
                                @if ($item->has_detail_page)</a>@endif
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif

            <nav class="article-nav">
                <a href="{{ route('menu.index') }}" class="textlink">お品書きへ戻る</a>
            </nav>
        </div>
    </div>
</x-layouts.site>
