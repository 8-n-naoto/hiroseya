# 実装状況

最終更新: 2026-08-30

## 完了

### Phase 1（後半）— 管理画面の基盤

| 区分 | 内容 |
| --- | --- |
| 認証 | ログイン/ログアウト/パスワード再設定。ログイン試行はメール+IPの組で5回失敗するとロック |
| 有効/無効 | `is_active=false` のユーザーは次のリクエストで即ログアウト（`active` ミドルウェア） |
| 準備中モード | `preparation` ミドルウェア。ONかつ未ログインの訪問者にのみ準備中ページを表示 |
| 共通レイアウト | `resources/views/components/layouts/admin.blade.php`。Alpineでモバイル用ドロワー |
| 画像ライブラリ | アップロード・alt編集・使用箇所表示（料理/お知らせ/イベント/トップページを横断）・未使用時のみ削除可 |
| ダッシュボード | 未確認予約・未対応問い合わせ・alt未入力件数・公開前チェックリスト（自動判定できる項目のみ判定） |
| パスワード再設定メール | 標準の英語文面を日本語に差し替え（`App\Notifications\ResetPasswordNotification`） |

ログイン確認用の初期アカウント（`UserSeeder`、要 `migrate --seed`）:
`owner@example.com` / `password`（管理者）、`editor@example.com` / `password`（編集者）。
**本番投入前に必ずパスワードを変更してください。**

公開サイトの `/` は Phase 4 まで仮ページ（旧 welcome.blade.php は `route('register')` を
参照しており未定義ルートで落ちる不具合があったため、仮の準備中案内ページに差し替え済み）。

### Phase 1（前半）— 基盤のデータ構造

| 区分 | 内容 |
| --- | --- |
| マイグレーション | 23テーブル + `users` の拡張（role / is_active / last_login_at） |
| Enum | UserRole / PublishStatus / ServiceType / ContactStatus / ReservationStatus |
| モデル | 22モデル。リレーション・キャスト・スコープまで |
| 設定基盤 | `App\Support\Settings`（キャッシュ付き・型付き・グループ単位の読み書き） |
| 権限 | Gate 定義（`manage-settings` / `manage-users` / `manage-content` / `handle-inquiries`） |
| シーダー | 13ファイル。料理64件・価格71件を含む仮データ一式 |
| 環境設定 | タイムゾーン Asia/Tokyo、ロケール ja、`.env.example` |
| 画像 | `ImageService`（アップロード時に WebP 3サイズ＋JPEGフォールバックを自動生成）。詳細は [IMAGES.md](IMAGES.md) |

### 既存写真の WebP 化（済）

JPG / PNG 3,897枚・10.96GB を長辺1600・品質80の WebP に変換し、**696MB**（6.4%）にした。
エラー0件。出力は `web画像/` に元と同じフォルダ構成のまま。変換後、元の JPG / PNG と
`Thumbs.db` / `ZbThumbnail.info` 188件は削除済み。
印刷用メニューの PSD 242点は**現行メニューの内容と価格を持つ唯一の記録**なので残してある。

### テーブル一覧

```
基盤        users(拡張) / media / settings / seo_metas / activity_logs
店舗        store_profile / business_hours / special_days
料理        dish_categories / dishes / dish_variants / dish_media
            allergens / allergen_dish
記事        news / events
トップ      home_sections / home_recommended_dishes
問い合わせ  contacts / contact_replies
予約        reservations / reservation_time_slots / reservation_slot_overrides
外部連携    social_links
```

### 設計上のポイント（実装に反映済み）

- **価格は `dishes` ではなく `dish_variants`。** 実データに「みそ煮込み / みそ煮込みセット」
  「みそかつ定食 / 単品」「二枚 / 三枚」「小 / 中 / 大」が存在するため。
- **`service_type` はバリエーション側。** かつ丼を店内880円・持ち帰り900円として
  1レコードで管理でき、写真・説明・SEOを共有できる。シーダーで実例を投入済み。
- **`available_from` / `available_to` による自動出し分け。** 冬季限定・季節商品が常態のため。
- **`has_detail_page`。** 全64品を個別URLにすると薄いページが量産されるので、
  説明文を書ける料理だけ詳細ページを持たせる。
- **営業時間は曜日別の行。** 構造化データ（openingHoursSpecification）と
  「本日営業中」判定のため、テキスト1項目にしない。
- **`preparation_mode`。** 仮の営業時間・価格が検索エンジンに登録されるのを防ぐ。
- **予約はリクエスト承認制。** システムが席の空きを正確に知る必要がなく、
  電話・店頭予約と二重管理になってもダブルブッキングが構造的に起きない。
- **キャンセルは電話のみのため `reservation_no` を発行。** 電話口での照合用。
  `tel` にも索引を張り、電話を受けながら検索できるようにしてある。

## 検証済み

- 全PHPファイルの構文チェック（`php -l`）
- `docs/check-schema.py` による静的整合性チェック（エラー0 / 警告0）
  - Fillable・リレーション・外部キー・作成順
  - 料理シーダーの slug 重複とカテゴリ参照
  - 価格60件が元データ（金額.xlsx / お持ち帰りメニュー.xlsx）と一致

## 未検証

Claude 側のサンドボックス・デバイスVMともに packagist.org への接続がブロックされており、
`composer install` を一度も実行できていません。そのため以下は**実機（お使いの仮想環境）でのみ検証可能**です。

- `composer install` が通るか（`laravel/framework` 等の依存解決）
- `php artisan migrate --seed`
- ログイン〜ダッシュボード〜画像アップロードの一連の画面動作
- `npm install && npm run build`（Tailwind v4 / Alpine.js / laravel-vite-plugin）

見つけた範囲は静的に確認済み：全PHPファイルの `php -l`、Bladeの `@if/@endif` 等の対応、
ビュー内の `route()` 呼び出しが `routes/web.php` の定義と一致すること、
コンポーネントタグ（`<x-layouts.admin>` 等）と実ファイルパスの一致。

## 次にやること

### Phase 2（着手）— SEO基盤

- [x] `robots.txt` / `sitemap.xml` を準備中モードと連動する動的ルートに変更
      （`RobotsController` / `SitemapController`。準備中はクロール全拒否・サイトマップ空）
- [ ] **要対応：`public/robots.txt`（静的ファイル）を削除してください。**
      Apache は静的ファイルが存在するとLaravelのルーティングより先にそれを返すため、
      削除しないと動的ルートが機能しません（`public/sitemap.xml` があればそれも削除）。
      このセッションでは device 側のシェルが起動せず、Claude側からは削除できませんでした。
- [ ] 公開サイトの各ページ用 SEO metaタグ・JSON-LD（LocalBusiness）コンポーネントは
      Phase 4（公開サイトのテンプレート）と合わせて実装する

### Phase 2 以降（続き）

実装設計確定書 Rev.2 の Phase 2〜11 のとおり（SEO基盤・公開サイトのテンプレート・
料理/お知らせ/イベント/予約/問い合わせの管理画面・設定画面・ユーザー管理など）。

## 未確定の確認事項

| # | 内容 |
| --- | --- |
| A1 | ロゴのベクター原本（AI / EPS / SVG）の有無 |
| A2 | ドメイン名と www の有無 |
| A3 | 電話番号・席数・アクセスの照合（ポータル掲載値のため） |
| A4 | 共用サーバーか VPS か |
| A5 | 公開サイトのCSS方針（デザイン確定まで保留） |
| A6 | 予約枠の実際の値（仮: 昼夜30分刻み・各枠10名） |
