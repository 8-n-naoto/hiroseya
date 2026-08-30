@php use App\Enums\ContactStatus; @endphp

<x-layouts.admin title="お問い合わせ">
    <x-admin.page-header title="お問い合わせ"
        description="ホームページのフォームから届いたお問い合わせです。未対応・対応中のものが上に並びます。" />

    @if (! $mailConfigured || ! $notifyConfigured)
        <div class="mb-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            メールの設定が未完了のため、新しいお問い合わせが届いても通知メールが送られません。
            <a href="{{ route('admin.settings.edit', 'mail') }}" class="underline">メール設定を開く</a>
        </div>
    @endif

    <x-admin.card class="mb-6">
        <form method="GET" action="{{ route('admin.contacts.index') }}" class="grid gap-4 sm:grid-cols-3">
            <x-admin.field label="キーワード" name="q">
                <x-admin.input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="お名前・メール・本文" />
            </x-admin.field>
            <x-admin.field label="対応状況" name="status">
                <x-admin.select name="status" placeholder="すべて" :selected="$filters['status'] ?? null"
                    :options="ContactStatus::options()" />
            </x-admin.field>
            <div class="flex items-end gap-2">
                <x-admin.button>絞り込む</x-admin.button>
                <x-admin.button variant="secondary" :href="route('admin.contacts.index')">解除</x-admin.button>
            </div>
        </form>
    </x-admin.card>

    @if ($contacts->isEmpty())
        <x-admin.empty message="お問い合わせはありません。" />
    @else
        <x-admin.card :padding="false">
            <table class="w-full text-sm">
                <thead class="border-b border-stone-200 bg-stone-50 text-xs text-stone-500">
                    <tr>
                        <th class="w-36 px-4 py-3 text-left">受付日時</th>
                        <th class="w-28 px-4 py-3 text-left">対応状況</th>
                        <th class="px-4 py-3 text-left">件名 / お名前</th>
                        <th class="w-28 px-4 py-3 text-left">担当</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @foreach ($contacts as $contact)
                        <tr class="hover:bg-stone-50">
                            <td class="px-4 py-3 align-top text-stone-600">
                                {{ $contact->created_at?->format('Y/n/j H:i') }}
                            </td>
                            <td class="px-4 py-3 align-top">
                                <x-admin.status-badge :tone="$contact->status->tone()" :label="$contact->status->label()" />
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.contacts.show', $contact) }}"
                                    class="font-medium text-stone-800 hover:underline">
                                    {{ $contact->subject ?: '（件名なし）' }}
                                </a>
                                <p class="mt-0.5 text-xs text-stone-500">
                                    {{ $contact->name }} 様 ／ {{ $contact->email }}
                                </p>
                                <p class="mt-1 line-clamp-2 text-xs text-stone-400">
                                    {{ \Illuminate\Support\Str::limit($contact->body, 90) }}
                                </p>
                            </td>
                            <td class="px-4 py-3 align-top text-xs text-stone-500">
                                {{ $contact->assignee?->name ?: '－' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-admin.card>

        <div class="mt-6">{{ $contacts->links() }}</div>
    @endif
</x-layouts.admin>
