@php
    /** @var \App\Support\Settings $settings */
    $settings = app(\App\Support\Settings::class);
@endphp
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ?? 'ログイン' }} - {{ $settings->string('site.site_name', '広瀬屋') }} 管理画面</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-stone-100 text-stone-800 antialiased">
    <div class="flex min-h-screen flex-col items-center justify-center px-4 py-10">
        <div class="mb-8 text-center">
            <p class="text-sm tracking-wide text-stone-500">{{ $settings->string('site.site_name', '広瀬屋') }} 管理画面</p>
        </div>

        <div class="w-full max-w-sm rounded-xl border border-stone-200 bg-white p-8 shadow-sm">
            {{ $slot }}
        </div>
    </div>
</body>
</html>
