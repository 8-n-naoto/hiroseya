# 仮画像（プレースホルダー）一式

VM での動作確認用に、**実写真の代わりに置く仮画像**と、それを DB に紐付ける SQL。
公開前には管理画面から本番写真に差し替える前提で、後からまとめて外せるようにしてある。

## なぜ SQL だけでは足りないか

`media` テーブルは**画像ファイルのパスを持っているだけ**で、ファイルそのものは
`storage/app/public/` に居る。しかも `<picture>` は `path` から

* `_sm.webp` / `_md.webp` / `_lg.webp`（srcset）
* `_md.jpg`（WebP 非対応向けの `<img src>`）

を組み立てて参照する。DB にレコードを入れても実ファイルが無ければ 404 のままなので、
**`install.sh` → SQL** の順に実行する。

## 手順（VM 上で root）

```bash
cd /var/www/apps/hiroseya

# 0) 前提。まだなら先に済ませる（add-laravel-app.sh はどちらもやらない）
php artisan storage:link
php artisan db:seed --force

# 1) 仮画像の実ファイルを storage に展開
bash database/placeholders/install.sh

# 2) DB に仮画像レコードを入れて紐付ける
mysql -u <user> -p <database> < database/placeholders/placeholder_media.sql

# 3) キャッシュを捨てる
php artisan optimize:clear
```

SQL の最後に確認用の SELECT が入っている。
`料理（画像あり）` が 0 件なら、`dishes` 自体が空＝`db:seed` がまだ、ということ。

## 元に戻す

```bash
mysql -u <user> -p <database> < database/placeholders/rollback.sql
rm -rf storage/app/public/placeholder     # 実ファイルも消す場合
```

`media` から消すだけでよい。`dishes.main_media_id` などの外部キーは
`ON DELETE SET NULL` なので、参照側は自動で NULL に戻る。

## 中身

| ファイル | 用途 | 割り当て先 |
|---|---|---|
| hero / hero-sp | メインビジュアル（PC・スマホ） | `home_sections.key = 'hero'` |
| catch | キャッチコピー節 | `home_sections.key = 'catch'` |
| about | 店舗紹介節 | `home_sections.key = 'about'` |
| cta | お問い合わせ節 | `home_sections.key = 'cta'` |
| dish-hot-noodles | 温かい麺・麺セット（温） | `dishes`（カテゴリ別） |
| dish-cold-noodles | 冷たい麺・麺セット（冷） | 〃 |
| dish-nikomi | 煮込み・冬限定 | 〃 |
| dish-donburi | 丼もの | 〃 |
| dish-teishoku | 定食物 | 〃 |
| dish-takeout | 揚げ物・丼物／ライス／おつまみ | 〃 |
| dish-other | カテゴリ未設定・その他 | 〃 |
| news | お知らせ | `news` 全件 |
| event | イベント | `events` 全件 |

SQL は**画像が未設定の行にしか入れない**（`main_media_id IS NULL` 条件）。
本番写真を入れたあとに再実行しても、その行は上書きされない。
