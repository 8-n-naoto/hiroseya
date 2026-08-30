#!/usr/bin/env python3
"""
料理写真を Web 用の WebP に一括変換する。

方針:
  - 元の JPG は触らない（非破壊）。出力は web画像/ に元と同じ階層で書く。
  - 長辺 1600px / 品質 80 の WebP を 1 枚だけ作る。
    サイト用の 800 / 400 は、選抜後にアプリのアップロード処理側で生成する。
  - EXIF の向き情報を反映してから縮小する（横倒しのまま出力されるのを防ぐ）。
  - 途中で止まっても再実行すれば続きから進む。

  フォルダの走査が遅い環境（ネットワーク越しのマウントなど）でも
  再実行のたびに全件を stat しないよう、ファイル一覧と完了済みを
  キャッシュファイルに持つ。

使い方:
    python3 convert-images.py                # 変換する（最後まで）
    python3 convert-images.py --limit 200    # 200 枚だけ変換して終了
    python3 convert-images.py --status       # 進捗だけ見る
    python3 convert-images.py --rescan       # 写真を足した後、一覧を作り直す

出力先:
    <プロジェクトルート>/web画像/<元と同じ相対パス>/<ファイル名>.webp
"""
import argparse
import json
import os
import sys
import time
from concurrent.futures import ProcessPoolExecutor
from pathlib import Path

try:
    from PIL import Image, ImageOps
except ImportError:
    sys.exit('Pillow が必要です:  pip install Pillow')

# ---------------------------------------------------------------- 設定
SOURCE_DIR = '料理写真'
OUTPUT_DIR = 'web画像'
LONG_EDGE = 1600
QUALITY = 80
METHOD = 4                      # 0=速い 6=小さい。4 が実用的な折衷
EXTENSIONS = {'.jpg', '.jpeg', '.png'}
WORKERS = max(2, (os.cpu_count() or 2) * 3)     # I/O 待ちが長いので多めに

MANIFEST = '.convert-manifest.json'
DONE_LIST = '.convert-done.txt'
LOG_NAME = 'convert-images.log'

# Windows / ZoomBrowser が作る、画像ではないゴミ。変換対象外。
JUNK_NAMES = {'thumbs.db', 'zbthumbnail.info'}


def root() -> Path:
    """このスクリプトの置き場所から、料理写真のあるフォルダを探す。"""
    here = Path(__file__).resolve()
    for candidate in [here.parent, *here.parents]:
        if (candidate / SOURCE_DIR).is_dir():
            return candidate
    sys.exit(f'{SOURCE_DIR} フォルダが見つかりません。'
             f'このスクリプトを {SOURCE_DIR} と同じ階層か、その下に置いてください。')


def build_manifest(base: Path) -> list[str]:
    """料理写真を走査して、相対パスの一覧を作る。遅いので結果をキャッシュする。"""
    src_root = base / SOURCE_DIR
    files = []
    for path in sorted(src_root.rglob('*')):
        if not path.is_file():
            continue
        if path.name.lower() in JUNK_NAMES:
            continue
        if path.suffix.lower() not in EXTENSIONS:
            continue
        files.append(str(path.relative_to(src_root)))
    (base / MANIFEST).write_text(
        json.dumps({'files': files}, ensure_ascii=False), encoding='utf-8')
    return files


def load_manifest(base: Path, rescan: bool = False) -> list[str]:
    cache = base / MANIFEST
    if rescan or not cache.exists():
        print('写真の一覧を作成中…（初回のみ時間がかかります）', flush=True)
        return build_manifest(base)
    return json.loads(cache.read_text(encoding='utf-8'))['files']


def load_done(base: Path) -> set[str]:
    """完了済みの相対パス。無ければ出力フォルダから作り直す。"""
    path = base / DONE_LIST
    if path.exists():
        return set(path.read_text(encoding='utf-8').splitlines())

    out_root = base / OUTPUT_DIR
    done = set()
    if out_root.is_dir():
        print('既存の変換結果を確認中…', flush=True)
        for webp in out_root.rglob('*.webp'):
            done.add(str(webp.relative_to(out_root)))
    path.write_text('\n'.join(sorted(done)), encoding='utf-8')
    return done


def key_of(rel: str) -> str:
    """料理写真からの相対パスを、出力側の相対パス（.webp）に変換する。"""
    return str(Path(rel).with_suffix('.webp'))


def convert(job):
    src, out, rel = job
    try:
        out.parent.mkdir(parents=True, exist_ok=True)

        with Image.open(src) as im:
            # JPEG は libjpeg のデコード段階で縮小させる。
            # 4000px の写真を等倍で展開してから縮小するより大幅に速い。
            if im.format == 'JPEG':
                im.draft('RGB', (LONG_EDGE, LONG_EDGE))
            im = ImageOps.exif_transpose(im)      # 撮影時の向きを反映
            im = im.convert('RGB')
            im.thumbnail((LONG_EDGE, LONG_EDGE), Image.LANCZOS)
            tmp = out.with_suffix('.webp.part')   # 途中終了で壊れた出力を残さない
            im.save(tmp, 'WEBP', quality=QUALITY, method=METHOD)
            tmp.replace(out)

        return ('ok', rel, out.stat().st_size)
    except Exception as exc:                       # 1枚壊れていても全体は止めない
        return ('error', rel, f'{src.name}: {exc}')


def human(n):
    for unit in ('B', 'KB', 'MB', 'GB'):
        if n < 1024:
            return f'{n:.1f}{unit}'
        n /= 1024
    return f'{n:.1f}TB'


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--status', action='store_true', help='進捗だけ表示する')
    ap.add_argument('--rescan', action='store_true', help='写真の一覧を作り直す')
    ap.add_argument('--limit', type=int, default=0, help='この枚数だけ変換して終了する')
    ap.add_argument('--workers', type=int, default=WORKERS)
    args = ap.parse_args()

    base = root()
    files = load_manifest(base, args.rescan)
    done = load_done(base)

    todo = [f for f in files if key_of(f) not in done]

    if args.status:
        print(f'対象 {len(files)} 枚 / 変換済み {len(files) - len(todo)} 枚 '
              f'({100 * (len(files) - len(todo)) / max(1, len(files)):.1f}%) / '
              f'残り {len(todo)} 枚')
        return

    if args.limit:
        todo = todo[:args.limit]

    if not todo:
        print('すべて変換済みです。')
        return

    already = len(files) - len([f for f in files if key_of(f) not in done])
    print(f'対象 {len(files)} 枚 / 変換済み {already} 枚 / '
          f'今回 {len(todo)} 枚 / 並列 {args.workers}', flush=True)

    src_root = base / SOURCE_DIR
    out_root = base / OUTPUT_DIR
    jobs = [(src_root / f, out_root / key_of(f), f) for f in todo]

    start = time.time()
    ok = errors = 0
    out_bytes = 0
    failed = []
    done_fh = (base / DONE_LIST).open('a', encoding='utf-8')

    try:
        with ProcessPoolExecutor(max_workers=args.workers) as pool:
            for i, (status, rel, extra) in enumerate(pool.map(convert, jobs, chunksize=4), 1):
                if status == 'ok':
                    ok += 1
                    out_bytes += extra
                    done_fh.write(key_of(rel) + '\n')
                else:
                    errors += 1
                    failed.append(extra)

                if i % 100 == 0 or i == len(jobs):
                    done_fh.flush()
                    elapsed = time.time() - start
                    rate = i / elapsed if elapsed else 0
                    total_done = already + ok
                    remain = (len(files) - total_done) / rate if rate else 0
                    line = (f'{total_done}/{len(files)}  今回 {ok} 枚  エラー {errors}  '
                            f'{rate:.1f}枚/秒  残り {remain / 60:.1f}分')
                    print(line, flush=True)
                    (base / LOG_NAME).write_text(line + '\n', encoding='utf-8')
    finally:
        done_fh.close()

    print()
    print(f'今回: 変換 {ok} 枚 / エラー {errors} 件 / 出力 {human(out_bytes)}')
    for f in failed[:20]:
        print('  ERROR', f)


if __name__ == '__main__':
    main()
