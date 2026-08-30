<x-layouts.admin :title="'トップページ：'.$section->label()">
    <x-admin.page-header :title="'トップページ：'.$section->label()">
        <x-slot:actions>
            <x-admin.button variant="secondary" :href="route('admin.home-sections.index')">一覧へ戻る</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <form method="POST" action="{{ route('admin.home-sections.update', $section) }}" class="max-w-2xl space-y-6">
        @csrf @method('PUT')

        <x-admin.card>
            <div class="space-y-5">
                <x-admin.field label="見出し" name="title" for="title"
                    :help="$section->key === 'hero' ? 'メインビジュアルに大きく重ねて出す文です。改行できます。空欄の場合は店舗情報のキャッチコピーが使われます。' : 'この段の見出しです。'">
                    <x-admin.textarea id="title" name="title" rows="2">{{ old('title', $section->title) }}</x-admin.textarea>
                </x-admin.field>

                <x-admin.field label="小見出し" name="subtitle" for="subtitle">
                    <x-admin.input id="subtitle" name="subtitle" value="{{ old('subtitle', $section->subtitle) }}" />
                </x-admin.field>

                @if ($definition['body'] ?? false)
                    <x-admin.field label="本文" name="body" for="body" help="改行はそのまま反映されます。">
                        <x-admin.textarea id="body" name="body" rows="8">{{ old('body', $section->body) }}</x-admin.textarea>
                    </x-admin.field>
                @endif

                @if (in_array($section->key, ['recommend', 'news', 'events'], true))
                    <x-admin.field label="表示件数" name="limit" for="limit">
                        <x-admin.input type="number" id="limit" name="limit" min="1" max="12"
                            value="{{ old('limit', $section->option('limit', 3)) }}" />
                    </x-admin.field>
                @endif

                @if ($definition['image'] ?? false)
                    <x-admin.media-picker name="media_id" :media="$section->image" label="画像"
                        :help="$section->key === 'hero' ? 'パソコン向けの横長の写真を選んでください。' : null" />
                @endif

                @if ($definition['image_sp'] ?? false)
                    <x-admin.media-picker name="media_sp_id" :media="$section->imageSp"
                        label="スマートフォン用の画像"
                        help="横長の写真をスマートフォンで表示すると、料理が切れてしまいます。縦長の写真を別に設定してください。空欄なら横長の写真がそのまま使われます。" />
                @endif

                @unless ($section->isLocked())
                    <x-admin.checkbox name="is_visible" label="この段をトップページに表示する"
                        :checked="old('is_visible', $section->is_visible)" />
                @else
                    <p class="rounded-md bg-stone-50 px-3 py-2 text-xs text-stone-500">
                        この段は常に表示されます（非表示にはできません）。
                    </p>
                @endunless
            </div>
        </x-admin.card>

        <x-admin.button>更新する</x-admin.button>
    </form>
</x-layouts.admin>
