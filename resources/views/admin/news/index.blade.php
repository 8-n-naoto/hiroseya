@php use App\Enums\PublishStatus; @endphp

<x-layouts.admin title="お知らせ">
    <x-admin.page-header title="お知らせ"
        description="新商品や臨時休業など、日付のあるお知らせを掲載します。公開日時を先の日付にすると、その時刻になってから自動で公開されます。">
        <x-slot:actions>
            <x-admin.button :href="route('admin.news.create')">お知らせを書く</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.card class="mb-6">
        <form method="GET" action="{{ route('admin.news.index') }}" class="grid gap-4 sm:grid-cols-3">
            <x-admin.field label="キーワード" name="q">
                <x-admin.input name="q" value="{{ $filters['q'] ?? '' }}" />
            </x-admin.field>
            <x-admin.field label="公開状態" name="status">
                <x-admin.select name="status" placeholder="すべて" :selected="$filters['status'] ?? null"
                    :options="PublishStatus::options()" />
            </x-admin.field>
            <div class="flex items-end gap-2">
                <x-admin.button>絞り込む</x-admin.button>
                <x-admin.button variant="secondary" :href="route('admin.news.index')">解除</x-admin.button>
            </div>
        </form>
    </x-admin.card>

    @if ($items->isEmpty())
        <x-admin.empty message="お知らせがありません。" />
    @else
        <x-admin.card :padding="false">
            <table class="w-full text-sm">
                <thead class="border-b border-stone-200 bg-stone-50 text-xs text-stone-500">
                    <tr>
                        <th class="w-40 px-4 py-3 text-left">公開日時</th>
                        <th class="px-4 py-3 text-left">タイトル</th>
                        <th class="w-32 px-4 py-3 text-left">状態</th>
                        <th class="w-24 px-4 py-3 text-right">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @foreach ($items as $news)
                        <tr>
                            <td class="px-4 py-3 text-stone-600">
                                {{ $news->published_at?->format('Y/n/j H:i') ?: '－' }}
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.news.edit', $news) }}"
                                    class="font-medium text-stone-800 hover:underline">{{ $news->title }}</a>
                            </td>
                            <td class="px-4 py-3">
                                @if ($news->isPublished() && $news->published_at?->isFuture())
                                    <x-admin.status-badge tone="notice" label="公開予約中" />
                                @else
                                    <x-admin.status-badge :tone="$news->isPublished() ? 'ok' : 'muted'"
                                        :label="$news->status->label()" />
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.news.edit', $news) }}" class="text-xs text-stone-600 underline">編集</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-admin.card>

        <div class="mt-6">{{ $items->links() }}</div>
    @endif
</x-layouts.admin>
