# 画像の扱い

サイトに出す画像は**必ず WebP を経由**させる。方針は2段構えになっている。

## 1. 既存写真の一括変換（済）

`convert-images.py`（プロジェクトの1つ上、`料理写真` と同じ階層）で
撮影データを Web 用の WebP に変換した。

| | |
| --- | --- |
| 変換前 | JPG / PNG 3,897枚・10.96GB |
| 変換後 | WebP 3,897枚・**696MB**（6.4%） |
| 仕様 | 長辺 1600px / 品質 80 / EXIF の向きを反映 |
| エラー | 0件 |
| 出力先 | `web画像/`（`料理写真/` と同じフォルダ構成のまま） |

変換後、元の JPG / PNG は削除済み（元データは別途コピーがある前提）。
Windows が作る `Thumbs.db` と ZoomBrowser の `ZbThumbnail.info` も188件削除した。

再実行するときは：

```bash
python3 convert-images.py            # 未変換だけ変換
python3 convert-images.py --status   # 進捗を見る
python3 convert-images.py --rescan   # 写真を足した後に一覧を作り直す
```

一覧は `.convert-manifest.json`、完了済みは `.convert-done.txt` にキャッシュしている。
ネットワーク越しのフォルダだと走査だけで40秒かかるため、毎回スキャンし直さない作りにしてある。

### 変換していないもの

| | 理由 |
| --- | --- |
| PSD 242点（約17GB） | **印刷用メニューの原本で、現行メニューの内容と価格を持つ唯一の記録。**料理データの入力元として必要なので残している |
| GIF 79点 | 紙メニュー・POP用の装飾素材（和柄・背景）でサイトには使わない |
| docx / xlsx / pdf | 画像ではない |

## 2. アップロード時の自動変換（実装済・未検証）

管理画面から上がってくる画像は `App\Services\ImageService` が処理する。
店舗の方がスマートフォンで撮った5MBの写真をそのまま公開領域に置かせない。

`dishes/misonikomi-a1b2c3.jpg` をアップロードした場合に生成されるもの：

```
dishes/misonikomi-a1b2c3.jpg        原本相当（長辺1600・JPEG）
dishes/misonikomi-a1b2c3_lg.webp    長辺 1600
dishes/misonikomi-a1b2c3_md.webp    長辺 800
dishes/misonikomi-a1b2c3_sm.webp    長辺 400
dishes/misonikomi-a1b2c3_md.jpg     WebP 非対応環境向けのフォールバック
```

- サイズと品質は `config/hiroseya.php` の `images` で変える。
- 派生ファイルのパスは規則で決まるのでDBには持たない。`Media::variantUrl('md')` が組み立てる。
- ファイル名は英数字に正規化してランダム6文字を足す。日本語ファイル名はサーバーとURLで事故る。
- 向きは `orient()` で補正する。スマートフォンの写真が横倒しで表示されるのを防ぐ。
- driver は Imagick があれば Imagick、無ければ GD。エックスサーバーの共用プランでは
  Imagick が使えないことがあるため自動で切り替える（`ImageServiceProvider`）。

サイズ設定を後から変えたときは `ImageService::regenerate()` で作り直せる。

## 3. 表示側（未実装）

`<picture>` で WebP と JPEG を出し分け、`width` / `height` を必ず出力する
（Core Web Vitals の CLS 対策）。テンプレートは Phase 4 で作る。

```blade
<picture>
  <source type="image/webp"
          srcset="{{ $media->variantUrl('sm') }} 400w,
                  {{ $media->variantUrl('md') }} 800w,
                  {{ $media->variantUrl('lg') }} 1600w"
          sizes="(max-width: 768px) 100vw, 800px">
  <img src="{{ $media->variantUrl('md', 'jpg') }}"
       alt="{{ $media->alt }}"
       width="{{ $media->width }}" height="{{ $media->height }}" loading="lazy">
</picture>
```

`media.alt` は管理画面で入力必須にはしないが、未入力の画像は
一覧で目立たせる（`Media::isMissingAlt()`）。SEO要件を満たすうえでの実務的な折衷。
