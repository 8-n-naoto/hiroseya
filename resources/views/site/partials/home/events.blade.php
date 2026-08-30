@if ($events->isNotEmpty())
    <section class="section">
        <div class="wrap">
            <x-site.heading reading="Event" :title="$section->title ?: 'イベント'" center />

            <div class="card-grid card-grid--2">
                @foreach ($events as $event)
                    <article class="card" data-reveal>
                        <a href="{{ route('events.show', $event->slug) }}">
                            <div class="card__media">
                                <x-site.picture :media="$event->mainImage" :alt="$event->mainImage?->alt ?: $event->title"
                                    sizes="(min-width: 640px) 48vw, 100vw" />
                            </div>
                            <p class="card__meta">
                                @if ($event->periodLabel())<span>{{ $event->periodLabel() }}</span>@endif
                                @if ($event->isOngoing())<span style="color:var(--accent);">開催中</span>@endif
                            </p>
                            <h3 class="card__title">{{ $event->title }}</h3>
                            @if ($event->excerpt)
                                <p class="card__excerpt">{{ \Illuminate\Support\Str::limit($event->excerpt, 80) }}</p>
                            @endif
                        </a>
                    </article>
                @endforeach
            </div>

            <p style="margin-top:44px;text-align:center;">
                <a href="{{ route('events.index') }}" class="textlink">イベント一覧</a>
            </p>
        </div>
    </section>
@endif
