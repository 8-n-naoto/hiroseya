@php $links = app(\App\Support\SiteContext::class)->socialLinks(); @endphp

@if ($links->isNotEmpty())
    <section class="section">
        <div class="wrap wrap--narrow" style="text-align:center;">
            <x-site.heading reading="Social" :title="$section->title ?: '広瀬屋の最新情報'" :lead="$section->body" center />

            <ul class="social-list" style="justify-content:center;">
                @foreach ($links as $link)
                    <li>
                        <a href="{{ $link->url }}" target="_blank" rel="noopener noreferrer"
                            style="border-color:var(--rule-strong);color:var(--ink-soft);">
                            {{ $link->platformLabel() }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
@endif
