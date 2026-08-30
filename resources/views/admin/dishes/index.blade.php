@php use App\Enums\PublishStatus; use App\Enums\ServiceType; @endphp

<x-layouts.admin title="お品書き（料理）">
    <x-admin.page-header title="お品書き（料理）"
        description="お品書きに出す料理を管理します。価格は「単品／セット」「二枚／三枚」のように複数持たせられます。">
        <x-slot:actions>
            <x-admin.button :href="route('admin.dishes.create')">料理を追加</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($noImageCount > 0 && ! ($filters['no_image'] ?? false))
        <div class="mb-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            公開中の料理のうち {{ $noImageCount }} 件に写真がありません。
            <a href="{{ route('admin.dishes.index', ['no_image' => 1]) }}" class="underline">絞り込んで見る</a>
        </div>
    @endif

    <x-admin.card class="mb-6">
        <form method="GET" action="{{ route('admin.dishes.index') }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-admin.field label="キーワード" name="q">
                <x-admin.input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="料理名・ふりがな" />
            </x-admin.field>

            <x-admin.field label="分類" name="category">
                <x-admin.select name="category" placeholder="すべて" :selected="$filters['category'] ?? null"
                    :options="$categories->mapWithKeys(fn ($c) => [$c->id => $c->name.'（'.$c->service_type->label().'）'])->all()" />
            </x-admin.field>

            <x-admin.field label="公開状態" name="status">
                <x-admin.select name="status" placeholder="すべて" :selected="$filters['status'] ?? null"
                    :options="PublishStatus::options()" />
            </x-admin.field>

            <div class="flex items-end gap-2">
                <x-admin.button>絞り込む</x-admin.button>
                <x-admin.button variant="secondary" :href="route('admin.dishes.index')">解除</x-admin.button>
            </div>
        </form>
    </x-admin.card>

    @if ($dishes->isEmpty())
        <x-admin.empty message="該当する料理がありません。" />
    @else
        <form method="POST" action="{{ route('admin.dishes.sort') }}" x-data="sortableList">
            @csrf

            <x-admin.card :padding="false">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[820px] text-sm">
                        <thead class="border-b border-stone-200 bg-stone-50 text-xs text-stone-500">
                            <tr>
                                <th class="w-20 px-4 py-3 text-left">並び</th>
                                <th class="w-16 px-2 py-3 text-left">写真</th>
                                <th class="px-4 py-3 text-left">料理名</th>
                                <th class="px-4 py-3 text-left">分類</th>
                                <th class="px-4 py-3 text-left">価格</th>
                                <th class="px-4 py-3 text-left">状態</th>
                                <th class="px-4 py-3 text-right">操作</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            @foreach ($dishes as $index => $dish)
                                <tr data-sort-row>
                                    <td class="px-4 py-3">
                                        <input type="hidden" name="order[]" value="{{ $dish->id }}">
                                        <input type="hidden" data-sort-input value="{{ $index }}">
                                        <div class="flex items-center gap-1">
                                            <button type="button" @click="move({{ $index }}, -1)"
                                                class="rounded border border-stone-200 px-1.5 text-stone-500 hover:bg-stone-50"
                                                aria-label="上へ">▲</button>
                                            <button type="button" @click="move({{ $index }}, 1)"
                                                class="rounded border border-stone-200 px-1.5 text-stone-500 hover:bg-stone-50"
                                                aria-label="下へ">▼</button>
                                        </div>
                                    </td>
                                    <td class="px-2 py-3">
                                        @if ($dish->mainImage)
                                            <img src="{{ $dish->mainImage->variantUrl('sm') }}" alt=""
                                                class="h-10 w-10 rounded object-cover" loading="lazy">
                                        @else
                                            <span class="flex h-10 w-10 items-center justify-center rounded bg-amber-50 text-[10px] text-amber-700">無</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('admin.dishes.edit', $dish) }}"
                                            class="font-medium text-stone-800 hover:underline">{{ $dish->name }}</a>
                                        <div class="mt-0.5 flex flex-wrap gap-1">
                                            @if ($dish->is_recommended)<span class="rounded bg-stone-100 px-1.5 text-[10px] text-stone-600">おすすめ</span>@endif
                                            @if ($dish->is_limited)<span class="rounded bg-stone-100 px-1.5 text-[10px] text-stone-600">期間限定</span>@endif
                                            @if ($dish->is_sold_out)<span class="rounded bg-red-100 px-1.5 text-[10px] text-red-700">品切れ</span>@endif
                                            @if ($dish->has_detail_page)<span class="rounded bg-stone-100 px-1.5 text-[10px] text-stone-600">詳細ページ有</span>@endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-stone-600">{{ $dish->category?->name ?: '－' }}</td>
                                    <td class="px-4 py-3 text-stone-600">
                                        @foreach ([ServiceType::DineIn, ServiceType::Takeout] as $type)
                                            @php $variant = $dish->defaultVariant($type); @endphp
                                            @if ($variant)
                                                <div class="text-xs">
                                                    <span class="text-stone-400">{{ $type->label() }}</span>
                                                    {{ $variant->formattedPrice() }}
                                                </div>
                                            @endif
                                        @endforeach
                                    </td>
                                    <td class="px-4 py-3">
                                        <x-admin.status-badge :tone="$dish->isPublished() ? 'ok' : 'muted'"
                                            :label="$dish->status->label()" />
                                        @if (! $dish->isAvailableToday())
                                            <span class="mt-1 block text-[10px] text-stone-400">提供期間外</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <a href="{{ route('admin.dishes.edit', $dish) }}" class="text-xs text-stone-600 underline">編集</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-admin.card>

            <div class="mt-4 flex flex-wrap items-center justify-between gap-4">
                <p class="text-xs text-stone-500">
                    ▲▼ で並べ替えたあと「並び順を保存」を押してください。並び順は公開サイトのお品書きに反映されます。
                </p>
                <x-admin.button>並び順を保存</x-admin.button>
            </div>
        </form>

        <div class="mt-6">{{ $dishes->links() }}</div>
    @endif
</x-layouts.admin>
