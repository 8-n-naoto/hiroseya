# セットアップ手順

## 1. 必要なもの

| | バージョン | 備考 |
| --- | --- | --- |
| PHP | 8.4 推奨（8.3 以上必須） | Laravel 13 の要件。エックスサーバーは 8.3 / 8.4 / 8.5 を提供 |
| Composer | 2.x | |
| Node.js | 20 以上 | フロントのビルド（この段階ではまだ不要） |
| DB | MySQL 8 / MariaDB 10.5 以上 | ローカル確認は SQLite でも可 |

PHP 拡張：`ctype` `filter` `hash` `mbstring` `openssl` `session` `tokenizer` `pdo_mysql`（または `pdo_sqlite`）、
画像処理に `gd`。

## 2. ローカルで動かす

```bash
composer install
cp .env.example .env
php artisan key:generate

# SQLite で試す場合
touch database/database.sqlite

php artisan migrate --seed
php artisan storage:link
php artisan serve
```

`php artisan migrate --seed` が通れば、DBスキーマと仮データの投入まで完了です。

### 初期ユーザー（開発用）

| メールアドレス | パスワード | 権限 |
| --- | --- | --- |
| owner@example.com | password | 管理者（owner） |
| editor@example.com | password | 編集者（editor） |

**本番では `UserSeeder` を実行せず、`php artisan tinker` などで管理者を1件だけ作ってください。**

### 投入される仮データ

| 内容 | 件数 | 確度 |
| --- | --- | --- |
| 料理 | 64件 | 価格は2019〜2021年頃。**要更新** |
| 価格バリエーション | 71件 | 同上 |
| 料理カテゴリ | 11件 | 店舗が看板で使っている分類をそのまま採用 |
| アレルゲン | 8件 | 特定原材料8品目 |
| 営業時間 | 13行 | **完全に仮**（11:00-14:00 / 17:00-20:00、水曜定休） |
| 予約枠 | 72件 | **完全に仮**（昼夜30分刻み、各枠10名） |
| トップページのセクション | 10件 | 見出しは仮 |
| SNS | 4件 | すべて非表示・URL空 |
| お知らせ / イベント | 3件 / 2件 | サンプル |

店舗情報のうち、住所（〒501-0622 岐阜県揖斐郡揖斐川町脛永1784-13）・電話番号（0585-22-1437）・
席数（48席）・アクセス（養老鉄道 揖斐駅 徒歩8分）はポータル掲載値です。
**公式サイトが一次情報源になるため、公開前に店舗へ確認してください。**

## 3. 準備中モード

`settings` の `site.preparation_mode` が既定で **ON** になっています。ONの間は:

- 全ページに `noindex, nofollow` を出力
- `sitemap.xml` を空にし、`robots.txt` を全 Disallow
- 未ログインの訪問者には準備中ページを表示
- 管理画面の上部に警告バナーを常時表示（消せない）

仮の営業時間や価格が検索エンジンに登録されるのを防ぐための仕組みです。
`config/hiroseya.php` の `launch_checklist` に列挙した項目をすべて済ませてから OFF にしてください。

## 4. エックスサーバーへの配置

### 4-1. Phase 0 の検証（実装を進める前に必ず通す）

| # | 確認内容 | 失敗した場合 |
| --- | --- | --- |
| 01 | サーバーパネルで PHP 8.4 に切り替えられる | 8.3 でも動く。8.5 は様子見 |
| 02 | SSH で `php -v` / `composer -V` が通る | ローカルで `composer install` 済みの `vendor` ごとアップロード |
| 03 | アプリ本体を公開領域の外に置き、`public_html` から到達できる | 下記 4-2 の代替方式へ |
| 04 | **`.env` がブラウザから読めない** | 最優先で対処。読めるまま公開は絶対に不可 |
| 05 | `storage:link` 経由で画像が表示される | 下記 4-3 の代替方式へ |
| 06 | cron が1分間隔で登録でき、artisan が実行される | キューを使わず同期送信＋リトライへ |
| 07 | MariaDB のバージョン確認とマイグレーション実行 | — |
| 08 | **独自ドメインから Gmail 宛にテストメールが届く** | SPF / DKIM / DMARC を設定して再試行 |
| 09 | HTTPS 化と www 有無の 301 統一 | — |
| 10 | 日次バックアップ（DB + アップロード画像）の手段確保 | 手動手順を文書化 |

### 4-2. ドキュメントルート

共用サーバーはドキュメントルートを `public` に変更できないため、次のどちらかにします。

**A. シンボリックリンク（推奨）**

```
/home/USER/hiroseya/            <- アプリ本体（公開領域の外）
/home/USER/DOMAIN/public_html   -> /home/USER/hiroseya/public へのリンク
```

**B. `public` の中身を `public_html` に置く**

`public_html/index.php` の 2 箇所のパスを、公開領域外のアプリ本体に向け直します。

どちらの場合も、**`.env` と `app/` `config/` `storage/` が公開領域の外にあること**を
ブラウザで直接アクセスして確認してください。

### 4-3. 画像の保存先

既定では `storage/app/public` に保存し、`storage:link` で `/storage/...` として公開します。
共用サーバーではシンボリックリンクとドキュメントルートの組み合わせで
403 / 404 になることがあります。その場合は `.env` の `FILESYSTEM_DISK` と
`config/filesystems.php` の `public` ディスクの `root` を `public_html/uploads` に向け替えます。
保存先を設定1つで切り替えられるようにしてあるので、コードの修正は不要です。

### 4-4. cron

キューは常駐プロセスが使えないため、cron で回します。

```
* * * * * cd /home/USER/hiroseya && /usr/bin/php8.4 artisan queue:work --stop-when-empty >> /dev/null 2>&1
* * * * * cd /home/USER/hiroseya && /usr/bin/php8.4 artisan schedule:run >> /dev/null 2>&1
```

### 4-5. メール

- 独自ドメインのメールアドレスを作り、**SPF / DKIM / DMARC を設定**します（未設定だと Gmail 宛が届きません）。
- 送信元（From）は必ず自ドメイン。お客様のアドレスは `Reply-To` に入れます。
  お客様のアドレスを From にするとなりすまし判定で弾かれます。
- SMTP の設定値は `.env` ではなく **管理画面から変更できる**設計です（`settings` の `mail` グループ）。
  パスワードは `APP_KEY` で暗号化して保存されるため、**`APP_KEY` を失うと復号できません**。
  `.env` は必ずバックアップしてください。

## 5. 検証について

この段階のコードは、パッケージを取得できない環境で作成したため
`php artisan migrate --seed` を実機で通した状態ではありません。かわりに以下を確認済みです。

- 全 PHP ファイルの構文チェック（`php -l`）
- `docs/check-schema.py` による静的整合性チェック
  - モデルの `Fillable` の列がマイグレーションに実在するか
  - リレーション（belongsTo / hasMany / belongsToMany / morphOne）が使う列・中間テーブルが実在するか
  - 外部キーの参照先テーブルの存在と作成順
  - 料理シーダーの slug 重複、カテゴリ参照
  - **料理64件の価格が元データ（金額.xlsx / お持ち帰りメニュー.xlsx）と一致するか**

```bash
python3 docs/check-schema.py
```

`composer install` 後に `php artisan migrate --seed` を実行し、エラーが出たらお知らせください。
