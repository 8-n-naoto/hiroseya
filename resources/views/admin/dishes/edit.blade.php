@php
    use App\Enums\PublishStatus;
    use App\Enums\ServiceType;

    $isNew = ! $dish->exists;
    $action = $isNew ? route('admin.dishes.store') : route('admin.dishes.update', $dish);
    $oldVariants = old('variants', $variants);
@endphp

<x-layouts.admin :title="$isNew ? '料理を追加' : '料理を編集'">
    <x-admin.page-header :title="$isNew ? '料理を追加' : $dish->name">
        <x-slot:actions>
            @unless ($isNew)
                @if ($dish->has_detail_page)
                    <x-admin.button variant="secondary" :href="route('menu.show', $dish->slug)">詳細ページを見る ↗</x-admin.button>
                @endif
                <form method="POST" action="{{ route('admin.dishes.duplicate', $dish) }}">
                    @csrf
                    <x-admin.button variant="secondary">複製する</x-admin.button>
                </form>
            @endunless
            <x-admin.button variant="secondary" :href="route('admin.dishes.index')">一覧へ戻る</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <form method="POST" action="{{ $action }}" class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
        @csrf
        @unless ($isNew) @method('PUT') @endunless

        <div class="space-y-6">
            <x-admin.card title="基本情報">
                <div class="space-y-5">
                    <x-admin.field label="料理名" name="name" for="name" required>
                        <x-admin.input id="name" name="name" value="{{ old('name', $dish->name) }}" required />
                    </x-admin.field>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-admin.field label="ふりがな" name="name_kana" for="name_kana"
                            help="URLの自動生成にも使われます。例：みそにこみ">
                            <x-admin.input id="name_kana" name="name_kana" value="{{ old('name_kana', $dish->name_kana) }}" />
                        </x-admin.field>

                        <x-admin.field label="分類" name="category_id" for="category_id">
                            <x-admin.select id="category_id" name="category_id" placeholder="分類なし"
                                :selected="old('category_id', $dish->category_id)"
                                :options="$categories->mapWithKeys(fn ($c) => [$c->id => $c->name.'（'.$c->service_type->label().'）'])->all()" />
                        </x-admin.field>
                    </div>

                    <x-admin.field label="説明" name="description" for="description"
                        help="お品書きの各行と、検索結果の説明文に使われます。100文字程度が読みやすい長さです。">
                        <x-admin.textarea id="description" name="description" rows="3">{{ old('description', $dish->description) }}</x-admin.textarea>
                    </x-admin.field>
                </div>
            </x-admin.card>

            {{-- 価格 --}}
            <x-admin.card title="価格"
                description="1品に複数の価格を持たせられます（単品／セット、二枚／三枚、小／中／大、店内／お持ち帰り）。お品書きの一覧には「代表」に印を付けた価格が出ます。">
                <div x-data="variantRows({{ \Illuminate\Support\Js::from($oldVariants) }})" class="space-y-3">
                    <template x-for="(row, index) in rows" :key="index">
                        <div class="grid grid-cols-12 items-end gap-2 rounded-md border border-stone-200 p-3">
                            <div class="col-span-12 sm:col-span-4">
                                <label class="mb-1 block text-xs text-stone-500">表示名（任意）</label>
                                <input type="text" x-model="row.label" :name="`variants[${index}][label]`"
                                    placeholder="セット / 二枚 / 小"
                                    class="block w-full rounded-md border border-stone-300 px-3 py-2 text-sm">
                            </div>

                            <div class="col-span-6 sm:col-span-3">
                                <label class="mb-1 block text-xs text-stone-500">税込価格</label>
                                <input type="number" x-model="row.price" :name="`variants[${index}][price]`" min="0" required
                                    class="block w-full rounded-md border border-stone-300 px-3 py-2 text-sm">
                            </div>

                            <div class="col-span-6 sm:col-span-3">
                                <label class="mb-1 block text-xs text-stone-500">提供区分</label>
                                <select x-model="row.service_type" :name="`variants[${index}][service_type]`"
                                    class="block w-full rounded-md border border-stone-300 px-3 py-2 text-sm">
                                    @foreach (ServiceType::options() as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-span-12 flex items-center justify-between gap-3 sm:col-span-2">
                                <label class="flex items-center gap-1.5 text-xs text-stone-600">
                                    <input type="hidden" :name="`variants[${index}][is_default]`" :value="row.is_default ? 1 : 0">
                                    <input type="checkbox" :checked="row.is_default"
                                        @change="$event.target.checked ? setDefault(index) : (row.is_default = false)"
                                        class="h-4 w-4 rounded border-stone-300">
                                    代表
                                </label>
                                <button type="button" @click="remove(index)"
                                    class="text-xs text-red-600 underline" x-show="rows.length > 1">削除</button>
                            </div>
                        </div>
                    </template>

                    <button type="button" @click="add()"
                        class="rounded-md border border-dashed border-stone-300 px-4 py-2 text-sm text-stone-600 hover:bg-stone-50">
                        ＋ 価格を追加
                    </button>
                </div>
                @error('variants')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                @foreach ($errors->get('variants.*') as $messages)
                    @foreach ($messages as $message)
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @endforeach
                @endforeach
            </x-admin.card>

            {{-- 写真 --}}
            <x-admin.card title="写真">
                <div class="space-y-5">
                    <x-admin.media-picker name="main_media_id" :media="$dish->mainImage" label="メイン画像"
                        help="お品書きの一覧とSNS共有時に使われます。" />

                    <div>
                        <p class="mb-3 text-sm font-medium text-stone-700">追加の画像（詳細ページに並びます）</p>
                        <div class="grid gap-4 sm:grid-cols-2">
                            @for ($i = 0; $i < 4; $i++)
                                <x-admin.media-picker :name="'images['.$i.']'" :media="$extraImages[$i] ?? null"
                                    :label="'画像 '.($i + 1)" />
                            @endfor
                        </div>
                    </div>
                </div>
            </x-admin.card>

            {{-- 詳細ページ --}}
            <x-admin.card title="詳細ページ"
                description="すべての料理に詳細ページを作る必要はありません。中身の薄いページを大量に作ると、サイト全体の検索評価がむしろ下がります。看板料理だけに作るのがおすすめです。">
                <div class="space-y-5">
                    <x-admin.checkbox name="has_detail_page" label="この料理の詳細ページを作る"
                        :checked="old('has_detail_page', $dish->has_detail_page)" />

                    <x-admin.field label="詳細本文" name="body" for="body"
                        help="改行はそのまま反映されます。素材や食べ方など、この料理ならではの内容を書いてください。">
                        <x-admin.textarea id="body" name="body" rows="10">{{ old('body', $dish->body) }}</x-admin.textarea>
                    </x-admin.field>

                    <x-admin.field label="URL（英数字）" name="slug" for="slug"
                        help="空欄なら自動で決まります。<strong>公開後は変更しないでください。</strong>URLが変わると、これまでの検索評価と外部からのリンクが失われます。">
                        <x-admin.input id="slug" name="slug" value="{{ old('slug', $dish->slug) }}" placeholder="miso-nikomi" />
                    </x-admin.field>
                </div>
            </x-admin.card>
        </div>

        {{-- 右側 --}}
        <div class="space-y-6">
            <x-admin.card title="公開">
                <div class="space-y-4">
                    <x-admin.field label="公開状態" name="status" for="status">
                        <x-admin.select id="status" name="status" :selected="old('status', $dish->status?->value)"
                            :options="PublishStatus::options()" />
                    </x-admin.field>

                    <x-admin.field label="並び順" name="sort_order" for="sort_order"
                        help="小さいほど先に出ます。一覧画面からまとめて並べ替えることもできます。">
                        <x-admin.input type="number" id="sort_order" name="sort_order"
                            value="{{ old('sort_order', $dish->sort_order ?? 0) }}" min="0" />
                    </x-admin.field>

                    <div class="space-y-2 border-t border-stone-100 pt-4">
                        <x-admin.checkbox name="is_recommended" label="おすすめにする"
                            :checked="old('is_recommended', $dish->is_recommended)" />
                        <x-admin.checkbox name="is_new" label="「新」の印を付ける" :checked="old('is_new', $dish->is_new)" />
                        <x-admin.checkbox name="is_limited" label="「期間限定」の印を付ける" :checked="old('is_limited', $dish->is_limited)" />
                        <x-admin.checkbox name="is_sold_out" label="品切れにする"
                            help="お品書きには残りますが、品切れと表示されます。" :checked="old('is_sold_out', $dish->is_sold_out)" />
                    </div>
                </div>
            </x-admin.card>

            <x-admin.card title="提供期間"
                description="冬限定などの季節メニューに使います。期間外になると、お品書きから自動で消えます。空欄なら通年です。">
                <div class="space-y-4">
                    <x-admin.field label="提供開始日" name="available_from" for="available_from">
                        <x-admin.input type="date" id="available_from" name="available_from"
                            value="{{ old('available_from', $dish->available_from?->toDateString()) }}" />
                    </x-admin.field>

                    <x-admin.field label="提供終了日" name="available_to" for="available_to">
                        <x-admin.input type="date" id="available_to" name="available_to"
                            value="{{ old('available_to', $dish->available_to?->toDateString()) }}" />
                    </x-admin.field>
                </div>
            </x-admin.card>

            @if ($allergens->isNotEmpty())
                <x-admin.card title="アレルギー"
                    description="特定原材料8品目。入力しない料理には表示自体が出ません。表示する場合は、同一の厨房で調理している旨の注記が自動で添えられます。">
                    <div class="grid grid-cols-2 gap-2">
                        @foreach ($allergens as $allergen)
                            <label class="flex items-center gap-2 text-sm text-stone-700">
                                <input type="checkbox" name="allergens[]" value="{{ $allergen->id }}"
                                    @checked(in_array($allergen->id, old('allergens', $selectedAllergens), false))
                                    class="h-4 w-4 rounded border-stone-300">
                                {{ $allergen->name }}
                            </label>
                        @endforeach
                    </div>
                </x-admin.card>
            @endif

            <div class="flex flex-col gap-3">
                <x-admin.button>{{ $isNew ? '追加する' : '更新する' }}</x-admin.button>
            </div>
        </div>
    </form>

    @unless ($isNew)
        <form method="POST" action="{{ route('admin.dishes.destroy', $dish) }}" class="mt-8"
            onsubmit="return confirm('この料理を削除します。よろしいですか？');">
            @csrf @method('DELETE')
            <button class="text-sm text-red-600 underline">この料理を削除する</button>
        </form>
    @endunless
</x-layouts.admin>
