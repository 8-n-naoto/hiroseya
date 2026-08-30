@if ($news->isNotEmpty())
    <section class="section section--tint">
        <div class="wrap wrap--narrow">
            <x-site.heading reading="News" :title="$section->title ?: 'お知らせ'" />

            <ul class="news-list">
                @foreach ($news as $item)
                    <li class="news-list__item">
                        <a href="{{ route('news.show', $item->slug) }}" class="news-list__link">
                            <time class="news-list__date" datetime="{{ $item->published_at?->toDateString() }}">
                                {{ $item->published_at?->format('Y.n.j') }}
                            </time>
                            <span class="news-list__title">{{ $item->title }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>

            <p style="margin-top:36px;text-align:right;">
                <a href="{{ route('news.index') }}" class="textlink">お知らせ一覧</a>
            </p>
        </div>
    </section>
@endif
