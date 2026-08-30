<x-layouts.site>
    <x-site.page-head reading="Contact" title="お問い合わせ"
        lead="ご不明な点、仕出し・お持ち帰りのご相談などをお受けしております。お急ぎの場合はお電話ください。" />

    <div class="section">
        <div class="wrap wrap--narrow">
            @if ($errors->any())
                <div class="alert alert--error" role="alert">
                    入力内容をご確認ください。
                </div>
            @endif

            @if ($store->tel)
                <p style="text-align:center;margin-bottom:44px;font-size:14px;color:var(--ink-soft);">
                    お電話でのお問い合わせ<br>
                    <a href="{{ $store->telLink() }}"
                        style="font-family:var(--font-mincho);font-size:30px;letter-spacing:.05em;color:var(--ink);">
                        {{ $store->tel }}
                    </a>
                </p>
            @endif

            <form method="POST" action="{{ route('contact.store') }}" data-guard novalidate>
                @csrf

                {{-- ハニーポット。自動投稿だけがここを埋める。 --}}
                <div aria-hidden="true" style="position:absolute;left:-9999px;" tabindex="-1">
                    <label>ウェブサイト<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                </div>

                <div class="form-row">
                    <label class="form-label" for="name">お名前 <span class="form-required">必須</span></label>
                    <input type="text" id="name" name="name" class="input" required autocomplete="name"
                        value="{{ old('name') }}">
                    @error('name')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-row">
                    <label class="form-label" for="name_kana">ふりがな <span class="form-optional">任意</span></label>
                    <input type="text" id="name_kana" name="name_kana" class="input" value="{{ old('name_kana') }}">
                    @error('name_kana')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-row">
                    <label class="form-label" for="email">メールアドレス <span class="form-required">必須</span></label>
                    <input type="email" id="email" name="email" class="input" required autocomplete="email"
                        inputmode="email" value="{{ old('email') }}">
                    <p class="form-help">ご入力のアドレスへ、確認のメールを自動でお送りします。</p>
                    @error('email')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-row">
                    <label class="form-label" for="tel">電話番号 <span class="form-optional">任意</span></label>
                    <input type="tel" id="tel" name="tel" class="input" autocomplete="tel" inputmode="tel"
                        value="{{ old('tel') }}">
                    <p class="form-help">お急ぎのご用件の場合は、お電話でのご連絡が確実です。</p>
                    @error('tel')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-row">
                    <label class="form-label" for="subject">件名 <span class="form-optional">任意</span></label>
                    <input type="text" id="subject" name="subject" class="input" value="{{ old('subject') }}">
                    @error('subject')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-row">
                    <label class="form-label" for="body">お問い合わせ内容 <span class="form-required">必須</span></label>
                    <textarea id="body" name="body" class="textarea" required>{{ old('body') }}</textarea>
                    @error('body')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-row">
                    <label style="display:flex;align-items:flex-start;gap:12px;font-size:14px;cursor:pointer;">
                        <input type="checkbox" name="agree" value="1" style="margin-top:6px;" {{ old('agree') ? 'checked' : '' }}>
                        <span>
                            <a href="{{ route('privacy') }}" target="_blank" rel="noopener"
                                style="border-bottom:1px solid;">プライバシーポリシー</a>に同意します。
                        </span>
                    </label>
                    @error('agree')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn--accent">この内容で送信する</button>
                    <p class="form-help" style="margin-top:16px;">
                        送信後、確認のメールが届かない場合は迷惑メールフォルダをご確認ください。
                    </p>
                </div>
            </form>
        </div>
    </div>
</x-layouts.site>
