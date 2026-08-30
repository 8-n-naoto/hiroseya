<x-layouts.admin title="ユーザー">
    <x-admin.page-header title="ユーザー"
        description="管理画面にログインできる方の一覧です。退職や交代のときは、削除ではなく「利用停止」にすると、これまでの操作記録が残ります。">
        <x-slot:actions>
            <x-admin.button :href="route('admin.users.create')">ユーザーを追加</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.card :padding="false" class="max-w-4xl">
        <table class="w-full text-sm">
            <thead class="border-b border-stone-200 bg-stone-50 text-xs text-stone-500">
                <tr>
                    <th class="px-4 py-3 text-left">お名前</th>
                    <th class="px-4 py-3 text-left">メールアドレス</th>
                    <th class="w-28 px-4 py-3 text-left">権限</th>
                    <th class="w-28 px-4 py-3 text-left">状態</th>
                    <th class="w-40 px-4 py-3 text-left">最終ログイン</th>
                    <th class="w-20 px-4 py-3 text-right">操作</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @foreach ($users as $user)
                    <tr>
                        <td class="px-4 py-3 font-medium text-stone-800">
                            {{ $user->name }}
                            @if ($user->id === auth()->id())
                                <span class="ml-1 text-xs text-stone-400">（あなた）</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-stone-600">{{ $user->email }}</td>
                        <td class="px-4 py-3">
                            <x-admin.status-badge :tone="$user->isOwner() ? 'notice' : 'muted'" :label="$user->roleLabel()" />
                        </td>
                        <td class="px-4 py-3">
                            <x-admin.status-badge :tone="$user->is_active ? 'ok' : 'muted'"
                                :label="$user->is_active ? '有効' : '利用停止'" />
                        </td>
                        <td class="px-4 py-3 text-xs text-stone-500">
                            {{ $user->last_login_at?->format('Y/n/j H:i') ?: '－' }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.users.edit', $user) }}" class="text-xs text-stone-600 underline">編集</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-admin.card>

    <div class="mt-6 max-w-4xl rounded-md border border-stone-200 bg-white px-4 py-3 text-sm leading-relaxed text-stone-600">
        <p><strong class="text-stone-800">権限について</strong></p>
        <ul class="mt-2 list-inside list-disc space-y-1">
            <li><strong>管理者</strong>… すべての操作ができます。店舗情報・営業時間・SEO・メール・SNS・ユーザーの設定は管理者のみです。</li>
            <li><strong>編集者</strong>… 料理・お知らせ・イベント・トップページ・画像の編集と、お問い合わせ対応ができます。</li>
        </ul>
    </div>
</x-layouts.admin>
