@php
    $site = app(\App\Support\SiteContext::class);
    $store = $site->store();
@endphp

<section class="section section--ink">
    <div class="wrap wrap--narrow" style="text-align:center;">
        <h2 style="font-size:clamp(21px,3vw,27px);letter-spacing:.18em;color:#f0ebe0;">
            {{ $section->title ?: 'お問い合わせ' }}
        </h2>

        <p style="margin-top:20px;font-size:14.5px;color:#c3bcae;">
            {{ $section->body ?: 'ご予約・仕出し・お持ち帰りのご相談は、お電話またはお問い合わせフォームより承ります。' }}
        </p>

        @if ($store->tel)
            <p style="margin-top:28px;">
                <a href="{{ $store->telLink() }}"
                    style="font-family:var(--font-mincho);font-size:clamp(28px,5vw,38px);letter-spacing:.06em;color:#f0ebe0;">
                    {{ $store->tel }}
                </a>
            </p>
            <p style="font-size:11.5px;letter-spacing:.16em;color:#9d968a;">
                受付時間　{{ collect($site->hours()->summarizedWeek())->first()['ranges'][0]['range'] ?? '営業時間内' }}
            </p>
        @endif

        <p style="margin-top:32px;display:flex;gap:14px;justify-content:center;flex-wrap:wrap;">
            <a href="{{ route('contact.create') }}" class="btn btn--light">お問い合わせフォーム</a>
            <a href="{{ route('menu.index') }}" class="btn btn--light">お品書き</a>
        </p>
    </div>
</section>
