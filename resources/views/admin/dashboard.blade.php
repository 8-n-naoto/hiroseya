<x-layouts.admin title="ダッシュボード">
    @if ($inPreparation)
        <x-admin.card class="mb-6 border-amber-300 bg-amber-50">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-sm font-semibold text-amber-900">サイトは準備中モードです</h2>
                    <p class="mt-1 max-w-2xl text-sm leading-relaxed text-amber-900/80">
                        一般の訪問者には準備中ページが表示され、検索エンジンにも登録されません。
                        下のチェックリストが済んだら、「各種設定 → サイト基本」で準備中モードをOFFにしてください。
                        @if ($checklistRemaining > 0)
                            <strong>残り {{ $checklistRemaining }} 項目</strong>
                        @endif
                    </p>
                </div>
                <x-admin.button variant="secondary" :href="route('admin.settings.edit', 'site')">設定を開く</x-admin.button>
            </div>
        </x-admin.card>
    @endif

    {{-- 対応が必要なもの --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @php
            $tiles = [
                ['未対応のお問い合わせ', $pendingContactCount, route('admin.contacts.index').'?status=pending', $pendingContactCount > 0],
                ['対応中を含む未完了', $openContactCount, route('admin.contacts.index'), false],
                ['写真が無い公開料理', $dishesWithoutImage, route('admin.dishes.index').'?no_image=1', $dishesWithoutImage > 0],
                ['代替テキスト未入力の画像', $missingAltCount, route('admin.media.index').'?missing_alt=1', false],
            ];
        @endphp

        @foreach ($tiles as [$label, $count, $url, $alert])
            <a href="{{ $url }}"
                class="rounded-lg border bg-white px-5 py-4 transition hover:border-stone-400
                    {{ $alert ? 'border-red-300' : 'border-stone-200' }}">
                <p class="text-xs text-stone-500">{{ $label }}</p>
                <p class="mt-1 text-2xl font-semibold {{ $alert ? 'text-red-600' : 'text-stone-900' }}">
                    {{ number_format($count) }}<span class="ml-1 text-sm font-normal text-stone-400">件</span>
                </p>
            </a>
        @endforeach
    </div>

    @if (! $mailConfigured || ! $notifyConfigured)
        <div class="mb-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <p class="font-medium">メールの設定が未完了です。</p>
            <p class="mt-1 leading-relaxed">
                この状態ではお問い合わせが届いても通知メールが送られず、お客様への自動返信も届きません。
                <a href="{{ route('admin.settings.edit', 'mail') }}" class="underline">メール設定</a>から、
                SMTPと通知先を登録し、テスト送信で到達をご確認ください。
            </p>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- 公開前チェックリスト --}}
        <x-admin.card title="公開前チェックリスト"
            description="判定できる項目は実データから自動で確認しています。すべて済んでから準備中モードを解除してください。">
            <ul class="divide-y divide-stone-100">
                @foreach ($checklist as $item)
                    <li class="flex items-start gap-3 py-3">
                        <span class="mt-0.5 shrink-0">
                            @if ($item['done'] === true)
                                <span class="text-green-600" aria-label="完了">✓</span>
                            @elseif ($item['done'] === false)
                                <span class="text-red-500" aria-label="未完了">●</span>
                            @else
                                <span class="text-stone-300" aria-label="要確認">－</span>
                            @endif
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm {{ $item['done'] === true ? 'text-stone-400 line-through' : 'text-stone-800' }}">
                                {{ $item['label'] }}
                            </p>
                            @if ($item['done'] !== true && $item['hint'])
                                <p class="mt-0.5 text-xs leading-relaxed text-stone-500">{{ $item['hint'] }}</p>
                            @endif
                        </div>
                        @if ($item['done'] !== true && $item['route'])
                            <a href="{{ route($item['route']) }}" class="shrink-0 text-xs text-stone-500 underline">開く</a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </x-admin.card>

        <div class="space-y-6">
            {{-- 最近のお問い合わせ --}}
            <x-admin.card title="対応が終わっていないお問い合わせ">
                @if ($openContacts->isEmpty())
                    <x-admin.empty message="未対応・対応中のお問い合わせはありません。" />
                @else
                    <ul class="divide-y divide-stone-100">
                        @foreach ($openContacts as $contact)
                            <li class="py-3">
                                <a href="{{ route('admin.contacts.show', $contact) }}" class="block hover:opacity-80">
                                    <div class="flex items-center gap-2">
                                        <x-admin.status-badge :tone="$contact->status->tone()" :label="$contact->status->label()" />
                                        <span class="text-xs text-stone-400">{{ $contact->created_at?->format('n月j日 H:i') }}</span>
                                    </div>
                                    <p class="mt-1 truncate text-sm font-medium text-stone-800">
                                        {{ $contact->subject ?: '（件名なし）' }}
                                    </p>
                                    <p class="truncate text-xs text-stone-500">{{ $contact->name }} 様</p>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    <p class="mt-4 text-right">
                        <a href="{{ route('admin.contacts.index') }}" class="text-xs text-stone-500 underline">すべて見る</a>
                    </p>
                @endif
            </x-admin.card>

            <x-admin.card title="コンテンツの状況">
                <dl class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-xs text-stone-500">公開中の料理</dt>
                        <dd class="text-lg font-semibold text-stone-900">{{ $publishedDishCount }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-stone-500">下書きの料理</dt>
                        <dd class="text-lg font-semibold text-stone-900">{{ $draftDishCount }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-stone-500">下書きのお知らせ</dt>
                        <dd class="text-lg font-semibold text-stone-900">{{ $draftNewsCount }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-stone-500">公開予約中のお知らせ</dt>
                        <dd class="text-lg font-semibold text-stone-900">{{ $scheduledNewsCount }}</dd>
                    </div>
                </dl>
            </x-admin.card>
        </div>
    </div>
</x-layouts.admin>
