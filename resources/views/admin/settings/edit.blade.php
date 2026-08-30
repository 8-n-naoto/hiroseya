<x-layouts.admin :title="'設定：'.$groups[$group][0]">
    <x-admin.page-header :title="'設定：'.$groups[$group][0]" :description="$groups[$group][1]" />

    <div class="grid gap-6 lg:grid-cols-[220px_minmax(0,1fr)]">
        <nav class="space-y-1">
            @foreach ($groups as $key => [$label, $description])
                <a href="{{ route('admin.settings.edit', $key) }}"
                    class="block rounded-md px-3 py-2 text-sm transition
                        {{ $key === $group ? 'bg-stone-800 font-medium text-white' : 'text-stone-600 hover:bg-stone-100' }}">
                    {{ $label }}
                </a>
            @endforeach
        </nav>

        <div class="space-y-6">
            @if ($group === 'site')
                <div class="rounded-md border border-stone-200 bg-white px-4 py-3 text-sm leading-relaxed text-stone-600">
                    <strong class="text-stone-800">準備中モードについて。</strong>
                    ONの間、一般の訪問者には準備中ページだけが表示され、robots.txt と sitemap.xml も
                    「クロールしないでください」という内容になります。ログイン中の方には通常どおり表示されるので、
                    公開前の確認はこのままできます。ダッシュボードのチェックリストがすべて済んでからOFFにしてください。
                </div>
            @endif

            @if ($group === 'mail')
                <div class="rounded-md border border-stone-200 bg-white px-4 py-3 text-sm leading-relaxed text-stone-600">
                    <strong class="text-stone-800">送信元アドレスは必ず自ドメインのものにしてください。</strong>
                    お客様のアドレスやフリーメールを送信元にすると、なりすましと判定されて相手に届きません。
                    設定を保存したら、下のテスト送信で実際に届くことを必ず確認してください。
                </div>
            @endif

            <form method="POST" action="{{ route('admin.settings.update', $group) }}">
                @csrf @method('PUT')

                <x-admin.card>
                    <div class="space-y-6">
                        @foreach ($definitions as $key => $definition)
                            @php
                                $type = $definition['type'] ?? 'string';
                                $value = $values[$key] ?? ($definition['default'] ?? null);
                                $name = 'settings['.$key.']';
                            @endphp

                            @if ($type === 'bool')
                                <x-admin.checkbox :name="$name" :label="$definition['label']"
                                    :help="$definition['help'] ?? null" :checked="(bool) $value" />
                            @elseif ($type === 'text')
                                <x-admin.field :label="$definition['label']" :name="'settings.'.$key"
                                    :help="$definition['help'] ?? null">
                                    <x-admin.textarea :name="$name" rows="4">{{ old('settings.'.$key, $value) }}</x-admin.textarea>
                                </x-admin.field>
                            @elseif ($type === 'encrypted')
                                <x-admin.field :label="$definition['label']" :name="'settings.'.$key"
                                    help="保存済みの値は表示されません。変更するときだけ入力してください。空欄のまま保存すると、今の値がそのまま残ります。">
                                    <x-admin.input type="password" :name="$name" value="" autocomplete="new-password"
                                        placeholder="{{ $value ? '（設定済み）' : '（未設定）' }}" />
                                </x-admin.field>
                            @else
                                <x-admin.field :label="$definition['label']" :name="'settings.'.$key"
                                    :help="$definition['help'] ?? null">
                                    <x-admin.input :type="$type === 'int' ? 'number' : 'text'" :name="$name"
                                        value="{{ old('settings.'.$key, $value) }}" />
                                </x-admin.field>
                            @endif
                        @endforeach
                    </div>
                </x-admin.card>

                <div class="mt-6">
                    <x-admin.button>設定を保存する</x-admin.button>
                </div>
            </form>

            @if ($group === 'mail')
                <x-admin.card title="テスト送信"
                    description="設定を保存してから実行してください。受信箱に届かない場合は、迷惑メールフォルダもご確認ください。">
                    @unless ($mailConfigured)
                        <p class="mb-4 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                            SMTPホストと送信元アドレスを保存すると、テスト送信できるようになります。
                        </p>
                    @endunless

                    <form method="POST" action="{{ route('admin.settings.mail.test') }}" class="flex flex-wrap items-end gap-3">
                        @csrf
                        <div class="min-w-64 flex-1">
                            <x-admin.field label="送信先" name="to" for="test-to">
                                <x-admin.input type="email" id="test-to" name="to"
                                    value="{{ old('to', auth()->user()?->email) }}" required />
                            </x-admin.field>
                        </div>
                        <x-admin.button variant="secondary">テストメールを送る</x-admin.button>
                    </form>
                </x-admin.card>
            @endif

            @if ($group === 'access')
                <x-admin.card title="地図の埋め込みコードの取り方">
                    <ol class="list-inside list-decimal space-y-1.5 text-sm leading-relaxed text-stone-600">
                        <li>Googleマップでお店を検索します。</li>
                        <li>「共有」をクリックし、「地図を埋め込む」タブを開きます。</li>
                        <li>「HTMLをコピー」を押して、上の「Googleマップの埋め込みコード」に貼り付けます。</li>
                    </ol>
                    <p class="mt-3 text-xs text-stone-500">APIキーの取得や課金の設定は不要です。</p>
                </x-admin.card>
            @endif

            @if ($group === 'reservation')
                <div class="rounded-md border border-stone-200 bg-white px-4 py-3 text-sm leading-relaxed text-stone-600">
                    サイト内の予約受付は現在準備中です。設定を保存しても、予約ページはまだ公開されません。
                    ご予約は引き続きお電話でお受けください。
                </div>
            @endif
        </div>
    </div>
</x-layouts.admin>
