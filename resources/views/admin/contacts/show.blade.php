@php use App\Enums\ContactStatus; @endphp

<x-layouts.admin title="お問い合わせの詳細">
    <x-admin.page-header :title="$contact->subject ?: '（件名なし）'"
        :description="$contact->created_at?->format('Y年n月j日 H:i').' に受け付け'">
        <x-slot:actions>
            <x-admin.button variant="secondary" :href="route('admin.contacts.index')">一覧へ戻る</x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
        <div class="space-y-6">
            <x-admin.card title="お問い合わせ内容">
                <dl class="mb-5 grid gap-3 border-b border-stone-100 pb-5 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-xs text-stone-500">お名前</dt>
                        <dd>{{ $contact->name }}{{ $contact->name_kana ? '（'.$contact->name_kana.'）' : '' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-stone-500">メールアドレス</dt>
                        <dd><a href="mailto:{{ $contact->email }}" class="underline">{{ $contact->email }}</a></dd>
                    </div>
                    <div>
                        <dt class="text-xs text-stone-500">電話番号</dt>
                        <dd>{{ $contact->tel ?: '（未入力）' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-stone-500">受付日時</dt>
                        <dd>{{ $contact->created_at?->format('Y年n月j日 H:i') }}</dd>
                    </div>
                </dl>

                <p class="whitespace-pre-line text-sm leading-relaxed text-stone-800">{{ $contact->body }}</p>
            </x-admin.card>

            <x-admin.card title="お客様へ返信する"
                description="ここから送った文面はそのままお客様へ届きます。定型の挨拶は自動では付きません。">
                @if (! $mailConfigured)
                    <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        メールの設定が未完了のため、この画面からは返信できません。
                        <a href="{{ route('admin.settings.edit', 'mail') }}" class="underline">メール設定を開く</a>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.contacts.reply', $contact) }}" class="space-y-4">
                    @csrf

                    <x-admin.field label="返信内容" name="body" for="reply-body" required>
                        <x-admin.textarea id="reply-body" name="body" rows="10" required
                            placeholder="いつもありがとうございます。お問い合わせいただいた件につきまして…">{{ old('body') }}</x-admin.textarea>
                    </x-admin.field>

                    <x-admin.checkbox name="mark_done" label="送信後、対応済みにする" :checked="true" />

                    <x-admin.button>{{ $contact->email }} 宛に送信する</x-admin.button>
                </form>

                @if ($contact->replies->isNotEmpty())
                    <div class="mt-8 space-y-4 border-t border-stone-100 pt-6">
                        <p class="text-xs font-semibold text-stone-500">返信の履歴</p>

                        @foreach ($contact->replies as $reply)
                            <div class="rounded-md border px-4 py-3 {{ $reply->hasFailed() ? 'border-red-200 bg-red-50' : 'border-stone-200' }}">
                                <p class="mb-2 flex flex-wrap items-center gap-2 text-xs text-stone-500">
                                    <span>{{ $reply->created_at?->format('Y/n/j H:i') }}</span>
                                    <span>{{ $reply->user?->name ?: '－' }}</span>
                                    @if ($reply->wasSent())
                                        <x-admin.status-badge tone="ok" label="送信済み" />
                                    @else
                                        <x-admin.status-badge tone="alert" label="送信失敗" />
                                    @endif
                                </p>
                                <p class="whitespace-pre-line text-sm text-stone-700">{{ $reply->body }}</p>
                                @if ($reply->hasFailed())
                                    <p class="mt-2 text-xs text-red-700">
                                        エラー内容：{{ $reply->error_message }}<br>
                                        メール設定を確認したうえで、上のフォームから同じ内容をもう一度送信してください。
                                    </p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-admin.card>
        </div>

        <div class="space-y-6">
            <x-admin.card title="対応状況">
                <form method="POST" action="{{ route('admin.contacts.update', $contact) }}" class="space-y-4">
                    @csrf @method('PATCH')

                    <x-admin.field label="状況" name="status" for="status">
                        <x-admin.select id="status" name="status" :selected="old('status', $contact->status->value)"
                            :options="ContactStatus::options()" />
                    </x-admin.field>

                    <x-admin.field label="社内メモ" name="admin_memo" for="admin_memo"
                        help="お客様には表示されません。電話で対応した内容などを残しておくと、引き継ぎが楽になります。">
                        <x-admin.textarea id="admin_memo" name="admin_memo" rows="6">{{ old('admin_memo', $contact->admin_memo) }}</x-admin.textarea>
                    </x-admin.field>

                    <x-admin.button>保存する</x-admin.button>
                </form>
            </x-admin.card>

            <x-admin.card title="受信時の記録"
                description="迷惑投稿かどうかの判断に使います。">
                <dl class="space-y-2 text-xs text-stone-500">
                    <div>
                        <dt>IPアドレス</dt>
                        <dd class="text-stone-700">{{ $contact->ip_address ?: '－' }}</dd>
                    </div>
                    <div>
                        <dt>ブラウザ情報</dt>
                        <dd class="break-all text-stone-700">{{ $contact->user_agent ?: '－' }}</dd>
                    </div>
                </dl>
            </x-admin.card>

            <form method="POST" action="{{ route('admin.contacts.destroy', $contact) }}"
                onsubmit="return confirm('このお問い合わせを削除します。よろしいですか？');">
                @csrf @method('DELETE')
                <button class="text-sm text-red-600 underline">このお問い合わせを削除する</button>
            </form>
        </div>
    </div>
</x-layouts.admin>
