@php
    /** @var \App\Support\Settings $settings */
    $settings = app(\App\Support\Settings::class);
    $user = auth()->user();
    $canSettings = $user?->canManageSettings() ?? false;

    /*
     * ナビは「毎日触るもの」「ときどき触るもの」「最初に一度決めるもの」の順。
     * 店舗の方が迷わないよう、機能名ではなく作業の名前で並べている。
     */
    $navGroups = array_filter([
        [
            'label' => null,
            'items' => [
                ['route' => 'admin.dashboard', 'label' => 'ダッシュボード'],
                ['route' => 'admin.contacts.index', 'label' => 'お問い合わせ', 'badge' => \App\Models\Contact::open()->count()],
            ],
        ],
        [
            'label' => 'ページの内容',
            'items' => [
                ['route' => 'admin.dishes.index', 'label' => 'お品書き（料理）'],
                ['route' => 'admin.dish-categories.index', 'label' => '料理の分類'],
                ['route' => 'admin.news.index', 'label' => 'お知らせ'],
                ['route' => 'admin.events.index', 'label' => 'イベント'],
                ['route' => 'admin.home-sections.index', 'label' => 'トップページ'],
                ['route' => 'admin.media.index', 'label' => '画像ライブラリ'],
            ],
        ],
        $canSettings ? [
            'label' => '店舗とサイトの設定',
            'items' => [
                ['route' => 'admin.store.edit', 'label' => '店舗情報'],
                ['route' => 'admin.business-hours.index', 'label' => '営業時間・臨時休業'],
                ['route' => 'admin.seo.index', 'label' => 'ページのSEO'],
                ['route' => 'admin.settings.edit', 'label' => '各種設定'],
                ['route' => 'admin.social-links.index', 'label' => 'SNS'],
                ['route' => 'admin.users.index', 'label' => 'ユーザー'],
                ['route' => 'admin.activity.index', 'label' => '操作ログ'],
            ],
        ] : null,
    ]);
@endphp
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ?? 'ダッシュボード' }} - {{ $settings->string('site.site_name', '広瀬屋') }} 管理画面</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-stone-100 text-stone-800 antialiased" x-data="{ sidebarOpen: false }">

    @if ($settings->inPreparation())
        <div class="bg-amber-100 px-4 py-2 text-center text-xs font-medium text-amber-900">
            準備中モードがONです。一般の訪問者には準備中ページが表示され、検索エンジンには登録されません。
            <a href="{{ route('admin.settings.edit', 'site') }}" class="underline">設定を開く</a>
        </div>
    @endif

    <div class="flex min-h-screen">
        {{-- モバイル用オーバーレイ --}}
        <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
            class="fixed inset-0 z-30 bg-stone-900/40 lg:hidden"></div>

        <aside
            class="fixed inset-y-0 left-0 z-40 w-64 transform overflow-y-auto border-r border-stone-200 bg-white transition-transform duration-200 ease-in-out lg:static lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
            <div class="flex h-16 items-center justify-between border-b border-stone-200 px-5">
                <a href="{{ route('admin.dashboard') }}" class="text-sm font-semibold tracking-wide text-stone-800">
                    {{ $settings->string('site.site_name', '広瀬屋') }} 管理画面
                </a>
                <button @click="sidebarOpen = false" class="text-stone-400 lg:hidden" aria-label="閉じる">✕</button>
            </div>

            <nav class="px-3 py-4">
                @foreach ($navGroups as $group)
                    @if ($group['label'])
                        <p class="mt-6 mb-2 px-3 text-[11px] font-semibold tracking-wider text-stone-400">
                            {{ $group['label'] }}
                        </p>
                    @endif

                    <div class="space-y-0.5">
                        @foreach ($group['items'] as $item)
                            @continue(! \Illuminate\Support\Facades\Route::has($item['route']))
                            @php
                                // 'admin.dishes.index' -> 'admin.dishes.*' で判定する。
                                // ダッシュボード（admin.dashboard）だけは前方一致にすると
                                // すべてのページで現在地扱いになるため、完全一致で見る。
                                $pattern = $item['route'] === 'admin.dashboard'
                                    ? 'admin.dashboard'
                                    : \Illuminate\Support\Str::beforeLast($item['route'], '.').'.*';
                            @endphp
                            <a href="{{ route($item['route']) }}"
                                class="flex items-center justify-between rounded-md px-3 py-2 text-sm transition
                                    {{ request()->routeIs($pattern) ? 'bg-stone-800 font-medium text-white' : 'text-stone-600 hover:bg-stone-100' }}">
                                <span>{{ $item['label'] }}</span>
                                @if (($item['badge'] ?? 0) > 0)
                                    <span class="rounded-full bg-red-500 px-2 py-0.5 text-[10px] font-semibold text-white">
                                        {{ $item['badge'] }}
                                    </span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                @endforeach

                <div class="mt-8 border-t border-stone-200 px-3 pt-4">
                    <a href="{{ route('home') }}" target="_blank" rel="noopener"
                        class="text-xs text-stone-500 underline hover:text-stone-700">
                        公開サイトを開く ↗
                    </a>
                </div>
            </nav>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-stone-200 bg-white px-4 lg:px-8">
                <button @click="sidebarOpen = true" class="text-stone-500 lg:hidden" aria-label="メニューを開く">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <h1 class="truncate text-base font-semibold text-stone-900">{{ $title ?? 'ダッシュボード' }}</h1>

                <div class="flex items-center gap-3 text-sm">
                    <span class="hidden text-stone-500 sm:inline">
                        {{ $user?->name }}（{{ $user?->roleLabel() }}）
                    </span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-stone-500 underline hover:text-stone-700">ログアウト</button>
                    </form>
                </div>
            </header>

            <main class="flex-1 px-4 py-6 lg:px-8">
                @if (session('status'))
                    <div class="mb-6 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                        {{ session('status') }}
                    </div>
                @endif

                @if (session('warning'))
                    <div class="mb-6 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                        {{ session('warning') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <p class="mb-1 font-medium">入力内容をご確認ください。</p>
                        <ul class="list-inside list-disc space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>

    @stack('modals')
</body>
</html>
