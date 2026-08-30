<x-layouts.guest title="ログイン">
    <h1 class="mb-6 text-lg font-semibold text-stone-900">管理画面ログイン</h1>

    @if (session('status'))
        <div class="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="mb-1 block text-sm font-medium text-stone-700">メールアドレス</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm focus:border-stone-500 focus:outline-none focus:ring-1 focus:ring-stone-500">
        </div>

        <div>
            <label for="password" class="mb-1 block text-sm font-medium text-stone-700">パスワード</label>
            <input id="password" type="password" name="password" required autocomplete="current-password"
                class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm focus:border-stone-500 focus:outline-none focus:ring-1 focus:ring-stone-500">
        </div>

        <label class="flex items-center gap-2 text-sm text-stone-600">
            <input type="checkbox" name="remember" class="rounded border-stone-300">
            ログイン状態を保持する
        </label>

        <button type="submit"
            class="w-full rounded-md bg-stone-800 px-4 py-2 text-sm font-medium text-white transition hover:bg-stone-700">
            ログイン
        </button>
    </form>

    <div class="mt-4 text-center text-sm">
        <a href="{{ route('password.request') }}" class="text-stone-500 underline hover:text-stone-700">
            パスワードをお忘れの方はこちら
        </a>
    </div>
</x-layouts.guest>
