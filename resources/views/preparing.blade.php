<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $siteName }} -ただいま準備中です</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-stone-100 px-6 text-stone-800 antialiased">
    <div class="max-w-md text-center">
        <p class="mb-2 text-sm tracking-widest text-stone-500">{{ $siteName }}</p>
        <h1 class="mb-6 text-2xl font-semibold">ただいまサイトを準備中です</h1>
        <p class="whitespace-pre-line text-stone-600">{{ $message }}</p>
    </div>
</body>
</html>
