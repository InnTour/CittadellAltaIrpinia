-- ============================================================
-- MetaBorghi — Migration v2: Utenti con RBAC
-- Da eseguire via phpMyAdmin dopo schema_migration.sql
-- La tabella si chiama admin_users per non confliggere con
-- eventuali sistemi di autenticazione futuri.
-- ============================================================

SET NAMES utf8mb4;

-- ------------------------------------------------------------
-- UTENTI PIATTAFORMA
-- 4 ruoli: visitatore, registrato, operatore, admin
-- Gli operatori hanno borough_id e/o company_id assegnati.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id`            VARCHAR(40)   NOT NULL COMMENT 'Slug leggibile, es: mario-rossi',
  `name`          VARCHAR(200)  NOT NULL,
  `email`         VARCHAR(200)  NOT NULL,
  `password_hash` VARCHAR(255)  NOT NULL DEFAULT '' COMMENT 'bcrypt via PHP password_hash()',
  `role`          ENUM('visitatore','registrato','operatore','admin') NOT NULL DEFAULT 'registrato',
  `borough_id`    VARCHAR(100)  DEFAULT NULL COMMENT 'Borgo assegnato (solo operatore)',
  `company_id`    VARCHAR(100)  DEFAULT NULL COMMENT 'Azienda assegnata (solo operatore)',
  `phone`         VARCHAR(50)   DEFAULT NULL,
  `bio`           TEXT          DEFAULT NULL,
  `is_active`     TINYINT(1)    NOT NULL DEFAULT 1,
  `last_login_at` TIMESTAMP     NULL DEFAULT NULL,
  `created_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
