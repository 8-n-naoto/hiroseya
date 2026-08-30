@php
    /** @var \App\Support\Settings $settings */
    $settings = app(\App\Support\Settings::class);
    $user = auth()->user();

    $navItems = [
        ['route' => 'admin.dashboard', 'label' => 'ダッシュボード'],
        ['route' => 'admin.media.index', 'label' => '画像ライブラリ'],
    ];
@endphp
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ?? 'ダッシュボード' }} - {{ $settings->string('site.site_name', '広瀬屋') }} 管理画面</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-stone-50 text-stone-800 antialiased" x-data="{ sidebarOpen: false }">

    @if ($settings->inPreparation())
        <div class="bg-amber-100 px-4 py-2 text-center text-xs font-medium text-amber-800">
            準備中モードがONです。一般の訪問者には準備中ページが表示されています。
        </div>
    @endif

    <div class="flex min-h-screen">
        {{-- モバイル用オーバーレイ --}}
        <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
            class="fixed inset-0 z-30 bg-stone-900/40 lg:hidden"></div>

        {{-- サイドバー --}}
        <aside
            class="fixed inset-y-0 left-0 z-40 w-64 transform border-r border-stone-200 bg-white transition-transform duration-200 ease-in-out lg:static lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
            <div class="flex h-16 items-center justify-between border-b border-stone-200 px-5">
                <span class="text-sm font-semibold tracking-wide text-stone-800">
                    {{ $settings->string('site.site_name', '広瀬屋') }} 管理画面
                </span>
                <button @click="sidebarOpen = false" class="text-stone-400 lg:hidden" aria-label="閉じる">✕</button>
            </div>

            <nav class="space-y-1 px-3 py-4">
                @foreach ($navItems as $item)
                    <a href="{{ route($item['route']) }}"
                        class="block rounded-md px-3 py-2 text-sm font-medium transition
                            {{ request()->routeIs($item['route'].'*') ? 'bg-stone-800 text-white' : 'text-stone-600 hover:bg-stone-100' }}">
                        {{ $item['label'] }}
                    </a>
                @endforeach

                <p class="mt-6 px-3 text-xs text-stone-400">
                    料理・お知らせ・予約・設定などの画面は今後追加していきます。
                </p>
            </nav>
        </aside>

        {{-- メイン --}}
        <div class="flex min-w-0 flex-1 flex-col lg:pl-0">
            <header class="flex h-16 items-center justify-between border-b border-stone-200 bg-white px-4 lg:px-8">
                <button @click="sidebarOpen = true" class="text-stone-500 lg:hidden" aria-label="メニューを開く">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <h1 class="text-base font-semibold text-stone-900">{{ $title ?? 'ダッシュボード' }}</h1>

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
                    <div class="mb-6 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
