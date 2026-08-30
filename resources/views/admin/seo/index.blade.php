<x-layouts.admin title="ページのSEO">
    <x-admin.page-header title="ページのSEO"
        description="検索結果に出るタイトルと説明文を、ページごとに設定できます。空欄のままでも自動で作られるので、無理に埋める必要はありません。むしろ、全ページに同じ文章を入れると検索エンジンの評価が下がります。" />

    @if ($inPreparation)
        <div class="mb-6 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            準備中モードの間は、ここで設定した内容にかかわらず、すべてのページが検索エンジンに登録されない設定（noindex）になります。
        </div>
    @endif

    <x-admin.card class="mb-6" title="全体の既定値">
        <dl class="space-y-2 text-sm">
            <div class="flex flex-wrap gap-2">
                <dt class="w-40 text-stone-500">既定のタイトル</dt>
                <dd class="text-stone-800">{{ $defaults['title'] ?: '（未設定）' }}</dd>
            </div>
            <div class="flex flex-wrap gap-2">
                <dt class="w-40 text-stone-500">タイトルの接尾辞</dt>
                <dd class="text-stone-800">{{ $defaults['suffix'] ?: '（なし）' }}</dd>
            </div>
            <div class="flex flex-wrap gap-2">
                <dt class="w-40 text-stone-500">既定の説明文</dt>
                <dd class="text-stone-800">{{ $defaults['description'] ?: '（未設定）' }}</dd>
            </div>
        </dl>
        <p class="mt-3 text-xs text-stone-500">
            既定値の変更は<a href="{{ route('admin.settings.edit', 'seo') }}" class="underline">各種設定 → SEO</a>から行えます。
        </p>
    </x-admin.card>

    <form method="POST" action="{{ route('admin.seo.update') }}" class="space-y-6">
        @csrf @method('PUT')

        @foreach ($pages as $key => [$label, $path])
            @php $meta = $metas[$key] ?? null; @endphp

            <x-admin.card :title="$label" :description="$path">
                <div class="space-y-5">
                    <x-admin.field label="タイトル" :name="'pages.'.$key.'.title'"
                        help="検索結果の見出しになります。30文字前後が目安です。店名は自動で後ろに付きます。">
                        <x-admin.input name="pages[{{ $key }}][title]"
                            value="{{ old('pages.'.$key.'.title', $meta?->title) }}" />
                    </x-admin.field>

                    <x-admin.field label="説明文" :name="'pages.'.$key.'.description'"
                        help="検索結果の本文になります。120文字前後が目安です。ページごとに違う内容にしてください。">
                        <x-admin.textarea name="pages[{{ $key }}][description]" rows="3">{{ old('pages.'.$key.'.description', $meta?->description) }}</x-admin.textarea>
                    </x-admin.field>

                    <x-admin.media-picker :name="'pages['.$key.'][og_image_media_id]'" :media="$meta?->ogImage"
                        label="SNS共有時の画像"
                        help="LINEやXでこのページが共有されたときに出る画像です。未設定でも構いません。" />
                </div>
            </x-admin.card>
        @endforeach

        <x-admin.button>SEO設定を保存する</x-admin.button>
    </form>
</x-layouts.admin>
