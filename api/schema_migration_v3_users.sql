-- ============================================================
-- MetaBorghi — Migration v3: Utenti, RBAC, Wishlist, Prenotazioni
-- Da eseguire via phpMyAdmin dopo le migrazioni precedenti
-- ============================================================

SET NAMES utf8mb4;

-- ------------------------------------------------------------
-- UTENTI — 4 ruoli: guest (non in DB), registered, operator, admin
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`               VARCHAR(100)    NOT NULL,
  `email`            VARCHAR(200)    NOT NULL,
  `password_hash`    VARCHAR(255)    NOT NULL,
  `name`             VARCHAR(200)    NOT NULL,
  `role`             ENUM('guest','registered','operator','admin') NOT NULL DEFAULT 'registered',
  `phone`            VARCHAR(50)     DEFAULT NULL,
  `avatar_url`       TEXT            DEFAULT NULL,
  `bio`              TEXT            DEFAULT NULL,
  `preferred_locale` VARCHAR(10)     DEFAULT 'it',
  `is_active`        TINYINT(1)      NOT NULL DEFAULT 1,
  `email_verified`   TINYINT(1)      NOT NULL DEFAULT 0,
  `last_login_at`    TIMESTAMP       NULL DEFAULT NULL,
  `created_at`       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- ASSOCIAZIONI OPERATORE → BORGO
-- Un operatore può gestire più borghi con permessi granulari
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_borough_assignments` (
  `id`                     INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`                VARCHAR(100) NOT NULL,
  `borough_id`             VARCHAR(100) NOT NULL,
  `can_edit_content`       TINYINT(1)   NOT NULL DEFAULT 1,
  `can_manage_experiences` TINYINT(1)   NOT NULL DEFAULT 1,
  `can_manage_companies`   TINYINT(1)   NOT NULL DEFAULT 0,
  `can_view_analytics`     TINYINT(1)   NOT NULL DEFAULT 1,
  `assigned_at`            TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `user_id` (`user_id`),
  KEY `borough_id` (`borough_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- ASSOCIAZIONI OPERATORE → AZIENDA
-- Un operatore aziendale gestisce la propria azienda
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_company_assignments` (
  `id`                  INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`             VARCHAR(100) NOT NULL,
  `company_id`          VARCHAR(100) NOT NULL,
  `can_edit_profile`    TINYINT(1)   NOT NULL DEFAULT 1,
  `can_manage_products` TINYINT(1)   NOT NULL DEFAULT 1,
  `can_manage_orders`   TINYINT(1)   NOT NULL DEFAULT 1,
  `can_view_analytics`  TINYINT(1)   NOT NULL DEFAULT 1,
  `assigned_at`         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `user_id` (`user_id`),
  KEY `company_id` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- WISHLIST UTENTE
-- Salva preferiti di qualsiasi tipo (borgo, esperienza, prodotto...)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_wishlist` (
  `id`        INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`   VARCHAR(100) NOT NULL,
  `item_type` ENUM('borough','experience','craft','food_product','accommodation','restaurant') NOT NULL,
  `item_id`   VARCHAR(100) NOT NULL,
  `added_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `user_id` (`user_id`),
  KEY `item_type_id` (`item_type`, `item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- PRENOTAZIONI
-- Prenotazioni di esperienze e alloggi
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `bookings` (
  `id`                        VARCHAR(100) NOT NULL,
  `user_id`                   VARCHAR(100) NOT NULL,
  `experience_id`             VARCHAR(100) DEFAULT NULL,
  `accommodation_id`          VARCHAR(100) DEFAULT NULL,
  `status`                    ENUM('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending',
  `booking_date`              TIMESTAMP    NOT NULL,
  `guests_count`              INT          NOT NULL DEFAULT 1,
  `total_price_cents`         INT          DEFAULT NULL,
  `notes`                     TEXT         DEFAULT NULL,
  `stripe_payment_intent_id`  VARCHAR(255) DEFAULT NULL,
  `created_at`                TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`                TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `experience_id` (`experience_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
