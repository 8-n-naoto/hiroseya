# 実装状況

最終更新: 2026-08-26

## 完了

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

`composer install` ができない環境で作成したため、**`php artisan migrate --seed` を
実機で通していません。** 最初に実行してエラーが出たらお知らせください。

## 次にやること

### Phase 1（後半）— 管理画面の基盤

- [ ] 管理画面の認証（ログイン / ログアウト / パスワード再設定 / ログイン試行制限）
- [ ] 管理画面の共通レイアウト（モバイル対応）
- [ ] 画像ライブラリの画面（アップロード・alt入力・使用箇所表示）※変換処理は `ImageService` に実装済み
- [ ] 準備中モードのミドルウェア
- [ ] ダッシュボード（未確認の予約 / 未対応の問い合わせ / 公開前チェックリスト）

### Phase 2 以降

実装設計確定書 Rev.2 の Phase 2〜11 のとおり。

## 未確定の確認事項

| # | 内容 |
| --- | --- |
| A1 | ロゴのベクター原本（AI / EPS / SVG）の有無 |
| A2 | ドメイン名と www の有無 |
| A3 | 電話番号・席数・アクセスの照合（ポータル掲載値のため） |
| A4 | 共用サーバーか VPS か |
| A5 | 公開サイトのCSS方針（デザイン確定まで保留） |
| A6 | 予約枠の実際の値（仮: 昼夜30分刻み・各枠10名） |
