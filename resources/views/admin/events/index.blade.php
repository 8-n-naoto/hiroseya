@php use App\Enums\PublishStatus; @endphp

<x-layouts.admin title="イベント">
    <x-admin.page-header title="イベント"
        description="開催期間を入れておくと、期間が過ぎたイベントは自動で「終了したイベント」に移ります。">
        <x-slot:actions>
            <x-admin.button :href="route('admin.events.create')">イベントを追加</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.card class="mb-6">
        <form method="GET" action="{{ route('admin.events.index') }}" class="grid gap-4 sm:grid-cols-3">
            <x-admin.field label="キーワード" name="q">
                <x-admin.input name="q" value="{{ $filters['q'] ?? '' }}" />
            </x-admin.field>
            <x-admin.field label="公開状態" name="status">
                <x-admin.select name="status" placeholder="すべて" :selected="$filters['status'] ?? null"
                    :options="PublishStatus::options()" />
            </x-admin.field>
            <div class="flex items-end gap-2">
                <x-admin.button>絞り込む</x-admin.button>
                <x-admin.button variant="secondary" :href="route('admin.events.index')">解除</x-admin.button>
            </div>
        </form>
    </x-admin.card>

    @if ($items->isEmpty())
        <x-admin.empty message="イベントがありません。" />
    @else
        <x-admin.card :padding="false">
            <table class="w-full text-sm">
                <thead class="border-b border-stone-200 bg-stone-50 text-xs text-stone-500">
                    <tr>
                        <th class="w-56 px-4 py-3 text-left">開催期間</th>
                        <th class="px-4 py-3 text-left">タイトル</th>
                        <th class="w-32 px-4 py-3 text-left">状態</th>
                        <th class="w-24 px-4 py-3 text-right">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @foreach ($items as $event)
                        <tr>
                            <td class="px-4 py-3 text-stone-600">{{ $event->periodLabel() ?: '－' }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.events.edit', $event) }}"
                                    class="font-medium text-stone-800 hover:underline">{{ $event->title }}</a>
                            </td>
                            <td class="px-4 py-3">
                                <x-admin.status-badge :tone="$event->isPublished() ? 'ok' : 'muted'"
                                    :label="$event->status->label()" />
                                @if ($event->isPublished())
                                    <span class="ml-1 text-[10px] text-stone-400">
                                        {{ $event->isOngoing() ? '開催中' : '期間外' }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.events.edit', $event) }}" class="text-xs text-stone-600 underline">編集</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-admin.card>

        <div class="mt-6">{{ $items->links() }}</div>
    @endif
</x-layouts.admin>
