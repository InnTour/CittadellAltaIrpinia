-- ============================================================
-- MetaBorghi — Migration V4: Rating/Reviews + ENUM Companies fix
-- Da eseguire via phpMyAdmin dopo le migrazioni precedenti (v1→v3)
--
-- Risolve 4 problemi identificati nell'audit backend-frontend:
-- 1. Colonne `rating` e `reviews_count` mancanti in `restaurants`
-- 2. Colonne `rating` e `reviews_count` mancanti in `accommodations`
-- 3. ENUM `companies.type` incompleto (mancano 4 valori usati dall'admin)
-- 4. Colonne extra in `food_products` aggiunte dall'auto-migration
--
-- NOTA: I punti 1 e 2 sono già gestiti dall'auto-migration inline
-- in ristorazione.php e ospitalita.php al primo caricamento.
-- Questo file consolida le migrazioni sul DB in modo permanente.
-- ============================================================

SET NAMES utf8mb4;

-- ------------------------------------------------------------
-- 1. RISTORAZIONE — Rating e recensioni
-- ------------------------------------------------------------
ALTER TABLE `restaurants`
  ADD COLUMN IF NOT EXISTS `rating`        DECIMAL(3,2) DEFAULT 0.00,
  ADD COLUMN IF NOT EXISTS `reviews_count` INT          DEFAULT 0;

-- ------------------------------------------------------------
-- 2. OSPITALITÀ — Rating e recensioni
-- ------------------------------------------------------------
ALTER TABLE `accommodations`
  ADD COLUMN IF NOT EXISTS `rating`        DECIMAL(3,2) DEFAULT 0.00,
  ADD COLUMN IF NOT EXISTS `reviews_count` INT          DEFAULT 0;

-- ------------------------------------------------------------
-- 3. COMPANIES — Espansione ENUM type
-- Schema base aveva solo: PRODUTTORE_FOOD, MISTO, AGRITURISMO
-- Admin panel usa 7 tipi. ALTER TABLE estende l'ENUM.
-- Operazione sicura: non altera i dati esistenti.
-- ------------------------------------------------------------
ALTER TABLE `companies`
  MODIFY COLUMN `type` ENUM(
    'PRODUTTORE_FOOD',
    'ARTIGIANO',
    'MISTO',
    'AGRITURISMO',
    'RISTORANTE',
    'GUIDA_TURISTICA',
    'COOPERATIVA'
  ) DEFAULT 'MISTO';

-- ------------------------------------------------------------
-- 4. FOOD PRODUCTS — Colonne extra (aggiunte dall'auto-migration)
-- Consolidate qui per garantire consistenza su nuovi ambienti
-- ------------------------------------------------------------
ALTER TABLE `food_products`
  ADD COLUMN IF NOT EXISTS `rating`             DECIMAL(3,2) DEFAULT 0.00,
  ADD COLUMN IF NOT EXISTS `reviews_count`      INT          DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `origin_region`      TEXT         DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `tags`               TEXT         DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `traceability_chain` TEXT         DEFAULT NULL;

-- ------------------------------------------------------------
-- 5. ARTIGIANATO — Rename values → values_json (se necessario)
-- Eseguire SOLO se la tabella è stata creata con il vecchio
-- nome `values` (riservato in MySQL, causa warning).
-- Decommentare se SELECT `values_json` da craft_customization_options
-- restituisce "Unknown column" sul vostro ambiente.
-- ------------------------------------------------------------
-- ALTER TABLE `craft_customization_options`
--   CHANGE COLUMN `values` `values_json` TEXT DEFAULT NULL;

-- ============================================================
-- Query di verifica post-migration
-- ============================================================
-- Eseguire dopo per confermare lo stato:
-- SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE
-- FROM INFORMATION_SCHEMA.COLUMNS
-- WHERE TABLE_SCHEMA = DATABASE()
--   AND TABLE_NAME IN ('restaurants','accommodations','companies','food_products')
--   AND COLUMN_NAME IN ('rating','reviews_count','type','origin_region')
-- ORDER BY TABLE_NAME, COLUMN_NAME;
