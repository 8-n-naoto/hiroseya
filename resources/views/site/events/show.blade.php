<x-layouts.site>
    <div class="section">
        <div class="wrap wrap--narrow">
            @if ($isPreview)
                <p class="alert alert--error">このイベントは下書きです。ログイン中の方にのみ表示されています。</p>
            @endif

            <article>
                <header class="article-head">
                    <p class="article-head__meta">
                        @if ($event->periodLabel())<span>{{ $event->periodLabel() }}</span>@endif
                        @if ($event->isOngoing())
                            <span style="color:var(--accent);">開催中</span>
                        @elseif ($event->ends_on)
                            <span>終了しました</span>
                        @endif
                    </p>
                    <h1 class="article-head__title">{{ $event->title }}</h1>
                </header>

                @if ($event->mainImage)
                    <figure class="article-figure">
                        <x-site.picture :media="$event->mainImage" :alt="$event->mainImage->alt ?: $event->title"
                            sizes="(min-width: 760px) 760px, 100vw" loading="eager" />
                    </figure>
                @endif

                <div class="prose">{!! nl2br(e($event->body)) !!}</div>
            </article>

            <nav class="article-nav">
                <a href="{{ route('events.index') }}" class="textlink">イベント一覧へ戻る</a>
            </nav>
        </div>
    </div>
</x-layouts.site>
