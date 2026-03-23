-- ============================================================
-- MetaBorghi — Migration V2: Cover Images + Analytics
-- ============================================================

SET NAMES utf8mb4;

-- Cover image per tutte le entità principali
ALTER TABLE `boroughs`       ADD COLUMN IF NOT EXISTS `cover_image` VARCHAR(500) DEFAULT NULL;
ALTER TABLE `companies`      ADD COLUMN IF NOT EXISTS `cover_image` VARCHAR(500) DEFAULT NULL;
ALTER TABLE `experiences`    ADD COLUMN IF NOT EXISTS `cover_image` VARCHAR(500) DEFAULT NULL;
ALTER TABLE `craft_products` ADD COLUMN IF NOT EXISTS `cover_image` VARCHAR(500) DEFAULT NULL;
ALTER TABLE `food_products`  ADD COLUMN IF NOT EXISTS `cover_image` VARCHAR(500) DEFAULT NULL;
ALTER TABLE `accommodations` ADD COLUMN IF NOT EXISTS `cover_image` VARCHAR(500) DEFAULT NULL;
ALTER TABLE `restaurants`    ADD COLUMN IF NOT EXISTS `cover_image` VARCHAR(500) DEFAULT NULL;

-- ------------------------------------------------------------
-- ANALYTICS — Tracciamento visualizzazioni e KPI
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `page_views` (
  `id`            BIGINT AUTO_INCREMENT PRIMARY KEY,
  `entity_type`   VARCHAR(50)   NOT NULL COMMENT 'borough, company, experience, craft, food, accommodation, restaurant',
  `entity_id`     VARCHAR(100)  NOT NULL,
  `page_url`      TEXT          DEFAULT NULL,
  `referrer`      TEXT          DEFAULT NULL,
  `user_agent`    TEXT          DEFAULT NULL,
  `ip_hash`       VARCHAR(64)   DEFAULT NULL COMMENT 'SHA-256 hash for unique visitors',
  `session_id`    VARCHAR(100)  DEFAULT NULL,
  `viewed_at`     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_entity` (`entity_type`, `entity_id`),
  INDEX `idx_viewed_at` (`viewed_at`),
  INDEX `idx_session` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `daily_stats` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `stat_date`     DATE          NOT NULL,
  `entity_type`   VARCHAR(50)   NOT NULL,
  `entity_id`     VARCHAR(100)  NOT NULL,
  `views_count`   INT           DEFAULT 0,
  `unique_views`  INT           DEFAULT 0,
  UNIQUE KEY `uq_daily` (`stat_date`, `entity_type`, `entity_id`),
  INDEX `idx_date` (`stat_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- B2B FIELDS per RISTORAZIONE e OSPITALITA
-- ------------------------------------------------------------
ALTER TABLE `restaurants` ADD COLUMN IF NOT EXISTS `certifications` TEXT DEFAULT NULL;
ALTER TABLE `restaurants` ADD COLUMN IF NOT EXISTS `founder_name` VARCHAR(200) DEFAULT NULL;
ALTER TABLE `restaurants` ADD COLUMN IF NOT EXISTS `founder_quote` TEXT DEFAULT NULL;
ALTER TABLE `restaurants` ADD COLUMN IF NOT EXISTS `tier` ENUM('BASE','PREMIUM','PLATINUM') DEFAULT 'BASE';
ALTER TABLE `restaurants` ADD COLUMN IF NOT EXISTS `is_verified` TINYINT(1) DEFAULT 0;
ALTER TABLE `restaurants` ADD COLUMN IF NOT EXISTS `social_linkedin` TEXT DEFAULT NULL;

ALTER TABLE `accommodations` ADD COLUMN IF NOT EXISTS `certifications` TEXT DEFAULT NULL;
ALTER TABLE `accommodations` ADD COLUMN IF NOT EXISTS `founder_name` VARCHAR(200) DEFAULT NULL;
ALTER TABLE `accommodations` ADD COLUMN IF NOT EXISTS `founder_quote` TEXT DEFAULT NULL;
ALTER TABLE `accommodations` ADD COLUMN IF NOT EXISTS `tier` ENUM('BASE','PREMIUM','PLATINUM') DEFAULT 'BASE';
ALTER TABLE `accommodations` ADD COLUMN IF NOT EXISTS `is_verified` TINYINT(1) DEFAULT 0;
ALTER TABLE `accommodations` ADD COLUMN IF NOT EXISTS `b2b_open_for_contact` TINYINT(1) DEFAULT 0;
ALTER TABLE `accommodations` ADD COLUMN IF NOT EXISTS `b2b_interests` TEXT DEFAULT NULL;
ALTER TABLE `accommodations` ADD COLUMN IF NOT EXISTS `social_instagram` TEXT DEFAULT NULL;
ALTER TABLE `accommodations` ADD COLUMN IF NOT EXISTS `social_facebook` TEXT DEFAULT NULL;
ALTER TABLE `accommodations` ADD COLUMN IF NOT EXISTS `social_linkedin` TEXT DEFAULT NULL;
ALTER TABLE `accommodations` ADD COLUMN IF NOT EXISTS `contact_email` VARCHAR(200) DEFAULT NULL;
ALTER TABLE `accommodations` ADD COLUMN IF NOT EXISTS `contact_phone` VARCHAR(50) DEFAULT NULL;
ALTER TABLE `accommodations` ADD COLUMN IF NOT EXISTS `website_url` TEXT DEFAULT NULL;

-- ------------------------------------------------------------
-- COMUNI B2G — Programma per Pubbliche Amministrazioni
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `b2g_municipalities` (
  `id`                   VARCHAR(100)   NOT NULL,
  `borough_id`           VARCHAR(100)   DEFAULT NULL COMMENT 'collegamento a boroughs',
  `municipality_name`    VARCHAR(300)   NOT NULL,
  `province`             VARCHAR(100)   DEFAULT NULL,
  `region`               VARCHAR(100)   DEFAULT 'Campania',
  `mayor_name`           VARCHAR(200)   DEFAULT NULL,
  `mayor_email`          VARCHAR(200)   DEFAULT NULL,
  `contact_person`       VARCHAR(200)   DEFAULT NULL,
  `contact_email`        VARCHAR(200)   DEFAULT NULL,
  `contact_phone`        VARCHAR(50)    DEFAULT NULL,
  `pec_email`            VARCHAR(200)   DEFAULT NULL,
  `website_url`          TEXT           DEFAULT NULL,
  `population`           INT            DEFAULT NULL,
  `tier`                 ENUM('BASE','STANDARD','PREMIUM') DEFAULT 'BASE',
  `subscription_status`  ENUM('LEAD','CONTATTATO','DEMO','ATTIVO','SOSPESO','SCADUTO') DEFAULT 'LEAD',
  `subscription_start`   DATE           DEFAULT NULL,
  `subscription_end`     DATE           DEFAULT NULL,
  `annual_fee`           DECIMAL(10,2)  DEFAULT NULL,
  `pnrr_funded`          TINYINT(1)     DEFAULT 0,
  `pnrr_measure`         VARCHAR(200)   DEFAULT NULL,
  `notes`                TEXT           DEFAULT NULL,
  `services_enabled`     TEXT           DEFAULT NULL COMMENT 'JSON lista servizi attivi',
  `cover_image`          VARCHAR(500)   DEFAULT NULL,
  `created_at`           TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
  `updated_at`           TIMESTAMP      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
