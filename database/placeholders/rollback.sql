-- =====================================================================
--  広瀬屋 — 仮画像を全部取り消す
-- ---------------------------------------------------------------------
--  media から仮画像を消すだけでよい。
--  dishes.main_media_id / home_sections.media_id などの外部キーは
--  ON DELETE SET NULL なので、参照側は自動で NULL に戻る。
--  実ファイル（storage/app/public/placeholder/）は残るので、
--  不要なら OS 側で削除する。
-- =====================================================================

SET NAMES utf8mb4;

DELETE FROM `media` WHERE `path` LIKE 'placeholder/%';

SELECT COUNT(*) AS `残っている仮画像` FROM `media` WHERE `path` LIKE 'placeholder/%';
