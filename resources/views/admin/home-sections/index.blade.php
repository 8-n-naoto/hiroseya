<x-layouts.admin title="トップページ">
    <x-admin.page-header title="トップページ"
        description="トップページに並ぶ各段の内容と順序を変えられます。段の種類そのものは固定です（レイアウトが崩れないようにするため）。">
    </x-admin.page-header>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
        <form method="POST" action="{{ route('admin.home-sections.reorder') }}">
            @csrf @method('PUT')

            <x-admin.card title="段の並び" :padding="false" x-data="sortableList">
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-stone-100">
                        @foreach ($sections as $section)
                            <tr data-sort-row>
                                <td class="w-24 px-4 py-3">
                                    <input type="hidden" name="order[]" value="{{ $section->id }}">
                                    <input type="hidden" data-sort-input value="{{ $loop->index }}">
                                    <div class="flex items-center gap-1">
                                        <button type="button" @click="move({{ $loop->index }}, -1)"
                                            class="rounded border border-stone-200 px-1.5 text-stone-500" aria-label="上へ">▲</button>
                                        <button type="button" @click="move({{ $loop->index }}, 1)"
                                            class="rounded border border-stone-200 px-1.5 text-stone-500" aria-label="下へ">▼</button>
                                    </div>
                                </td>
                                <td class="w-14 px-2 py-3">
                                    @if ($section->image)
                                        <img src="{{ $section->image->variantUrl('sm') }}" alt=""
                                            class="h-10 w-10 rounded object-cover" loading="lazy">
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.home-sections.edit', $section) }}"
                                        class="font-medium text-stone-800 hover:underline">{{ $section->label() }}</a>
                                    @if ($section->title)
                                        <span class="mt-0.5 block truncate text-xs text-stone-500">{{ $section->title }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($section->isLocked())
                                        <x-admin.status-badge tone="muted" label="常に表示" />
                                    @elseif (! $section->is_visible)
                                        <x-admin.status-badge tone="muted" label="非表示" />
                                    @else
                                        <x-admin.status-badge tone="ok" label="表示中" />
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('admin.home-sections.edit', $section) }}"
                                        class="text-xs text-stone-600 underline">編集</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-admin.card>

            <div class="mt-4">
                <x-admin.button>並び順を保存</x-admin.button>
            </div>
        </form>

        <form method="POST" action="{{ route('admin.home-sections.recommended') }}">
            @csrf @method('PUT')

            <x-admin.card title="おすすめ料理"
                description="トップページの「おすすめ」に出す料理を選びます。上から順に並びます。空欄にすると、その枠は使われません。">
                <div class="space-y-3">
                    @for ($i = 0; $i < 6; $i++)
                        @php $current = $recommended->values()->get($i)?->dish_id; @endphp
                        <div class="flex items-center gap-2">
                            <span class="w-5 text-xs text-stone-400">{{ $i + 1 }}</span>
                            <x-admin.select name="dishes[]" placeholder="選択しない" :selected="$current"
                                :options="$dishes->pluck('name', 'id')->all()" />
                        </div>
                    @endfor
                </div>

                <p class="mt-4 text-xs leading-relaxed text-stone-500">
                    選ばれた料理が足りない場合は、料理の編集画面で「おすすめにする」を付けた料理から自動で補われます。
                </p>

                <div class="mt-4">
                    <x-admin.button>おすすめを保存</x-admin.button>
                </div>
            </x-admin.card>
        </form>
    </div>
</x-layouts.admin>
