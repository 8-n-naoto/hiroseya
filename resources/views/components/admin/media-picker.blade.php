@props(['name', 'media' => null, 'label' => '画像', 'help' => null, 'ratio' => 'aspect-[4/3]'])
@php
    $initial = $media ? [
        'id' => $media->id,
        'url' => $media->variantUrl('sm'),
        'name' => $media->original_name ?: $media->path,
        'alt' => $media->alt,
    ] : null;
@endphp

<x-admin.field :label="$label" :name="$name" :help="$help">
    <div x-data="mediaPicker({{ \Illuminate\Support\Js::from($initial) }}, '{{ route('admin.media.picker') }}')">
        <input type="hidden" name="{{ $name }}" :value="selected ? selected.id : ''">

        <div class="flex items-start gap-4">
            <div class="w-32 shrink-0 overflow-hidden rounded-md border border-stone-200 bg-stone-50 {{ $ratio }}">
                <template x-if="selected">
                    <img :src="selected.url" :alt="selected.alt || ''" class="h-full w-full object-cover">
                </template>
                <template x-if="!selected">
                    <div class="flex h-full w-full items-center justify-center text-xs text-stone-400">未設定</div>
                </template>
            </div>

            <div class="space-y-2">
                <div class="flex flex-wrap gap-2">
                    <button type="button" @click="show()"
                        class="rounded-md border border-stone-300 bg-white px-3 py-1.5 text-xs text-stone-700 hover:bg-stone-50">
                        画像を選ぶ
                    </button>
                    <button type="button" @click="clear()" x-show="selected"
                        class="rounded-md border border-stone-300 bg-white px-3 py-1.5 text-xs text-stone-600 hover:bg-stone-50">
                        外す
                    </button>
                </div>
                <p class="text-xs text-stone-500" x-text="selected ? selected.name : ''"></p>
                <template x-if="selected && !selected.alt">
                    <p class="text-xs text-amber-700">
                        この画像には代替テキスト（alt）が設定されていません。画像ライブラリから入力してください。
                    </p>
                </template>
            </div>
        </div>

        {{-- 選択用の重ね窓 --}}
        <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/50 p-4"
            @keydown.escape.window="open = false">
            <div class="flex max-h-[85vh] w-full max-w-4xl flex-col rounded-lg bg-white shadow-xl" @click.outside="open = false">
                <div class="flex items-center gap-3 border-b border-stone-200 px-5 py-3">
                    <h3 class="text-sm font-semibold text-stone-800">画像を選ぶ</h3>
                    <input type="search" x-model="keyword" @keydown.enter.prevent="search()"
                        placeholder="ファイル名・代替テキストで検索"
                        class="ml-auto w-56 rounded-md border border-stone-300 px-3 py-1.5 text-sm">
                    <button type="button" @click="search()"
                        class="rounded-md bg-stone-800 px-3 py-1.5 text-xs text-white">検索</button>
                    <button type="button" @click="open = false" class="text-stone-400 hover:text-stone-600">✕</button>
                </div>

                <div class="flex-1 overflow-y-auto p-5">
                    <p x-show="loading" class="py-10 text-center text-sm text-stone-500">読み込んでいます…</p>

                    <p x-show="!loading && items.length === 0" class="py-10 text-center text-sm text-stone-500">
                        画像が見つかりませんでした。画像ライブラリからアップロードしてください。
                    </p>

                    <div class="grid grid-cols-3 gap-3 sm:grid-cols-4 md:grid-cols-6" x-show="!loading">
                        <template x-for="item in items" :key="item.id">
                            <button type="button" @click="choose(item)"
                                class="group overflow-hidden rounded-md border border-stone-200 text-left hover:border-stone-800">
                                <img :src="item.url" :alt="item.alt || ''" class="aspect-square w-full object-cover" loading="lazy">
                                <span class="block truncate px-1.5 py-1 text-[10px] text-stone-500" x-text="item.name"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <div class="border-t border-stone-200 px-5 py-3 text-right">
                    <a href="{{ route('admin.media.index') }}" target="_blank" rel="noopener"
                        class="text-xs text-stone-500 underline">画像ライブラリを開く ↗</a>
                </div>
            </div>
        </div>
    </div>
</x-admin.field>
