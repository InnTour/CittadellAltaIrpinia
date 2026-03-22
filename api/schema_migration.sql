-- ============================================================
-- MetaBorghi — Migration: Prodotti Food, Ospitalità, Ristorazione
-- Da eseguire via phpMyAdmin dopo lo schema base
-- ============================================================

SET NAMES utf8mb4;

-- ------------------------------------------------------------
-- PRODOTTI FOOD
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `food_products` (
  `id`                   VARCHAR(100)    NOT NULL,
  `slug`                 VARCHAR(100)    NOT NULL,
  `name`                 VARCHAR(300)    DEFAULT NULL,
  `producer_id`          VARCHAR(100)    DEFAULT NULL,
  `borough_id`           VARCHAR(100)    DEFAULT NULL,
  `category`             VARCHAR(100)    DEFAULT NULL,
  `description_short`    TEXT            DEFAULT NULL,
  `description_long`     TEXT            DEFAULT NULL,
  `tagline`              TEXT            DEFAULT NULL,
  `pairing_suggestions`  TEXT            DEFAULT NULL,
  `price`                DECIMAL(10,2)   DEFAULT NULL,
  `unit`                 VARCHAR(100)    DEFAULT NULL,
  `weight_grams`         INT             DEFAULT NULL,
  `shelf_life_days`      INT             DEFAULT NULL,
  `storage_instructions` TEXT            DEFAULT NULL,
  `origin_protected`     VARCHAR(200)    DEFAULT NULL,
  `allergens`            TEXT            DEFAULT NULL,
  `ingredients`          TEXT            DEFAULT NULL,
  `stock_qty`            INT             DEFAULT 0,
  `min_order_qty`        INT             DEFAULT 1,
  `is_shippable`         TINYINT(1)      DEFAULT 0,
  `shipping_notes`       TEXT            DEFAULT NULL,
  `is_active`            TINYINT(1)      DEFAULT 1,
  `is_featured`          TINYINT(1)      DEFAULT 0,
  `created_at`           TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  `updated_at`           TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- OSPITALITÀ
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `accommodations` (
  `id`                    VARCHAR(100)    NOT NULL,
  `slug`                  VARCHAR(100)    NOT NULL,
  `name`                  VARCHAR(300)    DEFAULT NULL,
  `type`                  ENUM('HOTEL','AGRITURISMO','MASSERIA','BED_AND_BREAKFAST','HOSTEL','APPARTAMENTO') DEFAULT 'AGRITURISMO',
  `provider_id`           VARCHAR(100)    DEFAULT NULL,
  `borough_id`            VARCHAR(100)    DEFAULT NULL,
  `address_full`          TEXT            DEFAULT NULL,
  `lat`                   DECIMAL(10,7)   DEFAULT NULL,
  `lng`                   DECIMAL(10,7)   DEFAULT NULL,
  `distance_center_km`    DECIMAL(5,2)    DEFAULT NULL,
  `description_short`     TEXT            DEFAULT NULL,
  `description_long`      TEXT            DEFAULT NULL,
  `tagline`               TEXT            DEFAULT NULL,
  `rooms_count`           INT             DEFAULT NULL,
  `max_guests`            INT             DEFAULT NULL,
  `price_per_night_from`  DECIMAL(10,2)   DEFAULT NULL,
  `stars_or_category`     VARCHAR(100)    DEFAULT NULL,
  `check_in_time`         VARCHAR(10)     DEFAULT NULL,
  `check_out_time`        VARCHAR(10)     DEFAULT NULL,
  `min_stay_nights`       INT             DEFAULT 1,
  `amenities`             TEXT            DEFAULT NULL,
  `accessibility`         TEXT            DEFAULT NULL,
  `languages_spoken`      TEXT            DEFAULT NULL,
  `cancellation_policy`   TEXT            DEFAULT NULL,
  `booking_email`         VARCHAR(200)    DEFAULT NULL,
  `booking_phone`         VARCHAR(50)     DEFAULT NULL,
  `booking_url`           TEXT            DEFAULT NULL,
  `main_video_url`        TEXT            DEFAULT NULL,
  `virtual_tour_url`      TEXT            DEFAULT NULL,
  `contact_email`         VARCHAR(200)    DEFAULT NULL,
  `contact_phone`         VARCHAR(50)     DEFAULT NULL,
  `website_url`           TEXT            DEFAULT NULL,
  `social_instagram`      TEXT            DEFAULT NULL,
  `social_facebook`       TEXT            DEFAULT NULL,
  `social_linkedin`       TEXT            DEFAULT NULL,
  `certifications`        TEXT            DEFAULT NULL,
  `founder_name`          VARCHAR(200)    DEFAULT NULL,
  `founder_quote`         TEXT            DEFAULT NULL,
  `rating`                DECIMAL(3,2)    DEFAULT 0,
  `reviews_count`         INT             DEFAULT 0,
  `tier`                  VARCHAR(20)     DEFAULT 'BASE',
  `is_verified`           TINYINT(1)      DEFAULT 0,
  `b2b_open_for_contact`  TINYINT(1)      DEFAULT 0,
  `b2b_interests`         TEXT            DEFAULT NULL,
  `cover_image`           VARCHAR(500)    DEFAULT NULL,
  `is_active`             TINYINT(1)      DEFAULT 1,
  `is_featured`           TINYINT(1)      DEFAULT 0,
  `created_at`            TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  `updated_at`            TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- RISTORAZIONE
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `restaurants` (
  `id`                   VARCHAR(100)    NOT NULL,
  `slug`                 VARCHAR(100)    NOT NULL,
  `name`                 VARCHAR(300)    DEFAULT NULL,
  `type`                 ENUM('RISTORANTE','TRATTORIA','PIZZERIA','AGRITURISMO','ENOTECA','BAR','OSTERIA') DEFAULT 'RISTORANTE',
  `borough_id`           VARCHAR(100)    DEFAULT NULL,
  `address_full`         TEXT            DEFAULT NULL,
  `lat`                  DECIMAL(10,7)   DEFAULT NULL,
  `lng`                  DECIMAL(10,7)   DEFAULT NULL,
  `description_short`    TEXT            DEFAULT NULL,
  `description_long`     TEXT            DEFAULT NULL,
  `tagline`              TEXT            DEFAULT NULL,
  `cuisine_type`         VARCHAR(200)    DEFAULT NULL,
  `price_range`          ENUM('BUDGET','MEDIO','ALTO','GOURMET') DEFAULT 'MEDIO',
  `seats_indoor`         INT             DEFAULT NULL,
  `seats_outdoor`        INT             DEFAULT NULL,
  `opening_hours`        VARCHAR(200)    DEFAULT NULL,
  `closing_day`          VARCHAR(100)    DEFAULT NULL,
  `specialties`          TEXT            DEFAULT NULL,
  `menu_highlights`      TEXT            DEFAULT NULL,
  `contact_email`        VARCHAR(200)    DEFAULT NULL,
  `contact_phone`        VARCHAR(50)     DEFAULT NULL,
  `website_url`          TEXT            DEFAULT NULL,
  `social_instagram`     TEXT            DEFAULT NULL,
  `social_facebook`      TEXT            DEFAULT NULL,
  `social_linkedin`      TEXT            DEFAULT NULL,
  `booking_url`          TEXT            DEFAULT NULL,
  `accepts_groups`       TINYINT(1)      DEFAULT 0,
  `max_group_size`       INT             DEFAULT NULL,
  `certifications`       TEXT            DEFAULT NULL,
  `founder_name`         VARCHAR(200)    DEFAULT NULL,
  `founder_quote`        TEXT            DEFAULT NULL,
  `rating`               DECIMAL(3,2)    DEFAULT 0,
  `reviews_count`        INT             DEFAULT 0,
  `tier`                 VARCHAR(20)     DEFAULT 'BASE',
  `is_verified`          TINYINT(1)      DEFAULT 0,
  `b2b_open_for_contact` TINYINT(1)      DEFAULT 0,
  `b2b_interests`        TEXT            DEFAULT NULL,
  `main_video_url`       TEXT            DEFAULT NULL,
  `virtual_tour_url`     TEXT            DEFAULT NULL,
  `cover_image`          VARCHAR(500)    DEFAULT NULL,
  `is_active`            TINYINT(1)      DEFAULT 1,
  `is_featured`          TINYINT(1)      DEFAULT 0,
  `created_at`           TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  `updated_at`           TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add virtual_tour_url and main_video_url to restaurants if missing (migration)
ALTER TABLE `restaurants` ADD COLUMN IF NOT EXISTS `main_video_url` TEXT DEFAULT NULL AFTER `booking_url`;
ALTER TABLE `restaurants` ADD COLUMN IF NOT EXISTS `virtual_tour_url` TEXT DEFAULT NULL AFTER `main_video_url`;

-- Add virtual_tour_url and main_video_url to experiences if missing (migration)
ALTER TABLE `experiences` ADD COLUMN IF NOT EXISTS `main_video_url` TEXT DEFAULT NULL AFTER `cover_image`;
ALTER TABLE `experiences` ADD COLUMN IF NOT EXISTS `virtual_tour_url` TEXT DEFAULT NULL AFTER `main_video_url`;
