@php
    /** @var \App\Support\SiteContext $site */
    $site = app(\App\Support\SiteContext::class);
    /** @var \App\Support\Seo $seo */
    $seo = app(\App\Support\Seo::class);
    /** @var \App\Support\StructuredData $schema */
    $schema = app(\App\Support\StructuredData::class);

    $store = $site->store();

    // 構造化データ。店舗（Restaurant）とサイト（WebSite）は全ページ共通で、
    // パンくずとページ固有のもの（Menu / Article / Event）だけが増える。
    $schemas = array_values(array_filter(array_merge(
        [$schema->restaurant($store), $schema->website($store)],
        [$schema->breadcrumbs($seo->breadcrumbItems())],
        $seo->extraSchemas(),
    )));

    $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP;
@endphp
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $seo->metaTitle() }}</title>
    <meta name="description" content="{{ $seo->metaDescription() }}">
    @if ($seo->metaKeywords())
        <meta name="keywords" content="{{ $seo->metaKeywords() }}">
    @endif
    <meta name="robots" content="{{ $seo->metaRobots() }}">
    <link rel="canonical" href="{{ $seo->canonicalUrl() }}">

    {{-- Open Graph / X。SNSでの共有時に、意図した見出しと写真が出るようにする。 --}}
    <meta property="og:type" content="{{ $seo->ogType() }}">
    <meta property="og:site_name" content="{{ $site->siteName() }}">
    <meta property="og:locale" content="ja_JP">
    <meta property="og:title" content="{{ $seo->ogTitle() }}">
    <meta property="og:description" content="{{ $seo->ogDescription() }}">
    <meta property="og:url" content="{{ $seo->canonicalUrl() }}">
    @if ($seo->ogImageUrl())
        <meta property="og:image" content="{{ $seo->ogImageUrl() }}">
        <meta name="twitter:card" content="summary_large_image">
    @else
        <meta name="twitter:card" content="summary">
    @endif

    @if ($site->searchConsoleTag())
        <meta name="google-site-verification" content="{{ $site->searchConsoleTag() }}">
    @endif

    <meta name="format-detection" content="telephone=no">
    <meta name="theme-color" content="#26333f">

    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    {{-- 和文は OS 標準の書体で組むため、Webフォントの取得は行わない。 --}}
    @vite(['resources/css/site.css', 'resources/js/site.js'])

    @foreach ($schemas as $data)
        <script type="application/ld+json">{!! json_encode($data, $jsonFlags) !!}</script>
    @endforeach

    @if ($site->analyticsId())
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $site->analyticsId() }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '{{ $site->analyticsId() }}');
        </script>
    @endif

    @stack('head')
</head>
<body>
    <a href="#main" class="skip-link">本文へ移動</a>

    <x-site.header />

    <x-site.crumbs />

    <main id="main">
        {{ $slot }}
    </main>

    <x-site.footer />
</body>
</html>
