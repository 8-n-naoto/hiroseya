<x-layouts.admin title="料理の分類">
    <x-admin.page-header title="料理の分類"
        description="お品書きの見出しになります。分類は「並べ方」を決めるもので、絞り込みの正ではありません。たとえば『丼もの』の料理でも、お持ち帰り価格を持っていればお持ち帰りのページに出ます。">
        <x-slot:actions>
            <x-admin.button :href="route('admin.dish-categories.create')">分類を追加</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    {{-- 並び替えは提供区分ごとに独立して行う（カードごとに x-data を分ける）。
         送信される order[] は画面上の並び順のまま集まる。 --}}
    <form method="POST" action="{{ route('admin.dish-categories.sort') }}" class="space-y-6">
        @csrf

        @foreach ($serviceTypes as $type)
            @php $items = $categories->get($type->value, collect()); @endphp

            <x-admin.card :title="$type->label()" :padding="false" x-data="sortableList">
                @if ($items->isEmpty())
                    <div class="px-5 py-6"><x-admin.empty message="分類がありません。" /></div>
                @else
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-stone-100">
                            @foreach ($items as $category)
                                <tr data-sort-row>
                                    <td class="w-24 px-4 py-3">
                                        <input type="hidden" name="order[]" value="{{ $category->id }}">
                                        <input type="hidden" data-sort-input value="{{ $loop->index }}">
                                        <div class="flex items-center gap-1">
                                            <button type="button" @click="move({{ $loop->index }}, -1)"
                                                class="rounded border border-stone-200 px-1.5 text-stone-500" aria-label="上へ">▲</button>
                                            <button type="button" @click="move({{ $loop->index }}, 1)"
                                                class="rounded border border-stone-200 px-1.5 text-stone-500" aria-label="下へ">▼</button>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('admin.dish-categories.edit', $category) }}"
                                            class="font-medium text-stone-800 hover:underline">{{ $category->name }}</a>
                                        <span class="ml-2 text-xs text-stone-400">/{{ $category->slug }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-stone-500">{{ $category->dishes_count }}品</td>
                                    <td class="px-4 py-3">
                                        @unless ($category->is_visible)
                                            <x-admin.status-badge tone="muted" label="非表示" />
                                        @endunless
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('admin.dish-categories.edit', $category) }}"
                                            class="text-xs text-stone-600 underline">編集</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </x-admin.card>
        @endforeach

        <x-admin.button>並び順を保存</x-admin.button>
    </form>
</x-layouts.admin>
