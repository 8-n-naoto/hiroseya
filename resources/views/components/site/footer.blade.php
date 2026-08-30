@php
    $site = app(\App\Support\SiteContext::class);
    $settings = $site->settings();
    $store = $site->store();
    $hours = $site->hours();
    $social = $site->socialLinks();
@endphp

<footer class="site-footer">
    <div class="wrap">
        <div class="footer-grid">
            {{-- 店舗情報。NAP（店名・住所・電話）は store_profile が唯一の出どころ。 --}}
            <div>
                <p class="footer-brand__name">{{ $site->siteName() }}</p>
                <address class="footer-address">
                    {{ $store->formattedPostalCode() }}<br>
                    {{ $store->fullAddress() }}
                </address>
                @if ($store->tel)
                    <a href="{{ $store->telLink() }}" class="footer-tel">{{ $store->tel }}</a>
                @endif
            </div>

            <div>
                <p class="footer-heading">営業時間</p>
                <ul class="footer-list">
                    @foreach ($hours->summarizedWeek() as $group)
                        <li>
                            {{ $group['days'] }}
                            @foreach ($group['ranges'] as $range)
                                <br><span style="color:#a9a294;">{{ $range['label'] ? $range['label'].'　' : '' }}{{ $range['range'] }}</span>
                            @endforeach
                        </li>
                    @endforeach
                    <li>定休日　{{ $hours->closedDaysLabel() }}</li>
                </ul>
            </div>

            <div>
                <p class="footer-heading">メニュー</p>
                <ul class="footer-list">
                    @foreach ($site->navigation() as $item)
                        <li><a href="{{ route($item['route']) }}">{{ $item['label'] }}</a></li>
                    @endforeach
                    <li><a href="{{ route('menu.takeout') }}">お持ち帰り</a></li>
                    <li><a href="{{ route('privacy') }}">プライバシーポリシー</a></li>
                </ul>

                @if ($social->isNotEmpty())
                    <ul class="social-list">
                        @foreach ($social as $link)
                            <li>
                                <a href="{{ $link->url }}" target="_blank" rel="noopener noreferrer">
                                    {{ $link->platformLabel() }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if ($settings->bool('seo.gbp_enabled') && $settings->string('seo.gbp_url') !== '')
                    <ul class="social-list">
                        <li>
                            <a href="{{ $settings->string('seo.gbp_url') }}" target="_blank" rel="noopener noreferrer">
                                Googleビジネスプロフィール
                            </a>
                        </li>
                    </ul>
                @endif
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; {{ now()->year }} {{ $store->name }}</p>
            <p>{{ $store->fullAddress() }}　{{ $store->tel }}</p>
        </div>
    </div>
</footer>

{{-- 画面下の固定導線。スマートフォンのみ。 --}}
<div class="mobile-cta">
    <a href="{{ route('menu.index') }}">お品書き</a>
    @if ($store->tel)
        <a href="{{ $store->telLink() }}">お電話する</a>
    @else
        <a href="{{ route('contact.create') }}">お問い合わせ</a>
    @endif
</div>
