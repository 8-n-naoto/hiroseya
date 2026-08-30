@php
    use App\Enums\ServiceType;

    $isNew = ! $category->exists;
    $action = $isNew ? route('admin.dish-categories.store') : route('admin.dish-categories.update', $category);
@endphp

<x-layouts.admin :title="$isNew ? '分類を追加' : '分類を編集'">
    <x-admin.page-header :title="$isNew ? '分類を追加' : $category->name">
        <x-slot:actions>
            <x-admin.button variant="secondary" :href="route('admin.dish-categories.index')">一覧へ戻る</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <form method="POST" action="{{ $action }}" class="max-w-2xl space-y-6">
        @csrf
        @unless ($isNew) @method('PUT') @endunless

        <x-admin.card>
            <div class="space-y-5">
                <x-admin.field label="分類名" name="name" for="name" required>
                    <x-admin.input id="name" name="name" value="{{ old('name', $category->name) }}" required />
                </x-admin.field>

                <x-admin.field label="提供区分" name="service_type" for="service_type"
                    help="お品書きのどちらのページに、この見出しを先に出すかを決めます。">
                    <x-admin.select id="service_type" name="service_type"
                        :selected="old('service_type', $category->service_type?->value)"
                        :options="ServiceType::options()" />
                </x-admin.field>

                <x-admin.field label="説明" name="description" for="description"
                    help="見出しの右側に小さく出ます（任意）。">
                    <x-admin.input id="description" name="description" value="{{ old('description', $category->description) }}" />
                </x-admin.field>

                <x-admin.field label="URL（英数字）" name="slug" for="slug"
                    help="お品書きページ内の見出しへのリンクに使います。空欄なら自動で決まります。">
                    <x-admin.input id="slug" name="slug" value="{{ old('slug', $category->slug) }}" placeholder="hot-noodles" />
                </x-admin.field>

                <x-admin.field label="並び順" name="sort_order" for="sort_order">
                    <x-admin.input type="number" id="sort_order" name="sort_order"
                        value="{{ old('sort_order', $category->sort_order ?? 0) }}" min="0" />
                </x-admin.field>

                <x-admin.checkbox name="is_visible" label="お品書きに表示する"
                    :checked="old('is_visible', $category->is_visible ?? true)" />
            </div>
        </x-admin.card>

        <div class="flex items-center justify-between">
            <x-admin.button>{{ $isNew ? '追加する' : '更新する' }}</x-admin.button>

            @unless ($isNew)
                <button form="delete-category" class="text-sm text-red-600 underline">この分類を削除する</button>
            @endunless
        </div>
    </form>

    @unless ($isNew)
        <form id="delete-category" method="POST" action="{{ route('admin.dish-categories.destroy', $category) }}"
            onsubmit="return confirm('この分類を削除します。よろしいですか？');">
            @csrf @method('DELETE')
        </form>
    @endunless
</x-layouts.admin>
