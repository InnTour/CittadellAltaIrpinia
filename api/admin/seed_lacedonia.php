<?php
/**
 * Seed Lacedonia — inserisce tutti i dati di esempio nel database.
 * Accesso: /api/admin/seed_lacedonia.php (protetto da sessione admin)
 *
 * Popola:
 *   - 1 Borgo (Lacedonia)
 *   - 1 Azienda (Caciocavalleria De D.)
 *   - 1 Esperienza (Tour Caseificio Podolico)
 *   - 1 Artigianato (Cesto in Vimini)
 *   - 1 Prodotto Food (Caciocavallo Podolico 18m)
 *   - 1 Ospitalità (Masseria Santa Lucia)
 *   - 1 Ristorazione (Trattoria del Borgo)
 */
require_once __DIR__ . '/../config/db.php';
requireAdminSession();
$db = getDB();

$results = [];
$errors  = [];

// ─────────────────────────────────────────────────────────────
// 0. CREA TABELLE SE NON ESISTONO (schema_migration inline)
// ─────────────────────────────────────────────────────────────
try {
    $db->exec("
CREATE TABLE IF NOT EXISTS `food_products` (
  `id`                   VARCHAR(100)  NOT NULL,
  `slug`                 VARCHAR(100)  NOT NULL,
  `name`                 VARCHAR(300)  DEFAULT NULL,
  `producer_id`          VARCHAR(100)  DEFAULT NULL,
  `borough_id`           VARCHAR(100)  DEFAULT NULL,
  `category`             VARCHAR(100)  DEFAULT NULL,
  `description_short`    TEXT          DEFAULT NULL,
  `description_long`     TEXT          DEFAULT NULL,
  `tagline`              TEXT          DEFAULT NULL,
  `pairing_suggestions`  TEXT          DEFAULT NULL,
  `price`                DECIMAL(10,2) DEFAULT NULL,
  `unit`                 VARCHAR(100)  DEFAULT NULL,
  `weight_grams`         INT           DEFAULT NULL,
  `shelf_life_days`      INT           DEFAULT NULL,
  `storage_instructions` TEXT          DEFAULT NULL,
  `origin_protected`     VARCHAR(200)  DEFAULT NULL,
  `allergens`            TEXT          DEFAULT NULL,
  `ingredients`          TEXT          DEFAULT NULL,
  `stock_qty`            INT           DEFAULT 0,
  `min_order_qty`        INT           DEFAULT 1,
  `is_shippable`         TINYINT(1)    DEFAULT 0,
  `shipping_notes`       TEXT          DEFAULT NULL,
  `is_active`            TINYINT(1)    DEFAULT 1,
  `is_featured`          TINYINT(1)    DEFAULT 0,
  `created_at`           TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  `updated_at`           TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`), UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
    $db->exec("
CREATE TABLE IF NOT EXISTS `accommodations` (
  `id`                    VARCHAR(100)  NOT NULL,
  `slug`                  VARCHAR(100)  NOT NULL,
  `name`                  VARCHAR(300)  DEFAULT NULL,
  `type`                  ENUM('HOTEL','AGRITURISMO','MASSERIA','BED_AND_BREAKFAST','HOSTEL','APPARTAMENTO') DEFAULT 'AGRITURISMO',
  `provider_id`           VARCHAR(100)  DEFAULT NULL,
  `borough_id`            VARCHAR(100)  DEFAULT NULL,
  `address_full`          TEXT          DEFAULT NULL,
  `lat`                   DECIMAL(10,7) DEFAULT NULL,
  `lng`                   DECIMAL(10,7) DEFAULT NULL,
  `distance_center_km`    DECIMAL(5,2)  DEFAULT NULL,
  `description_short`     TEXT          DEFAULT NULL,
  `description_long`      TEXT          DEFAULT NULL,
  `tagline`               TEXT          DEFAULT NULL,
  `rooms_count`           INT           DEFAULT NULL,
  `max_guests`            INT           DEFAULT NULL,
  `price_per_night_from`  DECIMAL(10,2) DEFAULT NULL,
  `stars_or_category`     VARCHAR(100)  DEFAULT NULL,
  `check_in_time`         VARCHAR(10)   DEFAULT NULL,
  `check_out_time`        VARCHAR(10)   DEFAULT NULL,
  `min_stay_nights`       INT           DEFAULT 1,
  `amenities`             TEXT          DEFAULT NULL,
  `accessibility`         TEXT          DEFAULT NULL,
  `languages_spoken`      TEXT          DEFAULT NULL,
  `cancellation_policy`   TEXT          DEFAULT NULL,
  `booking_email`         VARCHAR(200)  DEFAULT NULL,
  `booking_phone`         VARCHAR(50)   DEFAULT NULL,
  `booking_url`           TEXT          DEFAULT NULL,
  `main_video_url`        TEXT          DEFAULT NULL,
  `virtual_tour_url`      TEXT          DEFAULT NULL,
  `is_active`             TINYINT(1)    DEFAULT 1,
  `is_featured`           TINYINT(1)    DEFAULT 0,
  `created_at`            TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  `updated_at`            TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`), UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
    $db->exec("
CREATE TABLE IF NOT EXISTS `restaurants` (
  `id`                   VARCHAR(100)  NOT NULL,
  `slug`                 VARCHAR(100)  NOT NULL,
  `name`                 VARCHAR(300)  DEFAULT NULL,
  `type`                 ENUM('RISTORANTE','TRATTORIA','PIZZERIA','AGRITURISMO','ENOTECA','BAR','OSTERIA') DEFAULT 'RISTORANTE',
  `borough_id`           VARCHAR(100)  DEFAULT NULL,
  `address_full`         TEXT          DEFAULT NULL,
  `lat`                  DECIMAL(10,7) DEFAULT NULL,
  `lng`                  DECIMAL(10,7) DEFAULT NULL,
  `description_short`    TEXT          DEFAULT NULL,
  `description_long`     TEXT          DEFAULT NULL,
  `tagline`              TEXT          DEFAULT NULL,
  `cuisine_type`         VARCHAR(200)  DEFAULT NULL,
  `price_range`          ENUM('BUDGET','MEDIO','ALTO','GOURMET') DEFAULT 'MEDIO',
  `seats_indoor`         INT           DEFAULT NULL,
  `seats_outdoor`        INT           DEFAULT NULL,
  `opening_hours`        VARCHAR(200)  DEFAULT NULL,
  `closing_day`          VARCHAR(100)  DEFAULT NULL,
  `specialties`          TEXT          DEFAULT NULL,
  `menu_highlights`      TEXT          DEFAULT NULL,
  `contact_email`        VARCHAR(200)  DEFAULT NULL,
  `contact_phone`        VARCHAR(50)   DEFAULT NULL,
  `website_url`          TEXT          DEFAULT NULL,
  `social_instagram`     TEXT          DEFAULT NULL,
  `social_facebook`      TEXT          DEFAULT NULL,
  `booking_url`          TEXT          DEFAULT NULL,
  `accepts_groups`       TINYINT(1)    DEFAULT 0,
  `max_group_size`       INT           DEFAULT NULL,
  `b2b_open_for_contact` TINYINT(1)    DEFAULT 0,
  `b2b_interests`        TEXT          DEFAULT NULL,
  `is_active`            TINYINT(1)    DEFAULT 1,
  `is_featured`          TINYINT(1)    DEFAULT 0,
  `created_at`           TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  `updated_at`           TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`), UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
    // Aggiungi cover_image a tutte le tabelle
    foreach (['boroughs','companies','experiences','craft_products','food_products','accommodations','restaurants'] as $_t) {
        try { $db->exec("ALTER TABLE `$_t` ADD COLUMN `cover_image` VARCHAR(500) DEFAULT NULL"); } catch (PDOException $e) { /* colonna già presente */ }
    }

    // B2B columns per restaurants
    foreach (['certifications TEXT DEFAULT NULL','founder_name VARCHAR(200) DEFAULT NULL','founder_quote TEXT DEFAULT NULL',
              "tier ENUM('BASE','PREMIUM','PLATINUM') DEFAULT 'BASE'",'is_verified TINYINT(1) DEFAULT 0','social_linkedin TEXT DEFAULT NULL'] as $_col) {
        try { $db->exec("ALTER TABLE `restaurants` ADD COLUMN $_col"); } catch (PDOException $e) {}
    }

    // B2B columns per accommodations
    foreach (['certifications TEXT DEFAULT NULL','founder_name VARCHAR(200) DEFAULT NULL','founder_quote TEXT DEFAULT NULL',
              "tier ENUM('BASE','PREMIUM','PLATINUM') DEFAULT 'BASE'",'is_verified TINYINT(1) DEFAULT 0',
              'b2b_open_for_contact TINYINT(1) DEFAULT 0','b2b_interests TEXT DEFAULT NULL',
              'social_instagram TEXT DEFAULT NULL','social_facebook TEXT DEFAULT NULL','social_linkedin TEXT DEFAULT NULL',
              'contact_email VARCHAR(200) DEFAULT NULL','contact_phone VARCHAR(50) DEFAULT NULL','website_url TEXT DEFAULT NULL'] as $_col) {
        try { $db->exec("ALTER TABLE `accommodations` ADD COLUMN $_col"); } catch (PDOException $e) {}
    }

    // Tabella B2G Comuni
    $db->exec("CREATE TABLE IF NOT EXISTS `b2g_municipalities` (
      `id` VARCHAR(100) NOT NULL, `borough_id` VARCHAR(100) DEFAULT NULL,
      `municipality_name` VARCHAR(300) NOT NULL, `province` VARCHAR(100) DEFAULT NULL,
      `region` VARCHAR(100) DEFAULT 'Campania', `mayor_name` VARCHAR(200) DEFAULT NULL,
      `mayor_email` VARCHAR(200) DEFAULT NULL, `contact_person` VARCHAR(200) DEFAULT NULL,
      `contact_email` VARCHAR(200) DEFAULT NULL, `contact_phone` VARCHAR(50) DEFAULT NULL,
      `pec_email` VARCHAR(200) DEFAULT NULL, `website_url` TEXT DEFAULT NULL,
      `population` INT DEFAULT NULL, `tier` ENUM('BASE','STANDARD','PREMIUM') DEFAULT 'BASE',
      `subscription_status` ENUM('LEAD','CONTATTATO','DEMO','ATTIVO','SOSPESO','SCADUTO') DEFAULT 'LEAD',
      `subscription_start` DATE DEFAULT NULL, `subscription_end` DATE DEFAULT NULL,
      `annual_fee` DECIMAL(10,2) DEFAULT NULL, `pnrr_funded` TINYINT(1) DEFAULT 0,
      `pnrr_measure` VARCHAR(200) DEFAULT NULL, `notes` TEXT DEFAULT NULL,
      `services_enabled` TEXT DEFAULT NULL, `cover_image` VARCHAR(500) DEFAULT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabelle analytics
    $db->exec("CREATE TABLE IF NOT EXISTS `page_views` (
      `id` BIGINT AUTO_INCREMENT PRIMARY KEY, `entity_type` VARCHAR(50) NOT NULL,
      `entity_id` VARCHAR(100) NOT NULL, `page_url` TEXT DEFAULT NULL, `referrer` TEXT DEFAULT NULL,
      `user_agent` TEXT DEFAULT NULL, `ip_hash` VARCHAR(64) DEFAULT NULL, `session_id` VARCHAR(100) DEFAULT NULL,
      `viewed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      INDEX `idx_entity` (`entity_type`, `entity_id`), INDEX `idx_viewed_at` (`viewed_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->exec("CREATE TABLE IF NOT EXISTS `daily_stats` (
      `id` INT AUTO_INCREMENT PRIMARY KEY, `stat_date` DATE NOT NULL,
      `entity_type` VARCHAR(50) NOT NULL, `entity_id` VARCHAR(100) NOT NULL,
      `views_count` INT DEFAULT 0, `unique_views` INT DEFAULT 0,
      UNIQUE KEY `uq_daily` (`stat_date`, `entity_type`, `entity_id`), INDEX `idx_date` (`stat_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $results[] = '✅ Tabelle food_products, accommodations, restaurants, b2g_municipalities, analytics — create/verificate';
} catch (PDOException $e) {
    $errors[] = '❌ Schema migration: ' . $e->getMessage();
}

function seedRun(PDO $db, string $label, callable $fn, array &$results, array &$errors): void {
    try {
        $fn($db);
        $results[] = "✅ $label";
    } catch (PDOException $e) {
        $errors[] = "❌ $label: " . $e->getMessage();
    }
}

// ─────────────────────────────────────────────────────────────
// 1. BORGO — Lacedonia
// ─────────────────────────────────────────────────────────────
seedRun($db, 'Borgo Lacedonia', function(PDO $db) {
    $desc = 'Lacedonia: Borgo Autentico tra Storia Millenaria e Innovazione Digitale

Lacedonia si svela al visitatore come un borgo autentico dell\'Alta Irpinia dove 13.000 anni di storia continuano a vivere tra vicoli medievali, palazzi nobiliari e tradizioni che il tempo non ha spezzato. Arroccato a 732 metri di altitudine, questo straordinario comune custodisce un\'identità profonda che affonda le radici nell\'antichità più remota e si proietta con determinazione verso il futuro.

Terra di Storia: dall\'Antica Aquilonia ai Romani
Il patrimonio archeologico di Lacedonia affonda le radici nella civiltà sannitica, quando il territorio era parte dell\'importante municipium romano di Aquilonia. Le oltre 150 grotte tufacee abitate sin da 13.000 anni fa raccontano una continuità insediativa eccezionale.

Terra di Miracoli: San Gerardo Maiella e il Pozzo Prodigioso
Lacedonia è universalmente riconosciuta come Terra di Miracoli, legata indissolubilmente alla figura di San Gerardo Maiella, santo patrono delle mamme e dei bambini, venerato in tutto il mondo cattolico.

Terra di Cultura: Francesco De Sanctis e l\'Illuminismo Meridionale
Lacedonia si distingue come Terra di Cultura grazie alla presenza dell\'Istituto Magistrale fondato da Francesco De Sanctis nel 1878, uno dei primi esempi di scuola nell\'Italia post-unificazione.

Eccellenze Culturali Internazionali
Tra le attrazioni di eccellenza brilla il MAVI (Museo Antropologico Visivo Irpino), istituzione culturale unica a livello internazionale che custodisce 1.801 fotografie scattate nel 1957 dall\'antropologo americano Frank Cancian.

Innovazione Digitale per il Futuro
Grazie alla piattaforma innovativa sviluppata da InnTour, oltre 150.000 mq del centro storico sono stati mappati con tecnologie di ultima generazione. Scansioni 3D, fotografie panoramiche a 360°, contenuti multimediali immersivi e avatar AI conversazionali permettono di esplorare ogni angolo.';

    $db->prepare("INSERT INTO boroughs
        (id, slug, name, province, region, population, altitude_meters, area_km2,
         lat, lng, main_video_url, virtual_tour_url, description, companies_count,
         hero_image_index, hero_image_alt)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
        name=VALUES(name), description=VALUES(description),
        main_video_url=VALUES(main_video_url), virtual_tour_url=VALUES(virtual_tour_url),
        updated_at=CURRENT_TIMESTAMP")
    ->execute([
        'lacedonia', 'lacedonia', 'Lacedonia',
        'Avellino', 'Campania', 2300, 732, 79.5,
        41.0472, 15.4297,
        'https://www.youtube.com/embed/XhB4SonU7Pw?autoplay=1&mute=1&loop=1&playlist=XhB4SonU7Pw',
        'https://my.treedis.com/tour/lacedonia-vrcerogn',
        $desc, 1, 0, 'Vista aerea di Lacedonia',
    ]);

    $bid = 'lacedonia';
    replaceArray($db, 'borough_highlights', 'borough_id', $bid, [
        'MAVI — Museo Antropologico Visivo Irpino (1.801 foto di Frank Cancian, 1957)',
        'Terra di Miracoli — San Gerardo Maiella e il Pozzo Prodigioso',
        'Innovazione Digitale — 150.000 mq mappati in 3D da InnTour',
    ]);
    replaceArray($db, 'borough_notable_products', 'borough_id', $bid, [
        'Caciocavallo Podolico',
        'Zafferano Irpino',
        'Asparagi selvatici di Contrada Forna',
        'Dolci natalizi lacedoniesi',
    ]);
    replaceArray($db, 'borough_notable_experiences', 'borough_id', $bid, [
        'MAVI',
        'Museo Diocesano',
        'Concattedrale S. Maria Assunta',
        'Pozzo del Miracolo',
        'Casa del Diavolo',
        'Grotte Tufacee',
        'Boschi dell\'Origlio',
        'Valle del Calaggio',
        'Tour Caseificio Podolico',
    ]);
    replaceArray($db, 'borough_notable_restaurants', 'borough_id', $bid, [
        'Trattoria del Borgo',
    ]);
}, $results, $errors);

// ─────────────────────────────────────────────────────────────
// 2. AZIENDA — Caciocavalleria De D.
// ─────────────────────────────────────────────────────────────
seedRun($db, 'Azienda Caciocavalleria De D.', function(PDO $db) {
    $db->prepare("INSERT INTO companies
        (id, slug, name, legal_name, vat_number, type, tagline,
         description_short, description_long,
         founding_year, employees_count, borough_id, address_full, lat, lng,
         contact_email, contact_phone, website_url,
         social_instagram, social_facebook,
         tier, is_verified, is_active, b2b_open_for_contact,
         founder_name, founder_quote)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
        name=VALUES(name), description_long=VALUES(description_long), updated_at=CURRENT_TIMESTAMP")
    ->execute([
        'caciocavalleria', 'caciocavalleria',
        'Caciocavalleria De D.', 'Caciocavalleria De D. S.a.s.', 'IT07890123456',
        'PRODUTTORE_FOOD', 'Il re dei formaggi irpini',
        'Caciocavallo Podolico e formaggi di latte crudo dall\'Irpinia.',
        'La Caciocavalleria De D. è un caseificio artigianale specializzato nella produzione di Caciocavallo Podolico, ottenuto dal latte delle vacche Podoliche allevate al pascolo brado sui pascoli dell\'Alta Irpinia. Questo formaggio raro, prodotto solo con latte di razza autoctona, viene stagionato per un minimo di 12 mesi nelle grotte naturali. Il risultato è un formaggio dal gusto intenso, leggermente piccante, con sentori di erbe selvatiche.',
        1975, 10, 'lacedonia',
        'Contrada Masseria 5, 83046 Lacedonia (AV)',
        41.05, 15.42,
        'info@caciocavalleriaded.it', '+39 0827 85012',
        'https://www.caciocavalleriaded.it',
        '#', '#',
        'PREMIUM', 1, 1, 1,
        'Paolo De Dominicis',
        'Le nostre vacche Podoliche camminano libere. Il formaggio che producono non ha eguali.',
    ]);

    replaceArray($db, 'company_certifications', 'company_id', 'caciocavalleria', [
        'Slow Food', 'De.Co.',
    ]);
    replaceArray($db, 'company_b2b_interests', 'company_id', 'caciocavalleria', [
        'Distribuzione', 'Ristorazione', 'Export',
    ]);
}, $results, $errors);

// ─────────────────────────────────────────────────────────────
// 3. ESPERIENZA — Tour Caseificio Podolico
// ─────────────────────────────────────────────────────────────
seedRun($db, 'Esperienza Tour Caseificio', function(PDO $db) {
    $db->prepare("INSERT INTO experiences
        (id, slug, title, tagline, description_short, description_long,
         category, provider_id, borough_id, lat, lng,
         duration_minutes, max_participants, min_participants, price_per_person,
         cancellation_policy, difficulty_level, accessibility_info, is_active)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
        title=VALUES(title), description_long=VALUES(description_long), updated_at=CURRENT_TIMESTAMP")
    ->execute([
        'tour-caseificio', 'tour-caseificio-caciocavallo-podolico',
        'Il Caciocavallo Podolico: Dal Pascolo alla Tavola',
        'Un formaggio raro, una storia millenaria',
        'Visita al caseificio artigianale con mungitura, caseificazione dal vivo e degustazione guidata di 5 stagionature.',
        'Scopri il segreto del Caciocavallo Podolico, uno dei formaggi più rari d\'Italia, nella Caciocavalleria De D. a Lacedonia. La giornata inizia con la visita ai pascoli dove le vacche Podoliche brucano libere. Si prosegue con la dimostrazione di caseificazione dal vivo. La visita si conclude nelle grotte di stagionatura in tufo. Degustazione finale di 5 stagionature diverse (3, 6, 12, 18 e 24 mesi), accompagnate da miele di castagno e confettura di fichi.',
        'GASTRONOMIA', 'caciocavalleria', 'lacedonia',
        41.05, 15.42,
        180, 10, 2, 55.00,
        'Cancellazione gratuita fino a 48 ore prima.',
        'FACILE',
        'Cantina accessibile con rampa. Visita pascoli su terreno non pavimentato.',
        1,
    ]);

    $eid = 'tour-caseificio';
    $db->prepare("DELETE FROM experience_languages WHERE experience_id=?")->execute([$eid]);
    $db->prepare("INSERT INTO experience_languages (experience_id, lang) VALUES (?,?)")->execute([$eid, 'Italiano']);

    replaceArray($db, 'experience_includes', 'experience_id', $eid, [
        'Visita pascoli', 'Dimostrazione caseificazione',
        'Degustazione 5 stagionature', 'Miele e confettura',
        'Forma di caciocavallo giovane omaggio',
    ]);
    replaceArray($db, 'experience_excludes', 'experience_id', $eid, [
        'Trasporto', 'Pranzo',
    ]);
    replaceArray($db, 'experience_bring', 'experience_id', $eid, [
        'Scarpe da campagna', 'Abbigliamento comodo',
    ]);
    replaceArray($db, 'experience_seasonal_tags', 'experience_id', $eid, [
        'primavera', 'estate', 'autunno',
    ]);

    $db->prepare("DELETE FROM experience_timeline WHERE experience_id=?")->execute([$eid]);
    $steps = [
        ['09:00', 'Pascoli',              'Visita alle vacche Podoliche al pascolo'],
        ['09:45', 'Caseificazione',       'Dimostrazione di cagliatura e filatura a mano'],
        ['10:45', 'Grotte di stagionatura','Visita alle grotte in tufo con formaggi in affinamento'],
        ['11:15', 'Degustazione',         '5 stagionature con miele e confettura'],
    ];
    $stmt = $db->prepare("INSERT INTO experience_timeline (experience_id, time_slot, title, description, sort_order) VALUES (?,?,?,?,?)");
    foreach ($steps as $i => [$time, $title, $desc]) {
        $stmt->execute([$eid, $time, $title, $desc, $i]);
    }
}, $results, $errors);

// ─────────────────────────────────────────────────────────────
// 4. ARTIGIANATO — Cesto in Vimini
// ─────────────────────────────────────────────────────────────
seedRun($db, 'Artigianato Cesto in Vimini', function(PDO $db) {
    $db->prepare("INSERT INTO craft_products
        (id, slug, name, description_short, description_long,
         technique_description, price, dimensions, weight_grams,
         artisan_id, borough_id,
         is_unique_piece, production_series_qty, lead_time_days,
         is_custom_order_available, stock_qty, is_active)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
        name=VALUES(name), description_long=VALUES(description_long), updated_at=CURRENT_TIMESTAMP")
    ->execute([
        'cesto-vimini-lacedonia', 'cesto-vimini-tradizionale-lacedonia',
        'Cesto in Vimini per Raccolta Tradizionale',
        'Cesto intrecciato a mano in vimini locale, perfetto per raccolta e decorazione.',
        'Cesto tradizionale intrecciato a mano con vimini raccolto lungo i fiumi dell\'Irpinia. La tecnica di intreccio è quella tramandata dai contadini per la raccolta di castagne, olive e uva. Il manico rinforzato permette di trasportare carichi pesanti. Ogni cesto è unico per le variazioni naturali del vimini.',
        'Intreccio a mano con tecnica tradizionale, manico rinforzato',
        42.00, 'diam. 35 cm x h 25 cm (con manico h 40 cm)', 450,
        'akudunniad', 'lacedonia',
        0, 20, 12,
        1, 15, 1,
    ]);

    $cid = 'cesto-vimini-lacedonia';
    replaceArray($db, 'craft_material_types', 'craft_id', $cid, ['vimini']);

    $db->prepare("DELETE FROM craft_customization_options WHERE craft_id=?")->execute([$cid]);
    $db->prepare("INSERT INTO craft_customization_options (craft_id, name, values_json, price_modifier) VALUES (?,?,?,?)")
       ->execute([$cid, 'Dimensione', json_encode(['Piccolo diam.25','Medio diam.35','Grande diam.45']), 15]);

    $db->prepare("DELETE FROM craft_process_steps WHERE craft_id=?")->execute([$cid]);
    $stmt = $db->prepare("INSERT INTO craft_process_steps (craft_id, title, description, sort_order) VALUES (?,?,?,?)");
    $stmt->execute([$cid, 'Raccolta vimini', 'Vimini locale raccolto in inverno lungo i fiumi', 0]);
    $stmt->execute([$cid, 'Intreccio', 'Intreccio a mano con tecnica tradizionale', 1]);
}, $results, $errors);

// ─────────────────────────────────────────────────────────────
// 5. PRODOTTO FOOD — Caciocavallo Podolico 18m
// ─────────────────────────────────────────────────────────────
seedRun($db, 'Prodotto Food Caciocavallo 18m', function(PDO $db) {
    $db->prepare("INSERT INTO food_products
        (id, slug, name, producer_id, borough_id, category,
         description_short, description_long, tagline, pairing_suggestions,
         price, unit, weight_grams, shelf_life_days, storage_instructions,
         origin_protected, allergens, ingredients,
         stock_qty, min_order_qty, is_shippable, shipping_notes,
         is_active, is_featured)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
        name=VALUES(name), description_long=VALUES(description_long), updated_at=CURRENT_TIMESTAMP")
    ->execute([
        'caciocavallo-podolico-18m', 'caciocavallo-podolico-18m',
        'Caciocavallo Podolico 18 Mesi',
        'caciocavalleria', 'lacedonia', 'FORMAGGI',
        'Il re dei formaggi irpini: Caciocavallo Podolico stagionato 18 mesi in grotta di tufo.',
        'Il Caciocavallo Podolico della Caciocavalleria De D. è un formaggio raro e prezioso, prodotto esclusivamente con latte crudo di vacche Podoliche allevate al pascolo brado sui pascoli dell\'Alta Irpinia. La cagliatura tradizionale e la stagionatura di 18 mesi nelle grotte naturali in tufo conferiscono un sapore intenso e complesso: note di erbe selvatiche, nocciola, burro fuso e un leggero piccante.',
        'Il re dei formaggi del Sud',
        'Miele di castagno, confettura di fichi, Taurasi DOCG',
        32.00, 'pezzo (ca. 1.2 kg)', 1200, 180,
        'Conservare in luogo fresco e asciutto, avvolto in carta alimentare',
        'Presidio Slow Food', 'Latte',
        'Latte crudo di vacca Podolica, caglio naturale, sale',
        25, 1, 1, 'Spedizione in confezione isotermica',
        1, 1,
    ]);
}, $results, $errors);

// ─────────────────────────────────────────────────────────────
// 6. OSPITALITÀ — Masseria Santa Lucia
// ─────────────────────────────────────────────────────────────
seedRun($db, 'Ospitalità Masseria Santa Lucia', function(PDO $db) {
    $db->prepare("INSERT INTO accommodations
        (id, slug, name, type, provider_id, borough_id,
         address_full, lat, lng, distance_center_km,
         description_short, description_long, tagline,
         rooms_count, max_guests, price_per_night_from, stars_or_category,
         check_in_time, check_out_time, min_stay_nights,
         amenities, accessibility, languages_spoken, cancellation_policy,
         booking_email, booking_phone, booking_url,
         main_video_url, virtual_tour_url,
         contact_email, contact_phone, website_url,
         social_instagram, social_facebook, social_linkedin,
         certifications, founder_name, founder_quote,
         tier, is_verified, b2b_open_for_contact, b2b_interests,
         is_active, is_featured)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
        name=VALUES(name), description_long=VALUES(description_long), updated_at=CURRENT_TIMESTAMP")
    ->execute([
        'masseria-lacedonia', 'masseria-santa-lucia-lacedonia',
        'Masseria Santa Lucia', 'MASSERIA', 'caciocavalleria', 'lacedonia',
        'Contrada Masseria 5, 83046 Lacedonia (AV)',
        41.05, 15.42, 2.5,
        'Masseria storica immersa nei pascoli dell\'Alta Irpinia, a due passi dal caseificio del Caciocavallo Podolico.',
        'Masseria Santa Lucia è un\'antica masseria ristrutturata che offre un\'esperienza di soggiorno autentica nel cuore dell\'Alta Irpinia. Le camere, ricavate dalle antiche stalle e dai fienili, conservano le volte in pietra e i pavimenti in cotto originali. La colazione è a base di prodotti del caseificio e dell\'orto aziendale. Posizione ideale per esplorare Lacedonia e i borghi circostanti.',
        'Dormire tra storia e pascoli',
        5, 12, 75.00, 'Agriturismo 3 spighe',
        '15:00', '11:00', 1,
        'WiFi, Parcheggio, Colazione inclusa, Giardino, Animali ammessi',
        'Piano terra accessibile',
        'Italiano, English',
        'Cancellazione gratuita fino a 48 ore prima',
        'booking@caciocavalleriaded.it', '+39 0827 85012',
        null,
        null, null,
        'info@caciocavalleriaded.it', '+39 0827 85012', 'https://www.caciocavalleriaded.it',
        '#', '#', null,
        'Agriturismo certificato', 'Paolo De Dominicis', 'Un luogo dove il tempo si ferma e la natura racconta.',
        'PREMIUM', 1, 1, 'Gruppi turistici, Tour operator, Pacchetti esperienziali',
        1, 1,
    ]);
}, $results, $errors);

// ─────────────────────────────────────────────────────────────
// 7. RISTORAZIONE — Trattoria del Borgo
// ─────────────────────────────────────────────────────────────
seedRun($db, 'Ristorazione Trattoria del Borgo', function(PDO $db) {
    $db->prepare("INSERT INTO restaurants
        (id, slug, name, type, borough_id,
         address_full, lat, lng,
         description_short, description_long, tagline,
         cuisine_type, price_range, seats_indoor, seats_outdoor,
         opening_hours, closing_day,
         specialties, menu_highlights,
         contact_email, contact_phone, website_url,
         social_instagram, social_facebook, social_linkedin,
         booking_url,
         accepts_groups, max_group_size,
         b2b_open_for_contact, b2b_interests,
         certifications, founder_name, founder_quote,
         tier, is_verified,
         is_active, is_featured)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
        name=VALUES(name), description_long=VALUES(description_long), updated_at=CURRENT_TIMESTAMP")
    ->execute([
        'trattoria-del-borgo-lacedonia', 'trattoria-del-borgo-lacedonia',
        'Trattoria del Borgo', 'TRATTORIA', 'lacedonia',
        'Via Roma 23, 83046 Lacedonia (AV)',
        41.0472, 15.4297,
        'Cucina tradizionale irpina nel cuore del centro storico di Lacedonia, con piatti della nonna e prodotti a km zero.',
        'La Trattoria del Borgo è il luogo dove la cucina irpina vive nella sua forma più autentica. Situata nel centro storico di Lacedonia, tra le antiche "strette" medievali, propone un menu che cambia con le stagioni: fusilli al ferretto con ragù di castrato, zuppe di legumi con cotiche, caciocavallo podolico alla piastra, e i dolci natalizi della tradizione lacedoniese. In estate si mangia nella terrazza panoramica con vista sulla Valle del Calaggio.',
        'I sapori autentici dell\'Irpinia a tavola',
        'Tradizionale Irpina', 'MEDIO', 40, 20,
        'Mar-Dom 12:00-15:00, 19:00-22:30', 'Lunedi',
        'Fusilli al ferretto con ragù di castrato, Caciocavallo Podolico alla piastra, Zuppa di legumi irpini, Dolci natalizi lacedoniesi',
        'Antipasto del pastore (salumi, formaggi, sottoli)|Fusilli al ragù di castrato|Agnello alla brace con patate|Torta di castagne',
        'info@trattoriadelborgo.it', '+39 0827 85100',
        null,
        '@trattoriadelborgo', '#', null,
        null,
        1, 30,
        1, 'Forniture locali, Gruppi turistici, Catering eventi',
        'Cucina tipica irpina', 'Maria Rossi', 'La nostra cucina racconta la storia di questa terra.',
        'BASE', 1,
        1, 1,
    ]);
}, $results, $errors);

// ─────────────────────────────────────────────────────────────
// RENDER
// ─────────────────────────────────────────────────────────────
$pageTitle = 'Seed Lacedonia';
require '_layout.php';
?>

<div class="max-w-2xl mx-auto">
  <div class="bg-slate-800 rounded-xl border border-slate-700 p-8">
    <h2 class="text-xl font-bold text-white mb-6">🌱 Seed dati Lacedonia</h2>

    <?php if ($errors): ?>
    <div class="mb-4 p-4 bg-red-900/40 border border-red-600 rounded-lg">
      <p class="text-red-300 font-semibold mb-2">Errori:</p>
      <ul class="text-sm text-red-300 space-y-1">
        <?php foreach ($errors as $e): ?>
        <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
      </ul>
      <p class="text-xs text-red-400 mt-3">Verifica che le tabelle esistano: esegui prima <code>api/schema.sql</code> e <code>api/schema_migration.sql</code> via phpMyAdmin.</p>
    </div>
    <?php endif; ?>

    <?php if ($results): ?>
    <div class="mb-4 p-4 bg-emerald-900/40 border border-emerald-600 rounded-lg">
      <p class="text-emerald-300 font-semibold mb-2">Completato:</p>
      <ul class="text-sm text-emerald-300 space-y-1">
        <?php foreach ($results as $r): ?>
        <li><?= htmlspecialchars($r) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <div class="flex gap-3 mt-4">
      <a href="/api/admin/"
         class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg transition-colors">
        Vai alla Dashboard
      </a>
      <a href="seed_lacedonia.php"
         onclick="return confirm('Ripopolare tutti i dati Lacedonia?')"
         class="px-5 py-2.5 bg-slate-600 hover:bg-slate-500 text-white text-sm rounded-lg transition-colors">
        Ri-esegui seed
      </a>
    </div>
  </div>
</div>

<?php require '_footer.php'; ?>
