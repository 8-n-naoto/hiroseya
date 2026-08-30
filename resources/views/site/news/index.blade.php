<x-layouts.site>
    <x-site.page-head reading="News" title="お知らせ"
        lead="新商品・期間限定のお品書き・臨時休業などをお知らせいたします。" />

    <div class="section">
        <div class="wrap wrap--narrow">
            @if ($items->isEmpty())
                <p class="empty-state">お知らせはまだありません。</p>
            @else
                <ul class="news-list">
                    @foreach ($items as $item)
                        <li class="news-list__item">
                            <a href="{{ route('news.show', $item->slug) }}" class="news-list__link">
                                <time class="news-list__date" datetime="{{ $item->published_at?->toDateString() }}">
                                    {{ $item->published_at?->format('Y.n.j') }}
                                </time>
                                <span>
                                    <span class="news-list__title">{{ $item->title }}</span>
                                    @if ($item->excerpt)
                                        <span style="display:block;margin-top:6px;font-size:13px;color:var(--ink-soft);">
                                            {{ \Illuminate\Support\Str::limit($item->excerpt, 90) }}
                                        </span>
                                    @endif
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>

                {{ $items->links('vendor.pagination.site') }}
            @endif
        </div>
    </div>
</x-layouts.site>
