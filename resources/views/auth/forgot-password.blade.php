<x-layouts.guest title="パスワード再設定">
    <h1 class="mb-4 text-lg font-semibold text-stone-900">パスワード再設定</h1>
    <p class="mb-6 text-sm text-stone-600">
        登録済みのメールアドレスを入力してください。再設定用のリンクをお送りします。
    </p>

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

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="mb-1 block text-sm font-medium text-stone-700">メールアドレス</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm focus:border-stone-500 focus:outline-none focus:ring-1 focus:ring-stone-500">
        </div>

        <button type="submit"
            class="w-full rounded-md bg-stone-800 px-4 py-2 text-sm font-medium text-white transition hover:bg-stone-700">
            再設定メールを送る
        </button>
    </form>

    <div class="mt-4 text-center text-sm">
        <a href="{{ route('login') }}" class="text-stone-500 underline hover:text-stone-700">ログイン画面に戻る</a>
    </div>
</x-layouts.guest>
