{{--
    準備中ページ。

    公開サイトのレイアウトは使わない。ここは「まだ何も見せない」ための
    ページなので、ヘッダーのメニューやフッターの店舗情報を出してしまうと
    準備中モードの意味がなくなる。
    店名と電話番号だけは出す。検索でたどり着いた方が、電話だけはかけられるように。
--}}
@php $store = \App\Models\StoreProfile::current(); @endphp
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $siteName }}｜ただいま準備中です</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <style>
        :root { --ink:#1f1b17; --soft:#544d45; --washi:#f7f3ea; --rule:#ddd3c0; --accent:#7d3129;
                --mincho:"Hiragino Mincho ProN","Yu Mincho","YuMincho","Noto Serif JP",serif;
                --gothic:"Hiragino Sans","Hiragino Kaku Gothic ProN","Yu Gothic","Noto Sans JP",system-ui,sans-serif; }
        * { box-sizing:border-box; }
        body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center;
               background:var(--washi); color:var(--ink); font-family:var(--gothic);
               line-height:1.95; letter-spacing:.06em; padding:32px 24px; }
        .box { max-width:34rem; text-align:center; }
        .name { font-family:var(--mincho); font-size:26px; letter-spacing:.32em; margin:0 0 4px;
                margin-right:-.32em; }
        .reading { font-size:10px; letter-spacing:.24em; color:#8b8177; text-transform:uppercase; }
        .rule { width:56px; height:1px; margin:28px auto; background:linear-gradient(to right,
                var(--rule) 0 19px, var(--accent) 19px 37px, var(--rule) 37px 100%); }
        h1 { font-family:var(--mincho); font-size:19px; font-weight:500; letter-spacing:.16em; margin:0 0 18px; }
        p { font-size:14px; color:var(--soft); margin:0 0 12px; white-space:pre-line; }
        .tel { display:inline-block; margin-top:26px; padding-top:22px; border-top:1px solid var(--rule); }
        .tel span { display:block; font-size:11px; letter-spacing:.18em; color:#8b8177; }
        .tel a { font-family:var(--mincho); font-size:28px; letter-spacing:.06em; color:var(--ink);
                 text-decoration:none; font-variant-numeric:tabular-nums; }
        .addr { margin-top:16px; font-size:12.5px; color:#8b8177; font-style:normal; }
    </style>
</head>
<body>
    <div class="box">
        <p class="name">{{ $siteName }}</p>
        <p class="reading">Hiroseya</p>

        <div class="rule" aria-hidden="true"></div>

        <h1>ただいまホームページを準備しております</h1>
        <p>{{ $message }}</p>

        @if ($store->tel)
            <div class="tel">
                <span>ご予約・お問い合わせ</span>
                <a href="{{ $store->telLink() }}">{{ $store->tel }}</a>
            </div>
        @endif

        @if ($store->fullAddress())
            <address class="addr">{{ $store->formattedPostalCode() }} {{ $store->fullAddress() }}</address>
        @endif
    </div>
</body>
</html>
