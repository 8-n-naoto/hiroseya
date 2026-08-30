@php
    $site = app(\App\Support\SiteContext::class);
    $store = $site->store();
    $nav = $site->navigation();
@endphp

<header class="site-header">
    <div class="wrap">
        <div class="site-header__bar">
            <a href="{{ route('home') }}" class="brand" aria-label="{{ $site->siteName() }} トップページへ">
                <span class="brand__name">{{ $site->siteName() }}</span>
                <span class="brand__reading" aria-hidden="true">{{ $site->siteNameEn() }}</span>
                @if ($store->catch_copy)
                    <span class="brand__tagline">{{ $store->catch_copy }}</span>
                @endif
            </a>

            <nav class="gnav" aria-label="サイト内メニュー">
                @foreach ($nav as $item)
                    <a href="{{ route($item['route']) }}" class="gnav__link"
                        @if (request()->routeIs($item['route']) || request()->routeIs(\Illuminate\Support\Str::before($item['route'], '.').'.*'))
                            aria-current="page"
                        @endif>
                        {{ $item['label'] }}
                        <span aria-hidden="true">{{ $item['reading'] }}</span>
                    </a>
                @endforeach
            </nav>

            @if ($store->tel)
                <div class="header-tel">
                    <a href="{{ $store->telLink() }}" class="header-tel__number">{{ $store->tel }}</a>
                    <span class="header-tel__note">ご予約・お問い合わせ</span>
                </div>
            @endif

            <button type="button" class="nav-toggle" data-nav-toggle aria-expanded="false"
                aria-controls="site-drawer" aria-label="メニューを開く">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>

    {{-- スマートフォン用。JS が動かない環境でも、フッターに同じ導線がある。 --}}
    <div class="drawer" id="site-drawer" data-nav-drawer data-open="false">
        <ul class="drawer__list">
            @foreach ($nav as $item)
                <li>
                    <a href="{{ route($item['route']) }}">
                        {{ $item['label'] }}
                        <span aria-hidden="true">{{ $item['reading'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>

        @if ($store->tel)
            <div class="drawer__foot">
                <a href="{{ $store->telLink() }}" class="btn btn--block">お電話する　{{ $store->tel }}</a>
            </div>
        @endif
    </div>
</header>
