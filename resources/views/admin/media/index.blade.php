<x-layouts.admin title="画像ライブラリ">
    <x-admin.page-header title="画像ライブラリ"
        description="料理・お知らせ・イベント・トップページで使う画像をここにまとめます。アップロードすると、表示用のWebPが自動で作られます。">
        <x-slot:actions>
            @if ($missingAltCount > 0)
                <a href="{{ route('admin.media.index', ['missing_alt' => 1]) }}"
                    class="rounded-md border border-amber-300 bg-amber-50 px-3 py-2 text-xs text-amber-900">
                    代替テキスト未入力 {{ $missingAltCount }}件
                </a>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid gap-6 lg:grid-cols-[320px_minmax(0,1fr)]">
        <div class="space-y-6">
            <x-admin.card title="画像をアップロード"
                description="まとめて選べます。1枚あたり8MBまで、JPEG・PNG・WebPに対応しています。">
                <form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <x-admin.field label="画像ファイル" name="files" required>
                        <input type="file" name="files[]" multiple required
                            accept="image/jpeg,image/png,image/webp"
                            class="block w-full text-sm text-stone-600 file:mr-3 file:rounded-md file:border-0 file:bg-stone-800 file:px-3 file:py-2 file:text-xs file:text-white">
                    </x-admin.field>

                    <x-admin.field label="保存先" name="directory"
                        help="dishes（料理）・news（お知らせ）・home（トップページ）のように、半角英数字で分けておくと後で探しやすくなります。">
                        <x-admin.input name="directory" list="directories" value="{{ old('directory', $directory ?: 'dishes') }}" />
                        <datalist id="directories">
                            @foreach ($directories as $dir)
                                <option value="{{ $dir }}"></option>
                            @endforeach
                        </datalist>
                    </x-admin.field>

                    <x-admin.field label="代替テキスト（まとめて設定）" name="alt"
                        help="画像の内容を短く説明する文です。目の不自由な方の読み上げに使われ、検索エンジンにも画像の内容が伝わります。例：「土鍋に入った味噌煮込みうどん」">
                        <x-admin.input name="alt" value="{{ old('alt') }}" />
                    </x-admin.field>

                    <x-admin.button>アップロード</x-admin.button>
                </form>
            </x-admin.card>

            <x-admin.card title="絞り込み">
                <form method="GET" action="{{ route('admin.media.index') }}" class="space-y-4">
                    <x-admin.field label="キーワード" name="q">
                        <x-admin.input name="q" value="{{ $keyword }}" placeholder="ファイル名・代替テキスト" />
                    </x-admin.field>

                    <x-admin.field label="保存先" name="directory">
                        <x-admin.select name="directory" placeholder="すべて" :selected="$directory"
                            :options="collect($directories)->mapWithKeys(fn ($d) => [$d => $d])->all()" />
                    </x-admin.field>

                    <x-admin.checkbox name="missing_alt" label="代替テキストが未入力のものだけ" :checked="$missingAlt" />

                    <div class="flex gap-2">
                        <x-admin.button>絞り込む</x-admin.button>
                        <x-admin.button variant="secondary" :href="route('admin.media.index')">解除</x-admin.button>
                    </div>
                </form>
            </x-admin.card>
        </div>

        <div>
            @if ($media->isEmpty())
                <x-admin.empty message="画像がありません。左のフォームからアップロードしてください。" />
            @else
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($media as $item)
                        <x-admin.card :padding="false">
                            <img src="{{ $item->variantUrl('sm') }}" alt="{{ $item->alt }}" loading="lazy"
                                class="aspect-[4/3] w-full rounded-t-lg object-cover">

                            <div class="space-y-3 p-4">
                                <p class="truncate text-xs text-stone-500" title="{{ $item->path }}">
                                    {{ $item->original_name ?: $item->path }}
                                </p>

                                <form method="POST" action="{{ route('admin.media.update', $item) }}" class="space-y-2">
                                    @csrf @method('PATCH')
                                    <x-admin.input name="alt" value="{{ $item->alt }}" placeholder="代替テキスト"
                                        class="{{ $item->isMissingAlt() ? 'border-amber-400 bg-amber-50' : '' }}" />
                                    <x-admin.input name="caption" value="{{ $item->caption }}" placeholder="キャプション（任意）" />
                                    <button class="text-xs text-stone-600 underline">保存</button>
                                </form>

                                @if (! empty($usages[$item->id]))
                                    <div class="rounded bg-stone-50 px-2 py-1.5">
                                        <p class="text-[11px] font-medium text-stone-600">使用中</p>
                                        <ul class="mt-0.5 space-y-0.5 text-[11px] text-stone-500">
                                            @foreach ($usages[$item->id] as $usage)
                                                <li class="truncate">{{ $usage }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @else
                                    <form method="POST" action="{{ route('admin.media.destroy', $item) }}"
                                        onsubmit="return confirm('この画像を削除します。よろしいですか？');">
                                        @csrf @method('DELETE')
                                        <button class="text-xs text-red-600 underline">削除</button>
                                    </form>
                                @endif
                            </div>
                        </x-admin.card>
                    @endforeach
                </div>

                <div class="mt-6">{{ $media->links() }}</div>
            @endif
        </div>
    </div>
</x-layouts.admin>
