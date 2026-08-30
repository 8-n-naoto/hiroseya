<x-layouts.admin title="画像ライブラリ">
    <section class="mb-8 rounded-lg border border-stone-200 bg-white p-5">
        <h2 class="mb-4 text-sm font-semibold text-stone-700">画像をアップロード</h2>
        <form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data"
            class="grid grid-cols-1 gap-4 sm:grid-cols-4 sm:items-end">
            @csrf

            <div class="sm:col-span-2">
                <label class="mb-1 block text-xs font-medium text-stone-600">画像ファイル</label>
                <input type="file" name="file" accept="image/jpeg,image/png,image/webp" required
                    class="w-full text-sm text-stone-600 file:mr-3 file:rounded-md file:border-0 file:bg-stone-800 file:px-3 file:py-1.5 file:text-xs file:text-white">
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-stone-600">保存先（dishes / news / home など）</label>
                <input type="text" name="directory" placeholder="uploads"
                    class="w-full rounded-md border border-stone-300 px-3 py-1.5 text-sm">
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-stone-600">alt（代替テキスト）</label>
                <input type="text" name="alt" maxlength="191"
                    class="w-full rounded-md border border-stone-300 px-3 py-1.5 text-sm">
            </div>

            <div class="sm:col-span-4">
                <button type="submit"
                    class="rounded-md bg-stone-800 px-4 py-2 text-sm font-medium text-white hover:bg-stone-700">
                    アップロード
                </button>
                <span class="ml-3 text-xs text-stone-400">
                    最大 {{ number_format(config('hiroseya.images.max_upload_kb', 8192) / 1024, 1) }}MB。自動でWebPの3サイズを生成します。
                </span>
            </div>
        </form>
    </section>

    <section class="mb-4 flex flex-wrap items-center gap-3 text-sm">
        <form method="GET" action="{{ route('admin.media.index') }}" class="flex flex-wrap items-center gap-2">
            <select name="directory" onchange="this.form.submit()"
                class="rounded-md border border-stone-300 px-2 py-1 text-sm">
                <option value="">すべてのフォルダ</option>
                @foreach ($directories as $dir)
                    <option value="{{ $dir }}" @selected($directory === $dir)>{{ $dir }}</option>
                @endforeach
            </select>

            <label class="flex items-center gap-1 text-stone-600">
                <input type="checkbox" name="missing_alt" value="1" onchange="this.form.submit()" @checked($missingAlt)>
                alt未入力のみ
            </label>
        </form>
    </section>

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
        @forelse ($media as $item)
            <div class="overflow-hidden rounded-lg border border-stone-200 bg-white">
                <div class="aspect-square bg-stone-100">
                    <img src="{{ $item->variantUrl('sm') }}" alt="{{ $item->alt }}" loading="lazy"
                        class="h-full w-full object-cover">
                </div>
                <div class="space-y-2 p-3">
                    <form method="POST" action="{{ route('admin.media.update', $item) }}">
                        @csrf
                        @method('PATCH')

                        <input type="text" name="alt" value="{{ $item->alt }}" placeholder="altを入力"
                            class="w-full rounded border px-2 py-1 text-xs {{ $item->isMissingAlt() ? 'border-amber-400 bg-amber-50' : 'border-stone-200' }}">

                        <div class="mt-1">
                            <button type="submit" class="text-xs text-stone-500 underline hover:text-stone-700">保存</button>
                        </div>
                    </form>

                    @if (!empty($usages[$item->id]))
                        <ul class="space-y-0.5 text-[11px] text-stone-500">
                            @foreach ($usages[$item->id] as $label)
                                <li>使用中: {{ $label }}</li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-[11px] text-stone-400">未使用</p>

                        <form method="POST" action="{{ route('admin.media.destroy', $item) }}"
                            onsubmit="return confirm('この画像を削除しますか？');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs text-red-500 underline hover:text-red-700">削除</button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <p class="col-span-full py-10 text-center text-sm text-stone-400">まだ画像がありません。</p>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $media->links() }}
    </div>
</x-layouts.admin>
