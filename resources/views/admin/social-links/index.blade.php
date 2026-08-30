<x-layouts.admin title="SNS">
    <x-admin.page-header title="SNS"
        description="URLを入力して「表示する」にチェックを入れると、フッターとトップページにリンクが出ます。URLが空欄のものは表示されません。" />

    <form method="POST" action="{{ route('admin.social-links.update') }}" class="max-w-3xl space-y-6">
        @csrf @method('PUT')

        <x-admin.card :padding="false">
            <table class="w-full text-sm">
                <thead class="border-b border-stone-200 bg-stone-50 text-xs text-stone-500">
                    <tr>
                        <th class="w-32 px-4 py-3 text-left">サービス</th>
                        <th class="px-4 py-3 text-left">URL</th>
                        <th class="w-36 px-4 py-3 text-left">表示名</th>
                        <th class="w-24 px-4 py-3 text-left">表示</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @foreach ($links as $link)
                        <tr>
                            <td class="px-4 py-3 font-medium text-stone-700">
                                {{ \App\Models\SocialLink::PLATFORMS[$link->platform] ?? $link->platform }}
                                <input type="hidden" name="links[{{ $link->id }}][sort_order]" value="{{ $link->sort_order }}">
                            </td>
                            <td class="px-4 py-3">
                                <x-admin.input name="links[{{ $link->id }}][url]" value="{{ $link->url }}"
                                    placeholder="https://" />
                                @error('links.'.$link->id.'.url')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </td>
                            <td class="px-4 py-3">
                                <x-admin.input name="links[{{ $link->id }}][display_name]" value="{{ $link->display_name }}" />
                            </td>
                            <td class="px-4 py-3">
                                <label class="flex items-center gap-2 text-sm text-stone-600">
                                    <input type="checkbox" name="links[{{ $link->id }}][is_visible]" value="1"
                                        @checked($link->is_visible) class="h-4 w-4 rounded border-stone-300">
                                    表示する
                                </label>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-admin.card>

        <x-admin.button>SNSの設定を保存する</x-admin.button>
    </form>
</x-layouts.admin>
