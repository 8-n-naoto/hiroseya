#!/bin/bash
# =====================================================================
#  広瀬屋 — 仮画像の実ファイルを storage に展開する（VM上で root で実行）
# ---------------------------------------------------------------------
#  DB にレコードを入れても、実ファイルが無ければ画像は 404 のままになる。
#  placeholder_media.sql を流す前に、これを実行しておくこと。
#
#    cd /var/www/apps/hiroseya
#    bash database/placeholders/install.sh
#
#  置かれるファイル（1つの仮画像につき5本）:
#    placeholder/hero.jpg          原本相当（管理画面の再生成用）
#    placeholder/hero_lg.webp      長辺1600  ┐
#    placeholder/hero_md.webp      長辺800   ├ <picture> の srcset
#    placeholder/hero_sm.webp      長辺400   ┘
#    placeholder/hero_md.jpg       WebP非対応環境向けフォールバック（<img src>）
# =====================================================================
set -euo pipefail

# このスクリプトの位置からアプリのルートを求める（どこから叩いても動くように）
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="$(cd "${SCRIPT_DIR}/../.." && pwd)"
DEST="${APP_DIR}/storage/app/public/placeholder"

echo "アプリ: ${APP_DIR}"
echo "展開先: ${DEST}"

mkdir -p "${DEST}"
tar xzf "${SCRIPT_DIR}/placeholders.tar.gz" -C "${DEST}"

# VM の運用に合わせる（apache:apache / ディレクトリ2775・ファイル664 / SELinux）
if id apache >/dev/null 2>&1; then
    chown -R apache:apache "${DEST}"
fi
find "${DEST}" -type d -exec chmod 2775 {} +
find "${DEST}" -type f -exec chmod 664 {} +

if command -v restorecon >/dev/null 2>&1; then
    restorecon -R "${APP_DIR}/storage" || true
fi

echo "展開したファイル数: $(find "${DEST}" -type f | wc -l)"

# public/storage が無いと /storage/... は全部 404 になるので、ここで気付けるようにする
if [ ! -e "${APP_DIR}/public/storage" ]; then
    echo
    echo "!! public/storage がありません。画像は404のままです。"
    echo "!! 次を実行してください: php artisan storage:link"
fi

echo "完了。続けて placeholder_media.sql を DB に流してください。"
