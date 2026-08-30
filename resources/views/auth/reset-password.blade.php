<x-layouts.guest title="新しいパスワードの設定">
    <h1 class="mb-6 text-lg font-semibold text-stone-900">新しいパスワードの設定</h1>

    @if ($errors->any())
        <div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <label for="email" class="mb-1 block text-sm font-medium text-stone-700">メールアドレス</label>
            <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required autofocus autocomplete="username"
                class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm focus:border-stone-500 focus:outline-none focus:ring-1 focus:ring-stone-500">
        </div>

        <div>
            <label for="password" class="mb-1 block text-sm font-medium text-stone-700">新しいパスワード</label>
            <input id="password" type="password" name="password" required autocomplete="new-password"
                class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm focus:border-stone-500 focus:outline-none focus:ring-1 focus:ring-stone-500">
            <p class="mt-1 text-xs text-stone-500">8文字以上で設定してください。</p>
        </div>

        <div>
            <label for="password_confirmation" class="mb-1 block text-sm font-medium text-stone-700">新しいパスワード（確認）</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                class="w-full rounded-md border border-stone-300 px-3 py-2 text-sm focus:border-stone-500 focus:outline-none focus:ring-1 focus:ring-stone-500">
        </div>

        <button type="submit"
            class="w-full rounded-md bg-stone-800 px-4 py-2 text-sm font-medium text-white transition hover:bg-stone-700">
            パスワードを再設定する
        </button>
    </form>
</x-layouts.guest>
