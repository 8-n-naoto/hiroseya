<x-layouts.site>
    <div class="section">
        <div class="wrap wrap--narrow">
            @if ($isPreview)
                <p class="alert alert--error">このお知らせは下書き（または公開予約中）です。ログイン中の方にのみ表示されています。</p>
            @endif

            <article>
                <header class="article-head">
                    <p class="article-head__meta">
                        <time datetime="{{ $news->published_at?->toDateString() }}">
                            {{ $news->published_at?->format('Y年n月j日') }}
                        </time>
                    </p>
                    <h1 class="article-head__title">{{ $news->title }}</h1>
                </header>

                @if ($news->mainImage)
                    <figure class="article-figure">
                        <x-site.picture :media="$news->mainImage" :alt="$news->mainImage->alt ?: $news->title"
                            sizes="(min-width: 760px) 760px, 100vw" loading="eager" />
                    </figure>
                @endif

                <div class="prose">{!! nl2br(e($news->body)) !!}</div>
            </article>

            <nav class="article-nav">
                <span>@if ($prev)<a href="{{ route('news.show', $prev->slug) }}">前のお知らせ</a>@endif</span>
                <a href="{{ route('news.index') }}" class="textlink">お知らせ一覧</a>
                <span>@if ($next)<a href="{{ route('news.show', $next->slug) }}">次のお知らせ</a>@endif</span>
            </nav>
        </div>
    </div>
</x-layouts.site>
