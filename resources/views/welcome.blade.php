{{--
    公開サイトの本実装は Phase 4 で行う（実装設計確定書 Rev.2 参照）。
    これは Laravel 標準の welcome ページに代わる、開発中であることが分かる仮のトップページ。
    admin.dashboard へのリンクだけを置いている（route('register') など存在しないルートは参照しない）。
--}}
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>広瀬屋 - 準備中</title>
</head>
<body style="font-family: system-ui, sans-serif; display:flex; min-height:100vh; align-items:center; justify-content:center; background:#f5f5f4; color:#292524;">
    <div style="text-align:center; max-width:28rem; padding:0 1.5rem;">
        <p style="font-size:.875rem; color:#78716c; margin-bottom:.5rem;">広瀬屋</p>
        <h1 style="font-size:1.5rem; font-weight:600; margin-bottom:1rem;">公開サイトは準備中です</h1>
        <p style="color:#57534e; margin-bottom:1.5rem;">
            公開サイトのテンプレートは Phase 4 で実装予定です（現在は管理画面の基盤を構築中）。
        </p>
        <a href="{{ route('login') }}" style="color:#57534e; text-decoration:underline;">管理画面ログイン</a>
    </div>
</body>
</html>
