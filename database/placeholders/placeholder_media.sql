-- =====================================================================
--  広瀬屋 — 仮画像（プレースホルダー）投入SQL
-- ---------------------------------------------------------------------
--  何をするか
--    1) media テーブルに 14 件の仮画像レコードを入れる（path は placeholder/ 配下）
--    2) home_sections / dishes / news / events の画像が未設定の行に、その仮画像を割り当てる
--
--  前提（この2つが済んでいないと画像は出ない）
--    A) 実ファイルが storage/app/public/placeholder/ に置いてあること
--         → database/placeholders/install.sh を先に実行する
--    B) php artisan storage:link が済んでいること（public/storage が無いと全部404）
--    C) php artisan db:seed --force が済んでいること
--         → dishes / home_sections が空だと 3) の UPDATE が 0 件になる
--
--  安全性
--    ・既に画像が入っている行（main_media_id IS NOT NULL）は書き換えない。
--      あとから管理画面で本番写真を入れた行を、再実行で潰さないため。
--    ・path LIKE 'placeholder/%' が仮画像の目印。戻すときは rollback.sql を流す。
--    ・何度流しても同じ状態になる（先に既存の仮画像を消してから入れ直す）。
-- =====================================================================

SET NAMES utf8mb4;
SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION';

START TRANSACTION;

-- ---------------------------------------------------------------------
-- 1. 前回の仮画像を削除（再実行できるように）
--    外部キーが ON DELETE SET NULL / CASCADE なので、
--    参照していた dishes.main_media_id などは自動で NULL に戻る。
-- ---------------------------------------------------------------------
DELETE FROM `media` WHERE `path` LIKE 'placeholder/%';

-- ---------------------------------------------------------------------
-- 2. 仮画像レコードの投入
--    width / height は ImageService が保存する「原本相当」の寸法に合わせてある。
--    <picture> は path から _sm/_md/_lg.webp と _md.jpg を組み立てて参照する。
-- ---------------------------------------------------------------------
INSERT INTO `media`
    (`disk`, `path`, `original_name`, `mime`, `size`, `width`, `height`, `alt`, `caption`, `uploaded_by`, `created_at`, `updated_at`)
VALUES
    ('public', 'placeholder/hero.jpg',              'hero.jpg',              'image/jpeg', 29369, 1600,  900, '広瀬屋の店内イメージ（仮画像）',           '仮画像／公開前に差し替える', NULL, NOW(), NOW()),
    ('public', 'placeholder/hero-sp.jpg',           'hero-sp.jpg',           'image/jpeg', 30192, 1080, 1350, '広瀬屋の店内イメージ（仮画像・スマホ用）', '仮画像／公開前に差し替える', NULL, NOW(), NOW()),
    ('public', 'placeholder/catch.jpg',             'catch.jpg',             'image/jpeg', 33399, 1600, 1067, 'キャッチコピー用の写真（仮画像）',         '仮画像／公開前に差し替える', NULL, NOW(), NOW()),
    ('public', 'placeholder/about.jpg',             'about.jpg',             'image/jpeg', 35220, 1600, 1067, '店舗紹介用の写真（仮画像）',               '仮画像／公開前に差し替える', NULL, NOW(), NOW()),
    ('public', 'placeholder/cta.jpg',               'cta.jpg',               'image/jpeg', 30713, 1600,  900, 'お問い合わせ欄の写真（仮画像）',           '仮画像／公開前に差し替える', NULL, NOW(), NOW()),
    ('public', 'placeholder/dish-hot-noodles.jpg',  'dish-hot-noodles.jpg',  'image/jpeg', 25683, 1200,  900, '温かい麺（仮画像）',                       '仮画像／公開前に差し替える', NULL, NOW(), NOW()),
    ('public', 'placeholder/dish-cold-noodles.jpg', 'dish-cold-noodles.jpg', 'image/jpeg', 26000, 1200,  900, '冷たい麺（仮画像）',                       '仮画像／公開前に差し替える', NULL, NOW(), NOW()),
    ('public', 'placeholder/dish-nikomi.jpg',       'dish-nikomi.jpg',       'image/jpeg', 23665, 1200,  900, '煮込み（仮画像）',                         '仮画像／公開前に差し替える', NULL, NOW(), NOW()),
    ('public', 'placeholder/dish-donburi.jpg',      'dish-donburi.jpg',      'image/jpeg', 22093, 1200,  900, '丼もの（仮画像）',                         '仮画像／公開前に差し替える', NULL, NOW(), NOW()),
    ('public', 'placeholder/dish-teishoku.jpg',     'dish-teishoku.jpg',     'image/jpeg', 24410, 1200,  900, '定食物（仮画像）',                         '仮画像／公開前に差し替える', NULL, NOW(), NOW()),
    ('public', 'placeholder/dish-takeout.jpg',      'dish-takeout.jpg',      'image/jpeg', 26539, 1200,  900, 'お持ち帰り（仮画像）',                     '仮画像／公開前に差し替える', NULL, NOW(), NOW()),
    ('public', 'placeholder/dish-other.jpg',        'dish-other.jpg',        'image/jpeg', 24910, 1200,  900, 'お品書き（仮画像）',                       '仮画像／公開前に差し替える', NULL, NOW(), NOW()),
    ('public', 'placeholder/news.jpg',              'news.jpg',              'image/jpeg', 21194, 1200,  800, 'お知らせ（仮画像）',                       '仮画像／公開前に差し替える', NULL, NOW(), NOW()),
    ('public', 'placeholder/event.jpg',             'event.jpg',             'image/jpeg', 18212, 1200,  800, 'イベント（仮画像）',                       '仮画像／公開前に差し替える', NULL, NOW(), NOW());

-- ---------------------------------------------------------------------
-- 3. トップページのセクションに割り当て
--    config/hiroseya.php で image=true の節だけ（hero / catch / about / cta）。
--    hero だけはスマホ用（media_sp_id）も持つ。
--    ※ 現時点の partial で実際に画像を描いているのは hero と about だけ。
--      catch / cta は管理画面で画像を持てる定義なので、あとで使えるように入れておく。
-- ---------------------------------------------------------------------
UPDATE `home_sections`
   SET `media_id` = (SELECT `id` FROM `media` WHERE `path` = 'placeholder/hero.jpg'),
       `updated_at` = NOW()
 WHERE `key` = 'hero' AND `media_id` IS NULL;

UPDATE `home_sections`
   SET `media_sp_id` = (SELECT `id` FROM `media` WHERE `path` = 'placeholder/hero-sp.jpg'),
       `updated_at` = NOW()
 WHERE `key` = 'hero' AND `media_sp_id` IS NULL;

UPDATE `home_sections`
   SET `media_id` = (SELECT `id` FROM `media` WHERE `path` = 'placeholder/catch.jpg'),
       `updated_at` = NOW()
 WHERE `key` = 'catch' AND `media_id` IS NULL;

UPDATE `home_sections`
   SET `media_id` = (SELECT `id` FROM `media` WHERE `path` = 'placeholder/about.jpg'),
       `updated_at` = NOW()
 WHERE `key` = 'about' AND `media_id` IS NULL;

UPDATE `home_sections`
   SET `media_id` = (SELECT `id` FROM `media` WHERE `path` = 'placeholder/cta.jpg'),
       `updated_at` = NOW()
 WHERE `key` = 'cta' AND `media_id` IS NULL;

-- ---------------------------------------------------------------------
-- 4. 料理に割り当て
--    カテゴリごとに絵柄を変えているので、一覧で「別の料理だ」と見分けがつく。
--    カテゴリ未設定・想定外のカテゴリは dish-other に落とす。
-- ---------------------------------------------------------------------
UPDATE `dishes` d
  LEFT JOIN `dish_categories` c ON c.`id` = d.`category_id`
   SET d.`main_media_id` = (
         SELECT m.`id` FROM `media` m
          WHERE m.`path` = CONCAT('placeholder/', CASE c.`slug`
                WHEN 'hot-noodles'      THEN 'dish-hot-noodles'
                WHEN 'men-set-hot'      THEN 'dish-hot-noodles'
                WHEN 'cold-noodles'     THEN 'dish-cold-noodles'
                WHEN 'men-set-cold'     THEN 'dish-cold-noodles'
                WHEN 'nikomi'           THEN 'dish-nikomi'
                WHEN 'winter'           THEN 'dish-nikomi'
                WHEN 'donburi'          THEN 'dish-donburi'
                WHEN 'teishoku'         THEN 'dish-teishoku'
                WHEN 'takeout-donburi'  THEN 'dish-takeout'
                WHEN 'takeout-rice'     THEN 'dish-takeout'
                WHEN 'takeout-otsumami' THEN 'dish-takeout'
                ELSE 'dish-other'
          END, '.jpg')
       ),
       d.`updated_at` = NOW()
 WHERE d.`main_media_id` IS NULL
   AND d.`deleted_at` IS NULL;

-- ---------------------------------------------------------------------
-- 5. お知らせ・イベントに割り当て
-- ---------------------------------------------------------------------
UPDATE `news`
   SET `main_media_id` = (SELECT `id` FROM `media` WHERE `path` = 'placeholder/news.jpg'),
       `updated_at` = NOW()
 WHERE `main_media_id` IS NULL AND `deleted_at` IS NULL;

UPDATE `events`
   SET `main_media_id` = (SELECT `id` FROM `media` WHERE `path` = 'placeholder/event.jpg'),
       `updated_at` = NOW()
 WHERE `main_media_id` IS NULL AND `deleted_at` IS NULL;

COMMIT;

-- ---------------------------------------------------------------------
-- 6. 結果確認（0 件の行があれば、その表がまだ空＝db:seed 未実行を疑う）
-- ---------------------------------------------------------------------
SELECT '仮画像レコード'        AS `対象`, COUNT(*) AS `件数` FROM `media`         WHERE `path` LIKE 'placeholder/%'
UNION ALL
SELECT 'トップの節（画像あり）', COUNT(*) FROM `home_sections` WHERE `media_id` IS NOT NULL
UNION ALL
SELECT '料理（画像あり）',       COUNT(*) FROM `dishes`        WHERE `main_media_id` IS NOT NULL AND `deleted_at` IS NULL
UNION ALL
SELECT '料理（画像なし）',       COUNT(*) FROM `dishes`        WHERE `main_media_id` IS NULL     AND `deleted_at` IS NULL
UNION ALL
SELECT 'お知らせ（画像あり）',   COUNT(*) FROM `news`          WHERE `main_media_id` IS NOT NULL AND `deleted_at` IS NULL
UNION ALL
SELECT 'イベント（画像あり）',   COUNT(*) FROM `events`        WHERE `main_media_id` IS NOT NULL AND `deleted_at` IS NULL;
