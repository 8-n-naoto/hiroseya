<x-layouts.admin title="操作ログ">
    <x-admin.page-header title="操作ログ"
        description="誰がいつ何を変更したかの記録です。複数人で運用しているときに、変更の経緯をたどれます。" />

    <x-admin.card class="mb-6">
        <form method="GET" action="{{ route('admin.activity.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="min-w-56">
                <x-admin.field label="ユーザー" name="user">
                    <x-admin.select name="user" placeholder="すべて" :selected="$filters['user'] ?? null"
                        :options="$users->all()" />
                </x-admin.field>
            </div>
            <x-admin.button>絞り込む</x-admin.button>
            <x-admin.button variant="secondary" :href="route('admin.activity.index')">解除</x-admin.button>
        </form>
    </x-admin.card>

    @if ($logs->isEmpty())
        <x-admin.empty message="記録がありません。" />
    @else
        <x-admin.card :padding="false">
            <table class="w-full text-sm">
                <thead class="border-b border-stone-200 bg-stone-50 text-xs text-stone-500">
                    <tr>
                        <th class="w-40 px-4 py-3 text-left">日時</th>
                        <th class="w-32 px-4 py-3 text-left">ユーザー</th>
                        <th class="px-4 py-3 text-left">内容</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @foreach ($logs as $log)
                        <tr>
                            <td class="px-4 py-2.5 text-stone-500">{{ $log->created_at?->format('Y/n/j H:i') }}</td>
                            <td class="px-4 py-2.5 text-stone-600">{{ $log->user?->name ?: '－' }}</td>
                            <td class="px-4 py-2.5 text-stone-800">{{ $log->summary ?: $log->action }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-admin.card>

        <div class="mt-6">{{ $logs->links() }}</div>
    @endif
</x-layouts.admin>
