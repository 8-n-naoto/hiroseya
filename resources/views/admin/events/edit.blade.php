@php
    use App\Enums\PublishStatus;

    $isNew = ! $event->exists;
    $action = $isNew ? route('admin.events.store') : route('admin.events.update', $event);
@endphp

<x-layouts.admin :title="$isNew ? 'イベントを追加' : 'イベントを編集'">
    <x-admin.page-header :title="$isNew ? 'イベントを追加' : $event->title">
        <x-slot:actions>
            @unless ($isNew)
                <x-admin.button variant="secondary" :href="route('events.show', $event->slug)">ページを見る ↗</x-admin.button>
            @endunless
            <x-admin.button variant="secondary" :href="route('admin.events.index')">一覧へ戻る</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <form method="POST" action="{{ $action }}" class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
        @csrf
        @unless ($isNew) @method('PUT') @endunless

        <div class="space-y-6">
            <x-admin.card>
                <div class="space-y-5">
                    <x-admin.field label="タイトル" name="title" for="title" required>
                        <x-admin.input id="title" name="title" value="{{ old('title', $event->title) }}" required />
                    </x-admin.field>

                    <x-admin.field label="概要" name="excerpt" for="excerpt">
                        <x-admin.textarea id="excerpt" name="excerpt" rows="2">{{ old('excerpt', $event->excerpt) }}</x-admin.textarea>
                    </x-admin.field>

                    <x-admin.field label="本文" name="body" for="body" required>
                        <x-admin.textarea id="body" name="body" rows="16" required>{{ old('body', $event->body) }}</x-admin.textarea>
                    </x-admin.field>
                </div>
            </x-admin.card>

            <x-admin.card title="検索結果での見え方"
                description="空欄なら、タイトルと概要から自動で作られます。">
                <div class="space-y-5">
                    <x-admin.field label="SEOタイトル" name="seo_title" for="seo_title">
                        <x-admin.input id="seo_title" name="seo_title" value="{{ old('seo_title', $event->seoMeta?->title) }}" />
                    </x-admin.field>
                    <x-admin.field label="SEOディスクリプション" name="seo_description" for="seo_description">
                        <x-admin.textarea id="seo_description" name="seo_description" rows="3">{{ old('seo_description', $event->seoMeta?->description) }}</x-admin.textarea>
                    </x-admin.field>
                </div>
            </x-admin.card>
        </div>

        <div class="space-y-6">
            <x-admin.card title="公開と開催期間">
                <div class="space-y-4">
                    <x-admin.field label="公開状態" name="status" for="status">
                        <x-admin.select id="status" name="status" :selected="old('status', $event->status?->value)"
                            :options="PublishStatus::options()" />
                    </x-admin.field>

                    <x-admin.field label="開催開始日" name="starts_on" for="starts_on">
                        <x-admin.input type="date" id="starts_on" name="starts_on"
                            value="{{ old('starts_on', $event->starts_on?->toDateString()) }}" />
                    </x-admin.field>

                    <x-admin.field label="開催終了日" name="ends_on" for="ends_on"
                        help="この日を過ぎると自動で「終了したイベント」に移ります。">
                        <x-admin.input type="date" id="ends_on" name="ends_on"
                            value="{{ old('ends_on', $event->ends_on?->toDateString()) }}" />
                    </x-admin.field>

                    <x-admin.field label="並び順" name="sort_order" for="sort_order">
                        <x-admin.input type="number" id="sort_order" name="sort_order"
                            value="{{ old('sort_order', $event->sort_order ?? 0) }}" min="0" />
                    </x-admin.field>

                    <x-admin.field label="URL（英数字）" name="slug" for="slug">
                        <x-admin.input id="slug" name="slug" value="{{ old('slug', $event->slug) }}" />
                    </x-admin.field>
                </div>
            </x-admin.card>

            <x-admin.card title="画像">
                <x-admin.media-picker name="main_media_id" :media="$event->mainImage" label="イベントの画像" />
            </x-admin.card>

            <x-admin.button>{{ $isNew ? '追加する' : '更新する' }}</x-admin.button>
        </div>
    </form>

    @unless ($isNew)
        <form method="POST" action="{{ route('admin.events.destroy', $event) }}" class="mt-8"
            onsubmit="return confirm('このイベントを削除します。よろしいですか？');">
            @csrf @method('DELETE')
            <button class="text-sm text-red-600 underline">このイベントを削除する</button>
        </form>
    @endunless
</x-layouts.admin>
