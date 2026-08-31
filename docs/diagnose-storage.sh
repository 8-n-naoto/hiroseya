#!/usr/bin/env bash
# =====================================================================
# diagnose.sh - Laravel のアップロード画像が表示されない原因を切り分ける
#
#   読み取り専用。ファイル・設定・パーミッションを一切変更しない。
#   sudo が無くても動く（パスワード無しで使える場合のみ AVC ログを追加取得）。
#
#   使い方:
#     bash diagnose.sh [アプリのルート] [ベースURL]
#     bash diagnose.sh /var/www/apps/kakeibo http://localhost:8001
#     bash diagnose.sh                      # カレントと .env の APP_URL から自動判定
#
#   最終行の VERDICT= が原因コード。SKILL.md の対応表から fixes.md の章を選ぶ。
# =====================================================================
set -u

APP_ROOT="${1:-}"
BASE_URL="${2:-}"

# ---------- アプリのルートを決める ----------
if [ -z "$APP_ROOT" ]; then
  d="$PWD"
  while [ "$d" != "/" ]; do
    [ -f "$d/artisan" ] && APP_ROOT="$d" && break
    d="$(dirname "$d")"
  done
fi
if [ -z "$APP_ROOT" ] || [ ! -f "$APP_ROOT/artisan" ]; then
  echo "!! Laravel アプリのルートが見つかりません（artisan が無い）。第1引数でパスを指定してください。"
  echo "VERDICT=unknown_app_root"
  exit 1
fi
cd "$APP_ROOT" || exit 1
APP_ROOT="$PWD"

SUDO=""
if [ "$(id -u)" -ne 0 ] && command -v sudo >/dev/null 2>&1 && sudo -n true 2>/dev/null; then
  SUDO="sudo -n"
fi

# SELinux の状態は複数の節で使うので最初に取る
SEL_MODE="none"
command -v getenforce >/dev/null 2>&1 && SEL_MODE="$(getenforce 2>/dev/null || echo none)"
# SELinux が無い環境で ls -Z を使うとサイズ欄が "?" になって紛らわしいので切り替える
if [ "$SEL_MODE" = "none" ] || [ "$SEL_MODE" = "Disabled" ]; then
  lsd() { ls -ld "$@" 2>/dev/null; }
  lsf() { ls -l  "$@" 2>/dev/null; }
else
  lsd() { ls -ldZ "$@" 2>/dev/null; }
  lsf() { ls -lZ  "$@" 2>/dev/null; }
fi

hdr() { printf '\n===== %s =====\n' "$*"; }
NOTES=""
note() { NOTES="${NOTES}  - $*
"; }
VERDICT=""

echo "Laravel storage 診断: $(date '+%Y-%m-%d %H:%M:%S')"
echo "アプリルート: $APP_ROOT"
echo "実行ユーザー: $(id -un) (uid=$(id -u))"

# ---------- 0. 環境の判定 ----------
# ここを取り違えると不要な SELinux / Apache 作業を案内してしまうので最初に確定させる。
hdr "0. 環境の判定"
SERVER_KIND="unknown"
PROCS="$(ps -eo comm= 2>/dev/null | sort -u)"
if printf '%s' "$PROCS" | grep -qE '^(httpd|apache2)$'; then SERVER_KIND="apache"
elif printf '%s' "$PROCS" | grep -qE '^nginx$';            then SERVER_KIND="nginx"
elif ps -eo args= 2>/dev/null | grep -qE 'artisan serve|php -S';  then SERVER_KIND="builtin"
fi
echo "稼働中の Web サーバー: $SERVER_KIND"
ps -eo user=,args= 2>/dev/null | grep -E 'httpd|apache2|nginx|php-fpm|artisan serve|php -S' \
  | grep -vE 'grep|bash -c|diagnose\.sh' | cut -c1-120 | head -6
echo "OS: $( { . /etc/os-release 2>/dev/null && echo "$PRETTY_NAME"; } || uname -s )"
echo "SELinux: $SEL_MODE"
[ -n "$(command -v aa-status 2>/dev/null)" ] && echo "AppArmor: あり（Debian系。SELinux の節は読み飛ばす）"
case "$SERVER_KIND" in
  apache) echo ">> RHEL系+Apache 想定の手順（fixes.md）がそのまま使える" ;;
  nginx|builtin) echo ">> Apache ではない。references/other-envs.md の該当節も読むこと" ;;
  unknown) echo ">> Web サーバーを特定できない（コンテナ外から配信 / リバースプロキシの可能性）" ;;
esac

# ---------- 1. public/storage の状態（最重要） ----------
hdr "1. public/storage の状態"
LINK="$APP_ROOT/public/storage"
TARGET="$APP_ROOT/storage/app/public"
LINK_STATE=""
if [ -L "$LINK" ]; then
  RESOLVED="$(readlink -f "$LINK" 2>/dev/null || true)"
  lsd "$LINK"
  echo "リンク先(生): $(readlink "$LINK")"
  echo "リンク先(解決後): ${RESOLVED:-<解決不能>}"
  if [ ! -d "$LINK/" ]; then
    LINK_STATE="broken"; echo ">> シンボリックリンクが壊れています（リンク先が存在しない）"
  elif [ "$RESOLVED" != "$(readlink -f "$TARGET" 2>/dev/null)" ]; then
    LINK_STATE="wrong_target"; echo ">> リンク先が storage/app/public ではありません"
  else
    LINK_STATE="ok"; echo ">> OK: 正しいシンボリックリンクです"
  fi
elif [ -d "$LINK" ]; then
  LINK_STATE="real_dir"; lsd "$LINK"
  echo ">> シンボリックリンクではなく実ディレクトリです（storage:link が失敗した痕跡）"
elif [ -e "$LINK" ]; then
  LINK_STATE="not_dir"; lsd "$LINK"
  echo ">> ディレクトリでもリンクでもない何かが存在します"
else
  LINK_STATE="missing"
  echo "public/storage は存在しません"
  echo ">> php artisan storage:link が未実行です。/public/storage は .gitignore 対象なので"
  echo "   git clone / git pull では絶対に作られません。"
fi

# ---------- 2. 配信対象ファイルの実体 ----------
hdr "2. storage/app/public の中身"
REL=""
NEWEST=""
if [ ! -d "$TARGET" ]; then
  echo "!! $TARGET が存在しません（まだ一度もアップロードされていない可能性）"
else
  lsd "$TARGET"
  NEWEST="$(find "$TARGET" -type f -printf '%T@ %p\n' 2>/dev/null | sort -rn | head -1 | cut -d' ' -f2-)"
  if [ -n "$NEWEST" ]; then
    REL="${NEWEST#$TARGET/}"
    echo "検証に使う最新ファイル: $REL"
    lsf "$NEWEST"
  else
    echo "!! ファイルが1つもありません。先に画面から1件アップロードしてください。"
  fi
fi

# ---------- 3. HTTP 応答 ----------
hdr "3. HTTP 応答"
if [ -z "$BASE_URL" ] && [ -f .env ]; then
  BASE_URL="$(grep -E '^APP_URL=' .env | head -1 | cut -d= -f2- | tr -d '"'"'"'\r' )"
fi
if [ -z "$BASE_URL" ]; then
  PORT="$(ss -lnt 2>/dev/null | awk '{print $4}' | grep -oE '[0-9]+$' | grep -vE '^(22|25|3306|5432|6379|11211)$' | sort -un | head -1)"
  [ -n "${PORT:-}" ] && BASE_URL="http://localhost:$PORT"
fi
BASE_URL="${BASE_URL%/}"
echo "ベースURL: ${BASE_URL:-<不明>}"
HTTP_CODE=""; SERVED_BY=""; PROBE_URL=""
if [ -n "$BASE_URL" ] && [ -n "$REL" ] && command -v curl >/dev/null 2>&1; then
  PROBE_URL="$BASE_URL/storage/$REL"
  echo "GET $PROBE_URL"
  RESP="$(curl -sS -D - -o /dev/null --max-time 10 "$PROBE_URL" 2>&1)"
  echo "$RESP" | sed -n '1,12p'
  HTTP_CODE="$(printf '%s' "$RESP" | awk 'NR==1{print $2}')"
  # 応答を作ったのが Web サーバーか PHP かを見分ける。ここが切り分けの決め手。
  # Web サーバーが実ファイルを見つけられないと Laravel に流れ、Laravel が 403/404 を返す。
  if printf '%s' "$RESP" | grep -qiE 'x-powered-by: *php|set-cookie: *[a-z0-9_]*session|cache-control: *no-cache, *private'; then
    SERVED_BY="php"
    echo ">> この応答は PHP(Laravel) が生成しています = Web サーバーは実ファイルを見つけていません"
  elif [ -n "$HTTP_CODE" ]; then
    SERVED_BY="server"
    echo ">> この応答は Web サーバー自身が生成しています（PHP を経由していない）"
  fi
else
  echo "(スキップ: URL または対象ファイルが特定できませんでした)"
fi

# ---------- 4. 親ディレクトリの通過権 ----------
hdr "4. ディレクトリ通過権 (Web サーバーは全ての親に x が必要)"
if command -v namei >/dev/null 2>&1; then
  namei -l "$TARGET" 2>/dev/null
else
  ls -ld / /var /var/www "$APP_ROOT" "$APP_ROOT/storage" "$APP_ROOT/storage/app" "$TARGET" 2>/dev/null
fi
PERM_NG=""
p="$TARGET"
while [ "$p" != "/" ] && [ -n "$p" ]; do
  if [ -d "$p" ]; then
    m="$(stat -c '%A' "$p" 2>/dev/null)"
    m="${m%[+.]}"                 # ACL(+) や SELinux(.) の末尾記号を落とす
    case "${m#${m%?}}" in
      x|t) : ;;                     # other に実行権あり（t は sticky 付き）
      *) PERM_NG="${PERM_NG} $p" ;; # other に実行権なし
    esac
  fi
  p="$(dirname "$p")"
done
if [ -n "$PERM_NG" ]; then
  echo ">> other に実行権が無いディレクトリ:$PERM_NG"
  echo "   （Web サーバーのユーザーがグループ経由で通れるなら問題ない。namei の出力で確認する）"
fi

# ---------- 5. SELinux ----------
hdr "5. SELinux"
echo "モード: $SEL_MODE"
if [ "$SEL_MODE" = "Enforcing" ] || [ "$SEL_MODE" = "Permissive" ]; then
  echo "-- 期待ラベル: storage 配下 = httpd_sys_rw_content_t / public 配下 = httpd_sys_content_t"
  lsd "$APP_ROOT/storage" "$TARGET"
  [ -n "$NEWEST" ] && lsf "$NEWEST"
  [ -e "$LINK" ] && lsd "$LINK"
  echo "-- httpd 関連 boolean"
  getsebool -a 2>/dev/null | grep -E 'httpd_(can_network_connect|read_user_content|enable_homedirs|unified)\b' || true
  echo "-- 直近の AVC 拒否 (空なら SELinux は原因ではない)"
  if command -v ausearch >/dev/null 2>&1; then
    $SUDO ausearch -m avc -ts recent 2>/dev/null | tail -20 || echo "(ausearch を実行できませんでした / 拒否記録なし)"
  else
    echo "(ausearch が無い)"
  fi
else
  echo ">> SELinux は無効。ラベルや boolean をいじっても症状は変わらない。この線は除外してよい。"
fi

# ---------- 6. Web サーバー設定 ----------
hdr "6. Web サーバー設定"
CONF_HITS="$(grep -rls --include='*.conf' "$APP_ROOT/public" /etc/httpd /etc/apache2 /etc/nginx 2>/dev/null | head -5)"
OPTS_NG=""
if [ -n "$CONF_HITS" ]; then
  for c in $CONF_HITS; do
    echo "--- $c"
    grep -nE 'DocumentRoot|<Directory|Options|Require|AllowOverride|FallbackResource|root |try_files|fastcgi_param SCRIPT_FILENAME|location' "$c" 2>/dev/null | head -30
  done
  if [ "$SERVER_KIND" = "apache" ]; then
    if grep -rhE '^[[:space:]]*Options' $CONF_HITS 2>/dev/null | grep -q 'SymLinks'; then
      echo ">> Options にシンボリックリンク追従の指定あり"
    else
      OPTS_NG="yes"
      echo ">> Options に FollowSymLinks / SymLinksIfOwnerMatch が見当たりません（403 の典型原因）"
    fi
  fi
else
  echo "(このアプリを指す設定ファイルを特定できませんでした)"
  echo "   次の一手: Apache なら 'apachectl -S' で使用中の vhost ファイルを確認する。"
  echo "            nginx なら 'nginx -T | grep -n -A20 server_name' で実効設定を確認する。"
  echo "            コンテナなら設定はイメージ内にある。"
fi

# ---------- 7. root 所有ファイルの混入 ----------
hdr "7. root 所有ファイルの混入 (sudo php artisan を実行した痕跡)"
APP_OWNER="$(stat -c '%U' "$APP_ROOT/artisan" 2>/dev/null)"
echo "アプリの所有ユーザー: ${APP_OWNER:-不明}"
if [ "$APP_OWNER" = "root" ]; then
  echo "(アプリ全体が root 所有の構成。この検査は意味を持たないのでスキップ)"
else
  ROOT_OWNED="$(find "$APP_ROOT/storage/framework" "$APP_ROOT/storage/logs" "$APP_ROOT/bootstrap/cache" \
                  ! -user "$APP_OWNER" -user root 2>/dev/null | head -10)"
  if [ -n "$ROOT_OWNED" ]; then
    echo "!! root 所有のファイルがあります。Web サーバーが書き込めず別の不具合を招きます:"
    echo "$ROOT_OWNED"
    note "root 所有ファイルの是正が必要 (fixes.md 「E. root 所有ファイルの掃除」)"
  else
    echo "OK: root 所有ファイルはありません"
  fi
fi

# ---------- 判定 ----------
hdr "判定"
case "$LINK_STATE" in
  missing)          VERDICT="missing_link" ;;
  broken)           VERDICT="broken_link" ;;
  wrong_target)     VERDICT="wrong_target" ;;
  real_dir|not_dir) VERDICT="real_dir_in_place" ;;
  ok)
    if [ -z "$REL" ]; then
      VERDICT="no_files_yet"
    elif [ "$HTTP_CODE" = "200" ]; then
      VERDICT="ok"
    elif [ "$SERVED_BY" = "php" ]; then
      VERDICT="fallthrough_to_laravel"
    elif [ "$HTTP_CODE" = "403" ]; then
      if [ -n "$OPTS_NG" ]; then VERDICT="apache_options"
      elif [ "$SEL_MODE" = "Enforcing" ]; then VERDICT="selinux_or_perm"
      else VERDICT="perm" ; fi
    elif [ "$HTTP_CODE" = "404" ]; then
      VERDICT="not_found"
    else
      VERDICT="inconclusive"
    fi
    ;;
  *) VERDICT="inconclusive" ;;
esac

case "$VERDICT" in
  ok)                    echo "画像は正常に配信されています (HTTP 200)。表示されないなら原因は Web サーバーの外側。" ;;
  missing_link)          echo "原因: public/storage シンボリックリンクが存在しない。→ fixes.md 「A. リンクを作る」" ;;
  broken_link)           echo "原因: シンボリックリンクが壊れている。→ fixes.md 「A. リンクを作る」" ;;
  wrong_target)          echo "原因: リンク先が storage/app/public を指していない。→ fixes.md 「A. リンクを作る」" ;;
  real_dir_in_place)     echo "原因: public/storage が実ディレクトリ/ファイルとして存在する。→ fixes.md 「B. 実ディレクトリを退避して張り直す」" ;;
  fallthrough_to_laravel)echo "原因: リンクはあるが Web サーバーが実ファイルを配信せず Laravel に流れている。→ fixes.md 「C. シンボリックリンク追従を許可する」" ;;
  apache_options)        echo "原因: Apache の Options に FollowSymLinks が無い。→ fixes.md 「C. シンボリックリンク追従を許可する」" ;;
  selinux_or_perm)       echo "原因: SELinux ラベルまたはパーミッション。→ fixes.md 「D. SELinux とパーミッション」" ;;
  perm)                  echo "原因: パーミッション（通過権/読み取り権）。→ fixes.md 「D. SELinux とパーミッション」" ;;
  not_found)             echo "原因: 404。リンク先にファイルが無いか URL のパスが違う。→ fixes.md 「F. 404 のとき」" ;;
  no_files_yet)          echo "storage/app/public にファイルがまだありません。1件アップロードしてから再実行してください。" ;;
  *)                     echo "自動判定できませんでした。上の各節を読んで切り分けてください。" ;;
esac
[ -n "$NOTES" ] && { echo "付随して直すべき点:"; printf '%s' "$NOTES"; }
[ -n "$PROBE_URL" ] && echo "修正後の確認コマンド: curl -sI \"$PROBE_URL\" | head -5"

echo
echo "VERDICT=$VERDICT"
