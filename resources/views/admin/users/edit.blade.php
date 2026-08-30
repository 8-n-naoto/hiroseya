@php
    use App\Enums\UserRole;

    $isNew = ! $user->exists;
    $isSelf = ! $isNew && $user->id === auth()->id();
    $action = $isNew ? route('admin.users.store') : route('admin.users.update', $user);
@endphp

<x-layouts.admin :title="$isNew ? 'ユーザーを追加' : 'ユーザーを編集'">
    <x-admin.page-header :title="$isNew ? 'ユーザーを追加' : $user->name">
        <x-slot:actions>
            <x-admin.button variant="secondary" :href="route('admin.users.index')">一覧へ戻る</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <form method="POST" action="{{ $action }}" class="max-w-xl space-y-6">
        @csrf
        @unless ($isNew) @method('PUT') @endunless

        <x-admin.card>
            <div class="space-y-5">
                <x-admin.field label="お名前" name="name" for="name" required>
                    <x-admin.input id="name" name="name" value="{{ old('name', $user->name) }}" required />
                </x-admin.field>

                <x-admin.field label="メールアドレス" name="email" for="email" required
                    help="ログインに使います。パスワードの再設定メールもここへ届きます。">
                    <x-admin.input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required />
                </x-admin.field>

                <x-admin.field label="パスワード" name="password" for="password" :required="$isNew"
                    :help="$isNew ? '10文字以上で、英字と数字を含めてください。' : '変更するときだけ入力してください。空欄なら今のパスワードのままです。'">
                    <x-admin.input type="password" id="password" name="password" autocomplete="new-password"
                        :required="$isNew" />
                </x-admin.field>

                <x-admin.field label="パスワード（確認）" name="password_confirmation" for="password_confirmation">
                    <x-admin.input type="password" id="password_confirmation" name="password_confirmation"
                        autocomplete="new-password" />
                </x-admin.field>

                <x-admin.field label="権限" name="role" for="role">
                    @if ($isSelf)
                        <p class="rounded-md bg-stone-50 px-3 py-2 text-sm text-stone-600">
                            {{ $user->roleLabel() }}（自分自身の権限は変更できません）
                        </p>
                        <input type="hidden" name="role" value="{{ $user->role->value }}">
                    @else
                        <x-admin.select id="role" name="role" :selected="old('role', $user->role?->value)"
                            :options="UserRole::options()" />
                    @endif
                </x-admin.field>

                @unless ($isSelf)
                    <x-admin.checkbox name="is_active" label="このユーザーの利用を許可する"
                        help="チェックを外すと、次のアクセスで自動的にログアウトされ、以降ログインできなくなります。"
                        :checked="old('is_active', $user->is_active ?? true)" />
                @endunless
            </div>
        </x-admin.card>

        <div class="flex items-center justify-between">
            <x-admin.button>{{ $isNew ? '追加する' : '更新する' }}</x-admin.button>

            @if (! $isNew && ! $isSelf)
                <button form="delete-user" class="text-sm text-red-600 underline">このユーザーを削除する</button>
            @endif
        </div>
    </form>

    @if (! $isNew && ! $isSelf)
        <form id="delete-user" method="POST" action="{{ route('admin.users.destroy', $user) }}"
            onsubmit="return confirm('このユーザーを削除します。よろしいですか？');">
            @csrf @method('DELETE')
        </form>
    @endif
</x-layouts.admin>
