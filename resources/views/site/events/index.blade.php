<x-layouts.site>
    <x-site.page-head reading="Event" title="イベント"
        lead="季節のご案内やキャンペーンのお知らせです。" />

    <div class="section">
        <div class="wrap">
            @if ($ongoing->isEmpty() && $finished->isEmpty())
                <p class="empty-state">現在ご案内しているイベントはありません。</p>
            @endif

            @if ($ongoing->isNotEmpty())
                <x-site.heading reading="Now" title="開催中" />
                <div class="card-grid card-grid--2">
                    @foreach ($ongoing as $event)
                        <article class="card" data-reveal>
                            <a href="{{ route('events.show', $event->slug) }}">
                                <div class="card__media">
                                    <x-site.picture :media="$event->mainImage" :alt="$event->title"
                                        sizes="(min-width: 640px) 48vw, 100vw" />
                                </div>
                                <p class="card__meta">{{ $event->periodLabel() }}</p>
                                <h3 class="card__title">{{ $event->title }}</h3>
                                @if ($event->excerpt)
                                    <p class="card__excerpt">{{ \Illuminate\Support\Str::limit($event->excerpt, 90) }}</p>
                                @endif
                            </a>
                        </article>
                    @endforeach
                </div>
            @endif

            @if ($finished->isNotEmpty())
                <div style="margin-top:{{ $ongoing->isNotEmpty() ? '80px' : '0' }};">
                    <x-site.heading reading="Archive" title="終了したイベント" />
                    <ul class="news-list">
                        @foreach ($finished as $event)
                            <li class="news-list__item">
                                <a href="{{ route('events.show', $event->slug) }}" class="news-list__link">
                                    <span class="news-list__date">{{ $event->periodLabel() }}</span>
                                    <span class="news-list__title">{{ $event->title }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</x-layouts.site>
