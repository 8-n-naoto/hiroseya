@php
    $site = app(\App\Support\SiteContext::class);
    $settings = $site->settings();
    $store = $site->store();
@endphp

@if ($settings->mapEnabled() && $settings->string('access.map_embed') !== '')
    <section class="section">
        <div class="wrap">
            <x-site.heading reading="Access" :title="$section->title ?: 'アクセス'" :lead="$section->body" center />

            <div class="map-frame" data-reveal>
                {{-- Googleマップの埋め込みコードは管理者のみが入力する設定値。 --}}
                {!! $settings->string('access.map_embed') !!}
            </div>

            <p style="margin-top:24px;text-align:center;font-size:14px;color:var(--ink-soft);">
                {{ $store->formattedPostalCode() }} {{ $store->fullAddress() }}
                @if ($settings->string('access.map_link') !== '')
                    <br>
                    <a href="{{ $settings->string('access.map_link') }}" class="textlink" target="_blank"
                        rel="noopener noreferrer" style="margin-top:14px;">地図アプリで開く</a>
                @endif
            </p>
        </div>
    </section>
@endif
