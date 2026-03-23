-- ============================================================
-- MetaBorghi — Migration V5: Cover Video URL for Boroughs
-- Aggiunge colonna cover_video_url alla tabella boroughs
-- per supportare video YouTube come alternativa alla copertina
--
-- NOTA: Questa migrazione viene eseguita automaticamente
-- dall'API boroughs.php al primo caricamento (ensureTableColumns).
-- Eseguire manualmente via phpMyAdmin solo se necessario.
-- ============================================================

SET NAMES utf8mb4;

ALTER TABLE `boroughs`
  ADD COLUMN IF NOT EXISTS `cover_video_url` TEXT DEFAULT NULL;
