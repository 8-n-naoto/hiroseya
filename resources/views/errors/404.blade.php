{{--
    404。標準のエラーページのままだと英語で出てしまい、
    「壊れている」という印象を与える。サイトの見た目のまま案内し、
    お品書き・アクセス・お問い合わせへ戻れるようにする。

    準備中モードの間は、存在しないURLからサイトの中身が見えてしまわないよう、
    準備中ページと同じ扱いにする。
--}}
@php
    $settings = app(\App\Support\Settings::class);
@endphp

@if ($settings->inPreparation() && auth()->guest())
    @include('preparing', [
        'siteName' => $settings->string('site.site_name', '広瀬屋'),
        'message' => $settings->string('site.preparation_message', 'ただいまホームページを準備しております。'),
    ])
@else
<x-layouts.site>
    <div class="section">
        <div class="wrap wrap--narrow" style="text-align:center;">
            <p style="font-family:var(--font-mincho);font-size:56px;letter-spacing:.1em;color:var(--rule-strong);">404</p>
            <h1 style="font-size:clamp(20px,3vw,26px);letter-spacing:.14em;margin-top:12px;">
                お探しのページが見つかりませんでした
            </h1>
            <p style="margin-top:20px;font-size:14px;color:var(--ink-soft);">
                ページが移動または削除された可能性があります。<br>
                お手数ですが、下のご案内からお進みください。
            </p>
            <p style="margin-top:40px;display:flex;gap:14px;justify-content:center;flex-wrap:wrap;">
                <a href="{{ route('home') }}" class="btn">トップページ</a>
                <a href="{{ route('menu.index') }}" class="btn">お品書き</a>
                <a href="{{ route('contact.create') }}" class="btn">お問い合わせ</a>
            </p>
        </div>
    </div>
</x-layouts.site>
@endif
