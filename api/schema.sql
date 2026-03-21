-- ============================================================
-- MetaBorghi — Schema MySQL
-- Da eseguire via phpMyAdmin su Hostinger
-- ============================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- ------------------------------------------------------------
-- BORGHI
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `boroughs` (
  `id`               VARCHAR(100)    NOT NULL,
  `slug`             VARCHAR(100)    NOT NULL,
  `name`             VARCHAR(200)    NOT NULL,
  `province`         VARCHAR(100)    DEFAULT NULL,
  `region`           VARCHAR(100)    DEFAULT NULL,
  `population`       INT             DEFAULT NULL,
  `altitude_meters`  INT             DEFAULT NULL,
  `area_km2`         DECIMAL(8,2)    DEFAULT NULL,
  `lat`              DECIMAL(10,7)   DEFAULT NULL,
  `lng`              DECIMAL(10,7)   DEFAULT NULL,
  `main_video_url`   TEXT            DEFAULT NULL,
  `virtual_tour_url` TEXT            DEFAULT NULL,
  `description`      TEXT            DEFAULT NULL,
  `companies_count`  INT             DEFAULT 0,
  `hero_image_index` INT             DEFAULT 0,
  `hero_image_alt`   VARCHAR(300)    DEFAULT NULL,
  `created_at`       TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `borough_highlights` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `borough_id`  VARCHAR(100) NOT NULL,
  `value`       TEXT NOT NULL,
  `sort_order`  INT DEFAULT 0,
  INDEX (`borough_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `borough_notable_products` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `borough_id`  VARCHAR(100) NOT NULL,
  `value`       TEXT NOT NULL,
  `sort_order`  INT DEFAULT 0,
  INDEX (`borough_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `borough_notable_experiences` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `borough_id`  VARCHAR(100) NOT NULL,
  `value`       TEXT NOT NULL,
  `sort_order`  INT DEFAULT 0,
  INDEX (`borough_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `borough_notable_restaurants` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `borough_id`  VARCHAR(100) NOT NULL,
  `value`       TEXT NOT NULL,
  `sort_order`  INT DEFAULT 0,
  INDEX (`borough_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `borough_gallery_images` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `borough_id`  VARCHAR(100) NOT NULL,
  `src_index`   INT DEFAULT 0,
  `alt_text`    VARCHAR(300) DEFAULT NULL,
  `sort_order`  INT DEFAULT 0,
  INDEX (`borough_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- AZIENDE
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `companies` (
  `id`                    VARCHAR(100) NOT NULL,
  `slug`                  VARCHAR(100) NOT NULL,
  `name`                  VARCHAR(200) DEFAULT NULL,
  `legal_name`            VARCHAR(200) DEFAULT NULL,
  `vat_number`            VARCHAR(20)  DEFAULT NULL,
  `type`                  ENUM('PRODUTTORE_FOOD','MISTO','AGRITURISMO') DEFAULT 'MISTO',
  `tagline`               TEXT         DEFAULT NULL,
  `description_short`     TEXT         DEFAULT NULL,
  `description_long`      TEXT         DEFAULT NULL,
  `founding_year`         INT          DEFAULT NULL,
  `employees_count`       INT          DEFAULT NULL,
  `borough_id`            VARCHAR(100) DEFAULT NULL,
  `address_full`          TEXT         DEFAULT NULL,
  `lat`                   DECIMAL(10,7) DEFAULT NULL,
  `lng`                   DECIMAL(10,7) DEFAULT NULL,
  `contact_email`         VARCHAR(200) DEFAULT NULL,
  `contact_phone`         VARCHAR(50)  DEFAULT NULL,
  `website_url`           TEXT         DEFAULT NULL,
  `social_instagram`      TEXT         DEFAULT NULL,
  `social_facebook`       TEXT         DEFAULT NULL,
  `social_linkedin`       TEXT         DEFAULT NULL,
  `tier`                  ENUM('BASE','PREMIUM','PLATINUM') DEFAULT 'BASE',
  `is_verified`           TINYINT(1)   DEFAULT 0,
  `is_active`             TINYINT(1)   DEFAULT 1,
  `b2b_open_for_contact`  TINYINT(1)   DEFAULT 0,
  `founder_name`          VARCHAR(200) DEFAULT NULL,
  `founder_quote`         TEXT         DEFAULT NULL,
  `main_video_url`        TEXT         DEFAULT NULL,
  `virtual_tour_url`      TEXT         DEFAULT NULL,
  `hero_image_index`      INT          DEFAULT 0,
  `hero_image_alt`        VARCHAR(300) DEFAULT NULL,
  `created_at`            TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  `updated_at`            TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `company_certifications` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `company_id`  VARCHAR(100) NOT NULL,
  `value`       VARCHAR(100) NOT NULL,
  `sort_order`  INT DEFAULT 0,
  INDEX (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `company_b2b_interests` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `company_id`  VARCHAR(100) NOT NULL,
  `value`       VARCHAR(100) NOT NULL,
  `sort_order`  INT DEFAULT 0,
  INDEX (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `company_awards` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `company_id`  VARCHAR(100) NOT NULL,
  `year`        INT          DEFAULT NULL,
  `title`       TEXT         DEFAULT NULL,
  `entity`      TEXT         DEFAULT NULL,
  INDEX (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- ESPERIENZE
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `experiences` (
  `id`                  VARCHAR(100) NOT NULL,
  `slug`                VARCHAR(100) NOT NULL,
  `title`               VARCHAR(300) DEFAULT NULL,
  `tagline`             TEXT         DEFAULT NULL,
  `description_short`   TEXT         DEFAULT NULL,
  `description_long`    TEXT         DEFAULT NULL,
  `category`            ENUM('GASTRONOMIA','CULTURA','NATURA','ARTIGIANATO','BENESSERE','AVVENTURA') DEFAULT 'CULTURA',
  `provider_id`         VARCHAR(100) DEFAULT NULL,
  `borough_id`          VARCHAR(100) DEFAULT NULL,
  `lat`                 DECIMAL(10,7) DEFAULT NULL,
  `lng`                 DECIMAL(10,7) DEFAULT NULL,
  `duration_minutes`    INT          DEFAULT NULL,
  `max_participants`    INT          DEFAULT NULL,
  `min_participants`    INT          DEFAULT NULL,
  `price_per_person`    DECIMAL(10,2) DEFAULT NULL,
  `cancellation_policy` TEXT         DEFAULT NULL,
  `difficulty_level`    ENUM('FACILE','MEDIO','DIFFICILE') DEFAULT 'FACILE',
  `accessibility_info`  TEXT         DEFAULT NULL,
  `rating`              DECIMAL(3,2) DEFAULT 0.00,
  `reviews_count`       INT          DEFAULT 0,
  `is_active`           TINYINT(1)   DEFAULT 1,
  `created_at`          TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `experience_languages` (
  `id`             INT AUTO_INCREMENT PRIMARY KEY,
  `experience_id`  VARCHAR(100) NOT NULL,
  `lang`           VARCHAR(50)  NOT NULL,
  `sort_order`     INT DEFAULT 0,
  INDEX (`experience_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `experience_includes` (
  `id`             INT AUTO_INCREMENT PRIMARY KEY,
  `experience_id`  VARCHAR(100) NOT NULL,
  `value`          TEXT NOT NULL,
  `sort_order`     INT DEFAULT 0,
  INDEX (`experience_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `experience_excludes` (
  `id`             INT AUTO_INCREMENT PRIMARY KEY,
  `experience_id`  VARCHAR(100) NOT NULL,
  `value`          TEXT NOT NULL,
  `sort_order`     INT DEFAULT 0,
  INDEX (`experience_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `experience_bring` (
  `id`             INT AUTO_INCREMENT PRIMARY KEY,
  `experience_id`  VARCHAR(100) NOT NULL,
  `value`          TEXT NOT NULL,
  `sort_order`     INT DEFAULT 0,
  INDEX (`experience_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `experience_seasonal_tags` (
  `id`             INT AUTO_INCREMENT PRIMARY KEY,
  `experience_id`  VARCHAR(100) NOT NULL,
  `value`          VARCHAR(100) NOT NULL,
  `sort_order`     INT DEFAULT 0,
  INDEX (`experience_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `experience_timeline` (
  `id`             INT AUTO_INCREMENT PRIMARY KEY,
  `experience_id`  VARCHAR(100) NOT NULL,
  `time_slot`      VARCHAR(10)  DEFAULT NULL,
  `title`          VARCHAR(200) DEFAULT NULL,
  `description`    TEXT         DEFAULT NULL,
  `icon`           VARCHAR(50)  DEFAULT NULL,
  `sort_order`     INT DEFAULT 0,
  INDEX (`experience_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- PRODOTTI ARTIGIANALI
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `craft_products` (
  `id`                        VARCHAR(100) NOT NULL,
  `slug`                      VARCHAR(100) NOT NULL,
  `name`                      VARCHAR(300) DEFAULT NULL,
  `description_short`         TEXT         DEFAULT NULL,
  `description_long`          TEXT         DEFAULT NULL,
  `price`                     DECIMAL(10,2) DEFAULT NULL,
  `is_custom_order_available` TINYINT(1)   DEFAULT 0,
  `lead_time_days`            INT          DEFAULT NULL,
  `technique_description`     TEXT         DEFAULT NULL,
  `dimensions`                VARCHAR(100) DEFAULT NULL,
  `weight_grams`              INT          DEFAULT NULL,
  `artisan_id`                VARCHAR(100) DEFAULT NULL,
  `borough_id`                VARCHAR(100) DEFAULT NULL,
  `is_unique_piece`           TINYINT(1)   DEFAULT 0,
  `production_series_qty`     INT          DEFAULT NULL,
  `rating`                    DECIMAL(3,2) DEFAULT 0.00,
  `reviews_count`             INT          DEFAULT 0,
  `stock_qty`                 INT          DEFAULT 0,
  `is_active`                 TINYINT(1)   DEFAULT 1,
  `created_at`                TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  `updated_at`                TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `craft_material_types` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `craft_id`    VARCHAR(100) NOT NULL,
  `value`       VARCHAR(100) NOT NULL,
  `sort_order`  INT DEFAULT 0,
  INDEX (`craft_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `craft_customization_options` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `craft_id`        VARCHAR(100)    NOT NULL,
  `name`            VARCHAR(200)    DEFAULT NULL,
  `values_json`     TEXT            DEFAULT NULL,
  `price_modifier`  DECIMAL(10,2)   DEFAULT 0,
  INDEX (`craft_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `craft_process_steps` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `craft_id`    VARCHAR(100) NOT NULL,
  `title`       VARCHAR(200) DEFAULT NULL,
  `description` TEXT         DEFAULT NULL,
  `sort_order`  INT DEFAULT 0,
  INDEX (`craft_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;

-- ============================================================
-- MIGRATION — Aggiunta sort_order a tabelle che ne erano prive
-- Eseguire SOLO se lo schema era già stato importato in precedenza
-- (phpMyAdmin > SQL > incolla solo questa sezione)
-- ============================================================
ALTER TABLE `company_certifications`
  ADD COLUMN IF NOT EXISTS `sort_order` INT DEFAULT 0;

ALTER TABLE `company_b2b_interests`
  ADD COLUMN IF NOT EXISTS `sort_order` INT DEFAULT 0;

ALTER TABLE `craft_material_types`
  ADD COLUMN IF NOT EXISTS `sort_order` INT DEFAULT 0;

ALTER TABLE `experience_seasonal_tags`
  ADD COLUMN IF NOT EXISTS `sort_order` INT DEFAULT 0;

ALTER TABLE `experience_languages`
  ADD COLUMN IF NOT EXISTS `sort_order` INT DEFAULT 0;
