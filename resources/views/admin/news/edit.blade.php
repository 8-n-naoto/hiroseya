@php
    use App\Enums\PublishStatus;

    $isNew = ! $news->exists;
    $action = $isNew ? route('admin.news.store') : route('admin.news.update', $news);
@endphp

<x-layouts.admin :title="$isNew ? 'お知らせを書く' : 'お知らせを編集'">
    <x-admin.page-header :title="$isNew ? 'お知らせを書く' : $news->title">
        <x-slot:actions>
            @unless ($isNew)
                <x-admin.button variant="secondary" :href="route('news.show', $news->slug)">
                    ページを見る ↗
                </x-admin.button>
            @endunless
            <x-admin.button variant="secondary" :href="route('admin.news.index')">一覧へ戻る</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <form method="POST" action="{{ $action }}" class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
        @csrf
        @unless ($isNew) @method('PUT') @endunless

        <div class="space-y-6">
            <x-admin.card>
                <div class="space-y-5">
                    <x-admin.field label="タイトル" name="title" for="title" required>
                        <x-admin.input id="title" name="title" value="{{ old('title', $news->title) }}" required />
                    </x-admin.field>

                    <x-admin.field label="概要" name="excerpt" for="excerpt"
                        help="一覧に出る短い説明です。空欄でも構いません。">
                        <x-admin.textarea id="excerpt" name="excerpt" rows="2">{{ old('excerpt', $news->excerpt) }}</x-admin.textarea>
                    </x-admin.field>

                    <x-admin.field label="本文" name="body" for="body" required
                        help="改行はそのまま反映されます。">
                        <x-admin.textarea id="body" name="body" rows="16" required>{{ old('body', $news->body) }}</x-admin.textarea>
                    </x-admin.field>
                </div>
            </x-admin.card>

            <x-admin.card title="検索結果での見え方"
                description="空欄なら、タイトルと概要から自動で作られます。特に理由がなければ空欄のままで構いません。">
                <div class="space-y-5">
                    <x-admin.field label="SEOタイトル" name="seo_title" for="seo_title">
                        <x-admin.input id="seo_title" name="seo_title"
                            value="{{ old('seo_title', $news->seoMeta?->title) }}" />
                    </x-admin.field>

                    <x-admin.field label="SEOディスクリプション" name="seo_description" for="seo_description"
                        help="検索結果に出る説明文です。120文字前後が目安です。">
                        <x-admin.textarea id="seo_description" name="seo_description" rows="3">{{ old('seo_description', $news->seoMeta?->description) }}</x-admin.textarea>
                    </x-admin.field>
                </div>
            </x-admin.card>
        </div>

        <div class="space-y-6">
            <x-admin.card title="公開">
                <div class="space-y-4">
                    <x-admin.field label="公開状態" name="status" for="status">
                        <x-admin.select id="status" name="status" :selected="old('status', $news->status?->value)"
                            :options="PublishStatus::options()" />
                    </x-admin.field>

                    <x-admin.field label="公開日時" name="published_at" for="published_at"
                        help="先の日時にすると、その時刻になってから自動で公開されます。">
                        <x-admin.input type="datetime-local" id="published_at" name="published_at"
                            value="{{ old('published_at', $news->published_at?->format('Y-m-d\TH:i')) }}" />
                    </x-admin.field>

                    <x-admin.field label="URL（英数字）" name="slug" for="slug"
                        help="空欄なら自動で決まります。公開後は変更しないでください。">
                        <x-admin.input id="slug" name="slug" value="{{ old('slug', $news->slug) }}" />
                    </x-admin.field>
                </div>
            </x-admin.card>

            <x-admin.card title="画像">
                <x-admin.media-picker name="main_media_id" :media="$news->mainImage" label="お知らせの画像"
                    help="一覧と記事の先頭、SNS共有時に使われます。" />
            </x-admin.card>

            <x-admin.button>{{ $isNew ? '保存する' : '更新する' }}</x-admin.button>
        </div>
    </form>

    @unless ($isNew)
        <form method="POST" action="{{ route('admin.news.destroy', $news) }}" class="mt-8"
            onsubmit="return confirm('このお知らせを削除します。よろしいですか？');">
            @csrf @method('DELETE')
            <button class="text-sm text-red-600 underline">このお知らせを削除する</button>
        </form>
    @endunless
</x-layouts.admin>
