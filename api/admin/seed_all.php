<?php
/**
 * Seed All Data — inserisce TUTTI i dati del frontend nel database.
 * Accesso: /api/admin/seed_all.php (protetto da sessione admin)
 *
 * Popola:
 *   - 25 Borghi dell'Alta Irpinia
 *   - 13 Aziende
 *   - 13 Esperienze
 *   - 7 Prodotti Artigianali
 */
require_once __DIR__ . '/../config/db.php';
requireAdminSession();
$db = getDB();

$results = [];
$errors  = [];

// ─────────────────────────────────────────────────────────────
// 0. SCHEMA MIGRATION
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
    $results[] = '✅ Tabelle food_products, accommodations, restaurants — create/verificate';
} catch (PDOException $e) {
    $errors[] = '❌ Schema migration: ' . $e->getMessage();
}

function seedRun(PDO $db, string $label, callable $fn, array &$results, array &$errors): void {
    try { $fn($db); $results[] = "✅ $label"; }
    catch (PDOException $e) { $errors[] = "❌ $label: " . $e->getMessage(); }
}

function replaceAwards(PDO $db, string $cid, array $awards): void {
    $db->prepare("DELETE FROM company_awards WHERE company_id=?")->execute([$cid]);
    $stmt = $db->prepare("INSERT INTO company_awards (company_id, year, title, entity) VALUES (?,?,?,?)");
    foreach ($awards as $a) { $stmt->execute([$cid, $a[0], $a[1], $a[2]]); }
}

function replaceLangs(PDO $db, string $eid, array $langs): void {
    $db->prepare("DELETE FROM experience_languages WHERE experience_id=?")->execute([$eid]);
    $stmt = $db->prepare("INSERT INTO experience_languages (experience_id, lang, sort_order) VALUES (?,?,?)");
    foreach ($langs as $i => $l) { $stmt->execute([$eid, $l, $i]); }
}

function replaceTimeline(PDO $db, string $eid, array $steps): void {
    $db->prepare("DELETE FROM experience_timeline WHERE experience_id=?")->execute([$eid]);
    $stmt = $db->prepare("INSERT INTO experience_timeline (experience_id, time_slot, title, description, sort_order) VALUES (?,?,?,?,?)");
    foreach ($steps as $i => $s) { $stmt->execute([$eid, $s[0], $s[1], $s[2], $i]); }
}

function replaceCraftCustom(PDO $db, string $cid, array $opts): void {
    $db->prepare("DELETE FROM craft_customization_options WHERE craft_id=?")->execute([$cid]);
    $stmt = $db->prepare("INSERT INTO craft_customization_options (craft_id, name, values_json, price_modifier) VALUES (?,?,?,?)");
    foreach ($opts as $o) { $stmt->execute([$cid, $o[0], json_encode($o[1]), $o[2] ?? null]); }
}

function replaceCraftProcess(PDO $db, string $cid, array $steps): void {
    $db->prepare("DELETE FROM craft_process_steps WHERE craft_id=?")->execute([$cid]);
    $stmt = $db->prepare("INSERT INTO craft_process_steps (craft_id, title, description, sort_order) VALUES (?,?,?,?)");
    foreach ($steps as $i => $s) { $stmt->execute([$cid, $s[0], $s[1], $i]); }
}

$YT = 'https://www.youtube.com/embed/dQw4w9WgXcQ';
$GMAPS = 'https://www.google.com/maps/embed?pb=!4v1700000000000!6m8!1m7!1sCAoSLEFGMVFpcE5fX0JQX3RhbWVfY2FrRXdJOXJIeHFYVWNZb2JtVXdMbTBw!2m2!1d';
$GMAPS_END = '!3f0!4f0!5f0.7820865974627469';

// ─────────────────────────────────────────────────────────────
// BORGHI (25)
// ─────────────────────────────────────────────────────────────

// Helper per INSERT borgo
function ib(PDO $db, string $id, string $name, int $pop, int $alt, float $area,
             float $lat, float $lng, string $vid, string $tour,
             string $desc, int $comp, int $hi, string $ha): void {
    $db->prepare("INSERT INTO boroughs
        (id, slug, name, province, region, population, altitude_meters, area_km2,
         lat, lng, main_video_url, virtual_tour_url, description, companies_count,
         hero_image_index, hero_image_alt)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
        name=VALUES(name), description=VALUES(description),
        main_video_url=VALUES(main_video_url), virtual_tour_url=VALUES(virtual_tour_url),
        updated_at=CURRENT_TIMESTAMP")
    ->execute([$id, $id, $name, 'Avellino', 'Campania', $pop, $alt, $area,
               $lat, $lng, $vid, $tour, $desc, $comp, $hi, $ha]);
}

seedRun($db, 'Borgo Andretta', function(PDO $db) {
    ib($db,'andretta','Andretta',1650,850,43.5,40.938,15.325,'','',
       "Andretta e un borgo dell'Alta Irpinia noto per la sua tradizione tessile artigianale e per il suggestivo centro storico medievale che domina la valle dell'Ofanto.",
       1,0,'Vista panoramica di Andretta');
    replaceArray($db,'borough_highlights','borough_id','andretta',['Museo della Civilta Contadina','Tessitura artigianale','Panorama Valle Ofanto']);
    replaceArray($db,'borough_notable_products','borough_id','andretta',['Tessuti artigianali','Olio extravergine']);
    replaceArray($db,'borough_notable_experiences','borough_id','andretta',['Workshop tessitura','Passeggiata storica']);
    replaceArray($db,'borough_notable_restaurants','borough_id','andretta',["Agriturismo Le Valli dell'Ofanto","Trattoria della Nonna Rossella","Osteria del Castello di Andretta","Bar Ristorante Il Telaio"]);
}, $results, $errors);

seedRun($db, 'Borgo Aquilonia', function(PDO $db) {
    ib($db,'aquilonia','Aquilonia',1600,740,55.7,40.979,15.463,'','',
       'Aquilonia custodisce il Museo Etnografico della Civilta Contadina "Beniamino Tartaglia", uno dei piu importanti del Mezzogiorno, testimonianza viva della cultura rurale irpina.',
       0,1,'Aquilonia borgo storico');
    replaceArray($db,'borough_highlights','borough_id','aquilonia',['Museo Etnografico','Borgo antico','Tradizioni contadine']);
    replaceArray($db,'borough_notable_products','borough_id','aquilonia',['Grano locale','Legumi']);
    replaceArray($db,'borough_notable_experiences','borough_id','aquilonia',['Visita Museo Etnografico','Percorso storico']);
    replaceArray($db,'borough_notable_restaurants','borough_id','aquilonia',['Trattoria Il Museo','Agriturismo Contadino Aquilonia',"Osteria della Civilta Contadina",'Ristorante Il Borgo Antico']);
}, $results, $errors);

seedRun($db, 'Borgo Bagnoli Irpino', function(PDO $db) use ($YT) {
    ib($db,'bagnoli-irpino','Bagnoli Irpino',2900,654,67.2,40.83,15.067,$YT,'',
       "Incastonato nel cuore dei Monti Picentini, Bagnoli Irpino e famoso per il Lago Laceno, stazione sciistica e meta estiva, e per il tartufo nero pregiato.",
       1,2,'Lago Laceno a Bagnoli Irpino');
    replaceArray($db,'borough_highlights','borough_id','bagnoli-irpino',['Lago Laceno','Tartufo nero','Monti Picentini','Stazione sciistica']);
    replaceArray($db,'borough_notable_products','borough_id','bagnoli-irpino',['Tartufo nero','Pecorino di Bagnoli','Castagne']);
    replaceArray($db,'borough_notable_experiences','borough_id','bagnoli-irpino',['Trekking Picentini','Sagra del Tartufo','Sci a Laceno']);
    replaceArray($db,'borough_notable_restaurants','borough_id','bagnoli-irpino',['Rifugio Lago Laceno','Trattoria del Tartufo Nero','Agriturismo Picentini','Ristorante Il Castagneto']);
}, $results, $errors);

seedRun($db, 'Borgo Bisaccia', function(PDO $db) {
    ib($db,'bisaccia','Bisaccia',3700,860,101.0,41.013,15.375,'','',
       "Bisaccia, dominata dal suo imponente castello ducale, e un borgo di frontiera tra Irpinia e Puglia. Patria del poeta Leonardo Sciascia, offre panorami mozzafiato sulle colline circostanti.",
       1,3,'Castello Ducale di Bisaccia');
    replaceArray($db,'borough_highlights','borough_id','bisaccia',['Castello Ducale','Parco Eolico','Panorama collinare']);
    replaceArray($db,'borough_notable_products','borough_id','bisaccia',['Grano duro','Olio extravergine']);
    replaceArray($db,'borough_notable_experiences','borough_id','bisaccia',['Visita Castello','Trekking collinare']);
    replaceArray($db,'borough_notable_restaurants','borough_id','bisaccia',['Osteria del Castello Ducale','Agriturismo Colline di Bisaccia','Trattoria La Frontiera','Ristorante Il Grano']);
}, $results, $errors);

seedRun($db, 'Borgo Cairano', function(PDO $db) {
    ib($db,'cairano','Cairano',320,800,14.3,40.893,15.367,'','',
       "Cairano e uno dei borghi piu piccoli d'Italia, arroccato su uno sperone roccioso con vista sulla diga di Conza. Un gioiello di architettura spontanea e silenzio rigenerante.",
       0,4,'Cairano borgo arroccato');
    replaceArray($db,'borough_highlights','borough_id','cairano',['Borgo arroccato','Vista Diga di Conza','Festival Cairano 7x']);
    replaceArray($db,'borough_notable_products','borough_id','cairano',[]);
    replaceArray($db,'borough_notable_experiences','borough_id','cairano',['Cairano 7x festival','Fotografia paesaggistica']);
    replaceArray($db,'borough_notable_restaurants','borough_id','cairano',['Bar Trattoria Diga di Conza','Agriturismo Il Roccolo','Osteria del Silenzio']);
}, $results, $errors);

seedRun($db, 'Borgo Calabritto', function(PDO $db) {
    ib($db,'calabritto','Calabritto',2200,550,51.6,40.782,15.22,'','',
       "Calabritto si trova alle pendici dei Monti Picentini, lungo la valle del Sele. Il paese e circondato da boschi di castagno e faggi secolari, ideale per il trekking naturalistico.",
       0,5,'Natura a Calabritto');
    replaceArray($db,'borough_highlights','borough_id','calabritto',['Oasi WWF Valle della Caccia','Cascate','Boschi Picentini']);
    replaceArray($db,'borough_notable_products','borough_id','calabritto',['Castagne','Miele di castagno']);
    replaceArray($db,'borough_notable_experiences','borough_id','calabritto',['Oasi WWF','Trekking cascate']);
    replaceArray($db,'borough_notable_restaurants','borough_id','calabritto',['Agriturismo Valle della Caccia','Trattoria Le Cascate','Rifugio dei Picentini','Osteria del Castagno']);
}, $results, $errors);

seedRun($db, 'Borgo Calitri', function(PDO $db) use ($YT, $GMAPS, $GMAPS_END) {
    ib($db,'calitri','Calitri',4200,530,101.8,40.898,15.437,$YT,
       $GMAPS.'40.898!2d15.437'.$GMAPS_END,
       "Calitri e il borgo piu grande dell'Alta Irpinia, famoso in tutto il mondo per la tradizione ceramica che risale al XVII secolo. Il rione Casale offre scorci di rara bellezza tra vicoli e botteghe artigiane.",
       3,6,'Calitri e le sue ceramiche');
    replaceArray($db,'borough_highlights','borough_id','calitri',['Ceramiche artistiche','Rione Casale','Sponz Fest','Tradizione millenaria']);
    replaceArray($db,'borough_notable_products','borough_id','calitri',['Ceramiche artistiche','Caciocavallo','Vino Aglianico']);
    replaceArray($db,'borough_notable_experiences','borough_id','calitri',['Workshop ceramica','Sponz Fest','Tour Rione Casale']);
    replaceArray($db,'borough_notable_restaurants','borough_id','calitri',['Ristorante Ceramica e Sapori','Trattoria del Rione Casale','Agriturismo Le Ceramiche','Osteria dello Sponz']);
}, $results, $errors);

seedRun($db, 'Borgo Caposele', function(PDO $db) {
    ib($db,'caposele','Caposele',3300,503,41.2,40.818,15.22,'','',
       "Caposele e la sorgente del fiume Sele, con le sue acque purissime che alimentano l'Acquedotto Pugliese. Il borgo vanta una ricca tradizione enogastronomica e un legame indissolubile con l'acqua.",
       1,7,'Sorgenti del Sele a Caposele');
    replaceArray($db,'borough_highlights','borough_id','caposele',['Sorgenti del Sele','Acquedotto Pugliese','Santuario S. Gerardo']);
    replaceArray($db,'borough_notable_products','borough_id','caposele',['Acqua','Caciocavallo','Olio']);
    replaceArray($db,'borough_notable_experiences','borough_id','caposele',['Tour sorgenti','Pellegrinaggio S. Gerardo']);
    replaceArray($db,'borough_notable_restaurants','borough_id','caposele',["Trattoria delle Sorgenti","Agriturismo San Gerardo","Osteria dell'Acquedotto","Ristorante Il Sele"]);
}, $results, $errors);

seedRun($db, 'Borgo Cassano Irpino', function(PDO $db) {
    ib($db,'cassano-irpino','Cassano Irpino',950,594,14.4,40.878,15.027,'','',
       "Piccolo borgo immerso nei boschi di castagno, Cassano Irpino e noto per la produzione di castagne e per la quiete dei suoi sentieri montani ai piedi del Terminio.",
       0,8,'Cassano Irpino tra i castagneti');
    replaceArray($db,'borough_highlights','borough_id','cassano-irpino',['Boschi di castagno','Monte Terminio','Tranquillita montanara']);
    replaceArray($db,'borough_notable_products','borough_id','cassano-irpino',['Castagne','Nocciole']);
    replaceArray($db,'borough_notable_experiences','borough_id','cassano-irpino',['Raccolta castagne','Trekking Terminio']);
    replaceArray($db,'borough_notable_restaurants','borough_id','cassano-irpino',['Agriturismo Castagneti del Terminio','Trattoria Montana del Castagno','Rifugio del Bosco']);
}, $results, $errors);

seedRun($db, 'Borgo Castelfranci', function(PDO $db) {
    ib($db,'castelfranci','Castelfranci',1900,460,14.9,40.93,15.04,'','',
       "Castelfranci e terra di vigneti e cantine, cuore pulsante della produzione vinicola irpina. I suoi terreni vulcanici donano ai vini un carattere minerale unico.",
       2,9,'Vigneti di Castelfranci');
    replaceArray($db,'borough_highlights','borough_id','castelfranci',['Vigneti storici','Cantine','Tradizione vinicola']);
    replaceArray($db,'borough_notable_products','borough_id','castelfranci',['Fiano di Avellino','Aglianico','Greco di Tufo']);
    replaceArray($db,'borough_notable_experiences','borough_id','castelfranci',['Degustazione in cantina','Tour vigneti']);
    replaceArray($db,'borough_notable_restaurants','borough_id','castelfranci',['Agriturismo Vigna Castelfranci','Osteria del Fiano','Trattoria delle Cantine',"Ristorante L'Aglianico"]);
}, $results, $errors);

seedRun($db, 'Borgo Conza della Campania', function(PDO $db) use ($GMAPS, $GMAPS_END) {
    ib($db,'conza-della-campania','Conza della Campania',1300,580,49.6,40.86,15.333,'',
       $GMAPS.'40.860!2d15.333'.$GMAPS_END,
       "Conza della Campania ospita il piu importante parco archeologico dell'Irpinia: l'antica Compsa romana. L'oasi naturale della diga sul fiume Ofanto e un paradiso per il birdwatching.",
       0,10,'Parco archeologico di Conza');
    replaceArray($db,'borough_highlights','borough_id','conza-della-campania',['Parco Archeologico Compsa','Oasi Lago di Conza','Birdwatching']);
    replaceArray($db,'borough_notable_products','borough_id','conza-della-campania',['Olio DOP','Legumi']);
    replaceArray($db,'borough_notable_experiences','borough_id','conza-della-campania',['Visita Compsa romana','Birdwatching','Canottaggio']);
    replaceArray($db,'borough_notable_restaurants','borough_id','conza-della-campania',['Agriturismo Lago di Conza','Trattoria Compsa Romana','Osteria del Birdwatcher','Ristorante Il Parco']);
}, $results, $errors);

seedRun($db, 'Borgo Guardia dei Lombardi', function(PDO $db) {
    ib($db,'guardia-dei-lombardi','Guardia dei Lombardi',1500,960,30.3,40.957,15.198,'','',
       "Guardia dei Lombardi, uno dei borghi piu alti dell'Irpinia, conserva tracce dell'insediamento longobardo nel suo nome e nella sua architettura. Terra di olio d'oliva pregiato.",
       1,11,'Guardia dei Lombardi panorama');
    replaceArray($db,'borough_highlights','borough_id','guardia-dei-lombardi',['Tradizione longobarda','Olio extravergine','Panorama montano']);
    replaceArray($db,'borough_notable_products','borough_id','guardia-dei-lombardi',['Olio Irpinia DOP','Formaggi']);
    replaceArray($db,'borough_notable_experiences','borough_id','guardia-dei-lombardi',['Percorso longobardo','Frantoio']);
    replaceArray($db,'borough_notable_restaurants','borough_id','guardia-dei-lombardi',['Agriturismo Olio DOP','Trattoria Longobarda','Osteria del Frantoio','Ristorante Il Lombardo']);
}, $results, $errors);

seedRun($db, 'Borgo Lacedonia', function(PDO $db) {
    ib($db,'lacedonia','Lacedonia',2300,700,79.5,41.05,15.42,
       'https://www.youtube.com/embed/XhB4SonU7Pw?autoplay=1&mute=1&loop=1&playlist=XhB4SonU7Pw',
       'https://my.treedis.com/tour/lacedonia-vrcerogn',
       "Lacedonia si svela al visitatore come un borgo autentico dell'Alta Irpinia dove 13.000 anni di storia continuano a vivere tra vicoli medievali, palazzi nobiliari e tradizioni che il tempo non ha spezzato. Arroccato a 732 metri di altitudine, custodisce un patrimonio unico: dal MAVI (Museo Antropologico Visivo Irpino) al Pozzo del Miracolo di San Gerardo Maiella, dalla Concattedrale di Santa Maria Assunta alle 150 grotte tufacee millenarie. Terra di Storia, Terra di Miracoli, Terra di Cultura.",
       1,12,'Lacedonia centro storico');
    replaceArray($db,'borough_highlights','borough_id','lacedonia',['MAVI - Museo Antropologico Visivo Irpino','Terra di Miracoli - San Gerardo Maiella','Innovazione Digitale - 150.000 mq mappati in 3D']);
    replaceArray($db,'borough_notable_products','borough_id','lacedonia',['Caciocavallo Podolico','Zafferano Irpino','Asparagi selvatici di Contrada Forna','Dolci natalizi tradizionali']);
    replaceArray($db,'borough_notable_experiences','borough_id','lacedonia',['MAVI - Museo Antropologico Visivo','Museo Diocesano','Pozzo del Miracolo','Concattedrale S. Maria Assunta','Casa del Diavolo','Grotte Tufacee',"Boschi dell'Origlio",'Cammino dei Tratturi','Tour Caseificio Podolico']);
    replaceArray($db,'borough_notable_restaurants','borough_id','lacedonia',['Agriturismo La Collina dei Tratturi','Trattoria Il Casale di Lacedonia','Osteria del Borgo Antico','Pizzeria Ristorante San Gerardo']);
}, $results, $errors);

seedRun($db, 'Borgo Lioni', function(PDO $db) {
    ib($db,'lioni','Lioni',5800,550,34.4,40.878,15.183,'','',
       "Lioni, ricostruita dopo il terremoto del 1980, e oggi un centro dinamico dell'Alta Irpinia con un tessuto imprenditoriale vivace e una forte identita comunitaria.",
       1,13,'Lioni veduta aerea');
    replaceArray($db,'borough_highlights','borough_id','lioni',['Ricostruzione post-sisma','Area industriale','Tradizione artigianale']);
    replaceArray($db,'borough_notable_products','borough_id','lioni',['Pasta artigianale','Prodotti da forno']);
    replaceArray($db,'borough_notable_experiences','borough_id','lioni',['Tour della ricostruzione','Mercato locale']);
    replaceArray($db,'borough_notable_restaurants','borough_id','lioni',['Trattoria della Ricostruzione','Agriturismo Alta Irpinia Lioni','Ristorante Il Mercato','Pizzeria Lioni Centro']);
}, $results, $errors);

seedRun($db, 'Borgo Montella', function(PDO $db) use ($YT, $GMAPS, $GMAPS_END) {
    ib($db,'montella','Montella',7500,600,57.6,40.843,15.017,$YT,
       $GMAPS.'40.843!2d15.017'.$GMAPS_END,
       "Montella e la patria della Castagna IGP, la piu pregiata d'Italia. Circondata dai castagneti dei Picentini, il borgo vanta anche il Santuario del Santissimo Salvatore sul Monte.",
       2,14,'Castagneti di Montella');
    replaceArray($db,'borough_highlights','borough_id','montella',['Castagna di Montella IGP','Castagneti secolari','Santuario del Salvatore']);
    replaceArray($db,'borough_notable_products','borough_id','montella',['Castagna di Montella IGP','Miele di castagno','Nocciole']);
    replaceArray($db,'borough_notable_experiences','borough_id','montella',['Raccolta castagne','Sagra della Castagna','Trekking Salvatore']);
    replaceArray($db,'borough_notable_restaurants','borough_id','montella',['Agriturismo Castagna IGP','Trattoria del Castagneto','Ristorante Il Salvatore','Osteria della Sagra']);
}, $results, $errors);

seedRun($db, 'Borgo Monteverde', function(PDO $db) {
    ib($db,'monteverde','Monteverde',700,730,24.5,40.998,15.533,'','',
       "Monteverde e un natura-vedetta al confine tra Campania, Basilicata e Puglia. Il castello normanno e le vedute panoramiche su tre regioni rendono questo luogo unico.",
       0,15,'Monteverde castello normanno');
    replaceArray($db,'borough_highlights','borough_id','monteverde',['Castello Normanno','Vista su tre regioni','Borgo silenzioso']);
    replaceArray($db,'borough_notable_products','borough_id','monteverde',['Olio','Grano']);
    replaceArray($db,'borough_notable_experiences','borough_id','monteverde',['Visita castello','Fotografia panoramica']);
    replaceArray($db,'borough_notable_restaurants','borough_id','monteverde',['Trattoria del Castello Normanno','Agriturismo Tre Regioni','Osteria del Confine']);
}, $results, $errors);

seedRun($db, 'Borgo Morra De Sanctis', function(PDO $db) {
    ib($db,'morra-de-sanctis','Morra De Sanctis',1200,850,20.1,40.927,15.25,'','',
       "Morra De Sanctis e il borgo natale di Francesco De Sanctis, padre della critica letteraria italiana. Il museo a lui dedicato e il centro storico offrono un viaggio nella cultura irpina.",
       1,16,'Morra De Sanctis borgo letterario');
    replaceArray($db,'borough_highlights','borough_id','morra-de-sanctis',['Casa-Museo De Sanctis','Borgo letterario','Lavorazione legno']);
    replaceArray($db,'borough_notable_products','borough_id','morra-de-sanctis',['Legno intagliato','Olio']);
    replaceArray($db,'borough_notable_experiences','borough_id','morra-de-sanctis',['Percorso De Sanctis','Workshop legno']);
    replaceArray($db,'borough_notable_restaurants','borough_id','morra-de-sanctis',['Trattoria De Sanctis','Agriturismo Il Letterato','Osteria del Legno Intagliato','Ristorante La Cultura']);
}, $results, $errors);

seedRun($db, 'Borgo Nusco', function(PDO $db) use ($YT, $GMAPS, $GMAPS_END) {
    ib($db,'nusco','Nusco',3800,914,43.0,40.888,15.09,$YT,
       $GMAPS.'40.888!2d15.090'.$GMAPS_END,
       "Nusco, il \"Balcone dell'Irpinia\", offre una vista spettacolare su tutta la regione. Il borgo medievale con la sua cattedrale romanica e le stradine lastricate e uno dei meglio conservati.",
       1,17,'Nusco il Balcone dell Irpinia');
    replaceArray($db,'borough_highlights','borough_id','nusco',["Balcone dell'Irpinia",'Cattedrale romanica','Borgo medievale integro']);
    replaceArray($db,'borough_notable_products','borough_id','nusco',['Vino Aglianico','Formaggi di montagna']);
    replaceArray($db,'borough_notable_experiences','borough_id','nusco',['Tour borgo medievale','Degustazione panoramica']);
    replaceArray($db,'borough_notable_restaurants','borough_id','nusco',["Ristorante Il Balcone dell'Irpinia",'Trattoria della Cattedrale','Agriturismo Nusco Alto','Osteria del Borgo Medievale']);
}, $results, $errors);

seedRun($db, 'Borgo Rocca San Felice', function(PDO $db) {
    ib($db,'rocca-san-felice','Rocca San Felice',800,750,16.0,40.948,15.165,'','',
       "Rocca San Felice e nota per la Mefite della Valle d'Ansanto, un fenomeno vulcanico unico descritto da Virgilio nell'Eneide. Il lago sulfureo e circondato da un'aura mistica.",
       0,18,'Mefite di Rocca San Felice');
    replaceArray($db,'borough_highlights','borough_id','rocca-san-felice',["Mefite Valle d'Ansanto","Lago sulfureo","Citazione nell'Eneide"]);
    replaceArray($db,'borough_notable_products','borough_id','rocca-san-felice',['Miele','Erbe officinali']);
    replaceArray($db,'borough_notable_experiences','borough_id','rocca-san-felice',['Visita Mefite','Percorso virgiliano']);
    replaceArray($db,'borough_notable_restaurants','borough_id','rocca-san-felice',["Agriturismo Valle d'Ansanto","Trattoria della Mefite","Osteria del Lago Sulfureo"]);
}, $results, $errors);

seedRun($db, "Borgo Sant'Andrea di Conza", function(PDO $db) {
    ib($db,'sant-andrea-di-conza',"Sant'Andrea di Conza",1400,660,17.6,40.847,15.365,'','',
       "Sant'Andrea di Conza e famosa per la lavorazione della pietra e del marmo locale. Gli artigiani scalpellini tramandano un'arte secolare che si riflette nelle facciate e nei portali del borgo.",
       0,19,"Artigianato della pietra a Sant Andrea");
    replaceArray($db,'borough_highlights','borough_id','sant-andrea-di-conza',['Arte degli scalpellini','Pietra locale','Portali scolpiti']);
    replaceArray($db,'borough_notable_products','borough_id','sant-andrea-di-conza',['Sculture in pietra','Manufatti lapidei']);
    replaceArray($db,'borough_notable_experiences','borough_id','sant-andrea-di-conza',['Workshop scalpellino','Tour dei portali']);
    replaceArray($db,'borough_notable_restaurants','borough_id','sant-andrea-di-conza',['Trattoria dello Scalpellino','Agriturismo Pietra Locale','Osteria dei Portali','Ristorante Il Marmo']);
}, $results, $errors);

seedRun($db, "Borgo Sant'Angelo dei Lombardi", function(PDO $db) {
    ib($db,'sant-angelo-dei-lombardi',"Sant'Angelo dei Lombardi",4000,870,35.8,40.927,15.175,'','',
       "Sant'Angelo dei Lombardi e il capoluogo storico dell'Alta Irpinia, sede dell'Abbazia del Goleto, uno dei complessi monastici piu importanti del Mezzogiorno medievale.",
       1,20,'Abbazia del Goleto');
    replaceArray($db,'borough_highlights','borough_id','sant-angelo-dei-lombardi',['Abbazia del Goleto','Cattedrale','Capoluogo Alta Irpinia']);
    replaceArray($db,'borough_notable_products','borough_id','sant-angelo-dei-lombardi',['Vino','Olio','Prodotti caseari']);
    replaceArray($db,'borough_notable_experiences','borough_id','sant-angelo-dei-lombardi',['Visita Abbazia Goleto','Tour storico','Cammino monastico']);
    replaceArray($db,'borough_notable_restaurants','borough_id','sant-angelo-dei-lombardi',['Ristorante Abbazia del Goleto','Trattoria del Capoluogo',"Agriturismo Sant'Angelo",'Osteria Longobarda']);
}, $results, $errors);

seedRun($db, 'Borgo Senerchia', function(PDO $db) {
    ib($db,'senerchia','Senerchia',700,540,31.2,40.733,15.2,'','',
       'Senerchia ospita l\'Oasi WWF "Valle della Caccia", con cascate e sentieri immersi in una natura incontaminata. Un paradiso per gli amanti del trekking e della biodiversita.',
       0,21,'Oasi WWF Senerchia');
    replaceArray($db,'borough_highlights','borough_id','senerchia',['Oasi WWF','Cascate','Biodiversita','Trekking']);
    replaceArray($db,'borough_notable_products','borough_id','senerchia',['Miele biologico','Erbe spontanee']);
    replaceArray($db,'borough_notable_experiences','borough_id','senerchia',['Oasi WWF','Trekking cascate','Workshop erboristeria']);
    replaceArray($db,'borough_notable_restaurants','borough_id','senerchia',['Agriturismo Oasi WWF Senerchia','Trattoria delle Cascate','Osteria del Trekker']);
}, $results, $errors);

seedRun($db, 'Borgo Teora', function(PDO $db) {
    ib($db,'teora','Teora',1400,660,23.0,40.855,15.253,'','',
       "Teora e il borgo della musica e del carnevale irpino. Dopo il terremoto del 1980, la comunita ha saputo rinascere preservando le sue tradizioni piu autentiche.",
       0,22,'Carnevale di Teora');
    replaceArray($db,'borough_highlights','borough_id','teora',['Carnevale Teorese','Tradizione musicale','Resilienza post-sisma']);
    replaceArray($db,'borough_notable_products','borough_id','teora',['Prodotti da forno','Conserve']);
    replaceArray($db,'borough_notable_experiences','borough_id','teora',['Carnevale Teorese','Festival musicale']);
    replaceArray($db,'borough_notable_restaurants','borough_id','teora',['Trattoria del Carnevale Teorese','Agriturismo Teora','Osteria della Musica','Ristorante La Resilienza']);
}, $results, $errors);

seedRun($db, 'Borgo Torella dei Lombardi', function(PDO $db) {
    ib($db,'torella-dei-lombardi','Torella dei Lombardi',2000,700,29.5,40.94,15.113,'','',
       "Torella dei Lombardi conserva il Castello Ruspoli-Candriano, fortezza medievale che domina il paesaggio collinare. Terra di vini e di accoglienza.",
       1,23,'Castello di Torella dei Lombardi');
    replaceArray($db,'borough_highlights','borough_id','torella-dei-lombardi',['Castello Ruspoli','Vigneti','Ospitalita irpina']);
    replaceArray($db,'borough_notable_products','borough_id','torella-dei-lombardi',['Vino Aglianico','Olio']);
    replaceArray($db,'borough_notable_experiences','borough_id','torella-dei-lombardi',['Visita castello','Degustazione vini']);
    replaceArray($db,'borough_notable_restaurants','borough_id','torella-dei-lombardi',['Agriturismo Castello Ruspoli','Trattoria dei Vigneti',"Osteria dell'Ospitalita Irpina",'Ristorante Il Castello']);
}, $results, $errors);

seedRun($db, 'Borgo Villamaina', function(PDO $db) {
    ib($db,'villamaina','Villamaina',900,540,8.4,40.965,15.088,'','',
       "Villamaina e conosciuta per le sue terme sulfuree, gia note in epoca romana. Le acque curative e il paesaggio collinare rendono questo borgo una destinazione di benessere naturale.",
       0,24,'Terme di Villamaina');
    replaceArray($db,'borough_highlights','borough_id','villamaina',['Terme sulfuree','Acque curative','Benessere naturale']);
    replaceArray($db,'borough_notable_products','borough_id','villamaina',['Prodotti termali','Erbe medicinali']);
    replaceArray($db,'borough_notable_experiences','borough_id','villamaina',['Terme naturali','Percorso benessere']);
    replaceArray($db,'borough_notable_restaurants','borough_id','villamaina',['Agriturismo Terme di Villamaina','Trattoria delle Acque Curative','Osteria del Benessere','Ristorante Il Solfureo']);
}, $results, $errors);

// ─────────────────────────────────────────────────────────────
// AZIENDE (13)
// ─────────────────────────────────────────────────────────────
function ic(PDO $db, string $id, string $name, string $legal, string $vat,
            string $type, string $tagline, string $dshort, string $dlong,
            int $year, int $emp, string $bid, string $addr, float $lat, float $lng,
            string $email, string $phone, string $web,
            string $insta, string $fb, string $linkedin,
            string $tier, int $verified, string $vid, string $tour,
            string $founder, string $quote): void {
    $db->prepare("INSERT INTO companies
        (id, slug, name, legal_name, vat_number, type, tagline,
         description_short, description_long,
         founding_year, employees_count, borough_id, address_full, lat, lng,
         contact_email, contact_phone, website_url,
         social_instagram, social_facebook, social_linkedin,
         tier, is_verified, is_active, b2b_open_for_contact,
         main_video_url, virtual_tour_url,
         founder_name, founder_quote)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
        name=VALUES(name), description_long=VALUES(description_long),
        main_video_url=VALUES(main_video_url), updated_at=CURRENT_TIMESTAMP")
    ->execute([$id,$id,$name,$legal,$vat,$type,$tagline,$dshort,$dlong,
               $year,$emp,$bid,$addr,$lat,$lng,$email,$phone,$web,
               $insta,$fb,$linkedin,$tier,$verified,1,1,
               $vid,$tour,$founder,$quote]);
}

seedRun($db, 'Azienda De Leo Carni', function(PDO $db) use ($YT) {
    ic($db,'deleo-carni','De Leo Carni','De Leo Carni S.r.l.','IT02345678901',
       'PRODUTTORE_FOOD',"La tradizione norcina dell'Alta Irpinia",
       'Salumi artigianali irpini prodotti secondo le antiche ricette di famiglia.',
       "Da oltre tre generazioni, De Leo Carni custodisce i segreti della norcineria irpina. Ogni salume nasce dalla selezione accurata delle carni di suini allevati allo stato semibrado nelle colline dell'Alta Irpinia, nutrendosi di ghiande e castagne. La lavorazione avviene ancora a mano, con la stagionatura naturale nelle cantine in tufo che garantiscono il microclima perfetto per esaltare i sapori autentici del territorio.",
       1968,12,'nusco','Via Roma 45, 83051 Nusco (AV)',40.888,15.09,
       'info@deleocarni.com','+39 0827 64521','https://www.deleocarni.com',
       '#','#','','PREMIUM',1,$YT,'','Antonio De Leo',
       'Ogni salume racconta la storia della nostra terra e del nostro lavoro.');
    replaceArray($db,'company_certifications','company_id','deleo-carni',['BIO','Slow Food']);
    replaceArray($db,'company_b2b_interests','company_id','deleo-carni',['Distribuzione','Ristorazione','Gift box']);
    replaceAwards($db,'deleo-carni',[[2023,"Miglior Soppressata d'Italia",'Slow Food']]);
}, $results, $errors);

seedRun($db, "Azienda Conte d'Oro", function(PDO $db) {
    ic($db,'contedoro',"Conte d'Oro","Conte d'Oro S.a.s.",'IT03456789012',
       'PRODUTTORE_FOOD',"L'oro verde dell'Irpinia",
       "Olio extravergine d'oliva DOP dalle colline irpine.",
       "Conte d'Oro e un frantoio familiare che produce olio extravergine d'oliva dalle cultivar autoctone dell'Irpinia: Ravece, Ogliarola e Marinese. Gli uliveti si estendono sulle colline di Guardia dei Lombardi, dove il clima e l'altitudine conferiscono all'olio note fruttate intense e un retrogusto leggermente piccante. La molitura avviene entro 24 ore dalla raccolta, a freddo, per preservare ogni proprieta organolettica.",
       1952,8,'guardia-dei-lombardi','Contrada Oliveto 12, 83040 Guardia dei Lombardi (AV)',40.957,15.198,
       'info@contedoro.com','+39 0827 41023','https://contedoro.com',
       '#','#','','PREMIUM',1,'','','Maria Conte',
       "L'olio buono si fa in campo, non in frantoio. Il frantoio lo completa.");
    replaceArray($db,'company_certifications','company_id','contedoro',['DOP','BIO']);
    replaceArray($db,'company_b2b_interests','company_id','contedoro',['Export','Ristorazione','Co-branding']);
    replaceAwards($db,'contedoro',[[2024,'Gran Menzione Olio DOP','Ercole Olivario']]);
}, $results, $errors);

seedRun($db, 'Azienda Boccella Rosa', function(PDO $db) use ($YT) {
    ic($db,'boccella-rosa','Boccella Rosa','Boccella Rosa Azienda Vinicola','IT04567890123',
       'PRODUTTORE_FOOD',"Vini d'Irpinia, anima di vulcano",
       "Vini irpini d'eccellenza: Taurasi, Fiano e Greco di Tufo.",
       "Boccella Rosa nasce dalla passione di una famiglia di viticoltori che da generazioni coltiva i vitigni autoctoni dell'Irpinia. I vigneti di Aglianico, Fiano e Greco crescono su terreni vulcanici a 500 metri di altitudine, dove le escursioni termiche e i venti montani regalano uve di straordinaria intensita. La vinificazione rispetta i ritmi naturali, con lunghi affinamenti in botti di rovere e in bottiglia.",
       1985,15,'castelfranci','Contrada Vigne 8, 83040 Castelfranci (AV)',40.93,15.04,
       'info@boccellarosa.it','+39 0827 72145','https://boccellarosa.it',
       '#','#','','PLATINUM',1,$YT,'','Giovanni Boccella',
       'Il Taurasi e un vino che ha bisogno di tempo. Come le cose migliori.');
    replaceArray($db,'company_certifications','company_id','boccella-rosa',['DOCG','DOC','BIO']);
    replaceArray($db,'company_b2b_interests','company_id','boccella-rosa',['Export','Distribuzione','Enoteca','Ristorazione']);
    replaceAwards($db,'boccella-rosa',[[2024,'Tre Bicchieri','Gambero Rosso'],[2023,'95 punti Taurasi Riserva','Wine Spectator']]);
}, $results, $errors);

seedRun($db, 'Azienda Re del Bosco', function(PDO $db) {
    ic($db,'re-del-bosco','Re del Bosco','Re del Bosco S.r.l.','IT05678901234',
       'PRODUTTORE_FOOD','Le castagne piu pregiate d\'Italia',
       'Castagna di Montella IGP e derivati dal cuore dei Picentini.',
       "Re del Bosco raccoglie e trasforma la Castagna di Montella IGP, una delle eccellenze piu riconosciute della Campania. I castagneti secolari dei Monti Picentini, tra Montella e Bagnoli Irpino, producono frutti di dimensioni generose e sapore dolcissimo. Dall'essiccatura tradizionale nei gratali alla farina di castagne macinata a pietra, ogni prodotto preserva il gusto autentico del bosco irpino.",
       1990,20,'montella','Via dei Castagneti 22, 83048 Montella (AV)',40.843,15.017,
       'info@redelbosco.it','+39 0827 61234','https://www.redelbosco.it',
       '#','#','','PREMIUM',1,'','','Luigi Montella',
       'Ogni castagna porta con se il profumo del bosco e la pazienza dei nostri nonni.');
    replaceArray($db,'company_certifications','company_id','re-del-bosco',['IGP','BIO']);
    replaceArray($db,'company_b2b_interests','company_id','re-del-bosco',['Distribuzione','Pasticceria','Export']);
    replaceAwards($db,'re-del-bosco',[[2024,'Premio Eccellenza IGP','Qualivita']]);
}, $results, $errors);

seedRun($db, 'Azienda Serrocroce', function(PDO $db) {
    ic($db,'serrocroce','Serrocroce','Serrocroce Azienda Agricola','IT06789012345',
       'PRODUTTORE_FOOD',"Vini naturali dall'Alta Irpinia",
       "Vini naturali biodinamici da vigneti d'alta quota.",
       "Serrocroce e una cantina boutique che pratica viticoltura biodinamica sui terreni vulcanici dell'Alta Irpinia. I vigneti, coltivati a 600 metri sul livello del mare, producono vini di grande personalita: minerali, freschi, con una complessita aromatica che racconta il territorio. La cantina e scavata nel tufo, dove i vini riposano in anfore di terracotta e botti di castagno locale.",
       2005,6,'castelfranci','Contrada Serrocroce, 83040 Castelfranci (AV)',40.935,15.045,
       'info@serrocroce.it','+39 0827 72098','https://www.serrocroce.it',
       '#','','','BASE',1,'','','Marco Serra',
       'Il vino naturale non si fa, si lascia fare. Noi ascoltiamo la vigna.');
    replaceArray($db,'company_certifications','company_id','serrocroce',['BIO','DOC']);
    replaceArray($db,'company_b2b_interests','company_id','serrocroce',['Enoteca','Ristorazione','Export']);
    replaceAwards($db,'serrocroce',[[2024,"Vino Naturale dell'Anno",'Guida Slow Wine']]);
}, $results, $errors);

seedRun($db, 'Azienda Caciocavalleria De D.', function(PDO $db) {
    ic($db,'caciocavalleria','Caciocavalleria De D.','Caciocavalleria De D. S.a.s.','IT07890123456',
       'PRODUTTORE_FOOD','Il re dei formaggi irpini',
       "Caciocavallo Podolico e formaggi di latte crudo dall'Irpinia.",
       "La Caciocavalleria De D. e un caseificio artigianale specializzato nella produzione di Caciocavallo Podolico, ottenuto dal latte delle vacche Podoliche allevate al pascolo brado sui pascoli dell'Alta Irpinia. Questo formaggio raro, prodotto solo con latte di razza autoctona, viene stagionato per un minimo di 12 mesi nelle grotte naturali. Il risultato e un formaggio dal gusto intenso, leggermente piccante, con sentori di erbe selvatiche.",
       1975,10,'lacedonia','Contrada Masseria 5, 83046 Lacedonia (AV)',41.05,15.42,
       'info@caciocavalleriaded.it','+39 0827 85012','https://www.caciocavalleriaded.it',
       '#','#','','PREMIUM',1,'','','Paolo De Dominicis',
       'Le nostre vacche Podoliche camminano libere. Il formaggio che producono non ha eguali.');
    replaceArray($db,'company_certifications','company_id','caciocavalleria',['Slow Food','De.Co.']);
    replaceArray($db,'company_b2b_interests','company_id','caciocavalleria',['Distribuzione','Ristorazione','Export']);
    replaceAwards($db,'caciocavalleria',[[2023,'Presidio Slow Food','Slow Food Italia']]);
}, $results, $errors);

seedRun($db, 'Azienda Carmasciando', function(PDO $db) use ($YT, $GMAPS, $GMAPS_END) {
    ic($db,'carmasciando','Carmasciando','Carmasciando Azienda Agricola','IT08901234567',
       'MISTO','Vino, terra, accoglienza',
       "Cantina con agriturismo e esperienze nel cuore dell'Irpinia.",
       "Carmasciando e un progetto di vita che unisce viticoltura, ospitalita e cultura del territorio. La cantina produce vini da vitigni autoctoni irpini, mentre l'agriturismo offre esperienze immersive: dalle vendemmie partecipate alle cene in vigna, dalla raccolta delle olive ai laboratori di cucina irpina. Un luogo dove il tempo rallenta e i sapori raccontano storie.",
       2010,8,'torella-dei-lombardi','Contrada Piano 15, 83057 Torella dei Lombardi (AV)',40.94,15.113,
       'info@carmasciando.it','+39 0827 49876','https://carmasciando.it',
       '#','#','','PLATINUM',1,$YT,$GMAPS.'40.940!2d15.113'.$GMAPS_END,
       'Salvatore Carmasciando',"Il vino e solo l'inizio. Venite a vivere questa terra.");
    replaceArray($db,'company_certifications','company_id','carmasciando',['BIO','DOC']);
    replaceArray($db,'company_b2b_interests','company_id','carmasciando',['Turismo','Ristorazione','Eventi','Co-branding']);
    replaceAwards($db,'carmasciando',[]);
}, $results, $errors);

seedRun($db, 'Azienda Torronificio del Casale', function(PDO $db) {
    ic($db,'torronificio-del-casale','Torronificio del Casale','Torronificio del Casale S.r.l.','IT09012345678',
       'PRODUTTORE_FOOD',"L'arte del torrone dal 1890",
       'Torroni artigianali irpini: miele, nocciole e tradizione.',
       "Il Torronificio del Casale porta avanti una tradizione dolciaria che risale alla fine dell'Ottocento. Il torrone viene prodotto con miele di castagno dell'Irpinia, nocciole Tonde di Giffoni e mandorle selezionate, seguendo il metodo di cottura lenta in caldaie di rame. Ogni barretta e confezionata a mano, come si faceva un tempo. Una dolcezza autentica che racconta la storia del Natale irpino.",
       1890,18,'sant-angelo-dei-lombardi',"Via Mancini 8, 83054 Sant'Angelo dei Lombardi (AV)",40.927,15.175,
       'info@torronificiodelcasale.com','+39 0827 23456','https://www.torronificiodelcasale.com',
       '#','#','','PREMIUM',1,'','','Rosa Del Casale',
       'Il segreto del nostro torrone? Il tempo. Non abbiamo mai avuto fretta.');
    replaceArray($db,'company_certifications','company_id','torronificio-del-casale',['De.Co.','Slow Food']);
    replaceArray($db,'company_b2b_interests','company_id','torronificio-del-casale',['Distribuzione','Gift box','Export','HoReCa']);
    replaceAwards($db,'torronificio-del-casale',[[2024,'Eccellenza Artigiana','CNA Campania']]);
}, $results, $errors);

seedRun($db, 'Azienda Dolci Terre', function(PDO $db) {
    $db->prepare("INSERT INTO companies
        (id, slug, name, legal_name, vat_number, type, tagline,
         description_short, description_long,
         founding_year, employees_count, borough_id, address_full, lat, lng,
         contact_email, contact_phone, website_url, social_instagram,
         tier, is_verified, is_active, b2b_open_for_contact,
         founder_name, founder_quote)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
        name=VALUES(name), description_long=VALUES(description_long), updated_at=CURRENT_TIMESTAMP")
    ->execute(['dolci-terre','dolci-terre','Dolci Terre','Dolci Terre di Irpinia','IT10123456789',
               'PRODUTTORE_FOOD',"La dolcezza autentica dell'Irpinia",
               "Confetture, mieli e conserve dal cuore verde dell'Irpinia.",
               "Dolci Terre trasforma i frutti spontanei e coltivati dell'Alta Irpinia in confetture, mieli aromatici e conserve. Dalle fichi tardive ai pomodori secchi, dal miele di castagno a quello millefiori di montagna: ogni vasetto e il risultato di una filiera cortissima, dalla pianta al prodotto finito in meno di 48 ore. Senza conservanti, senza coloranti, solo il sapore vero della terra.",
               2008,5,'montella','Via Fontana 30, 83048 Montella (AV)',40.845,15.02,
               'info@dolciterre.it','+39 0827 61890','https://www.dolciterre.it','#',
               'BASE',1,1,0,'Francesca Iannaccone',
               'La natura ci regala tutto. Noi lo mettiamo in un vasetto.']);
    replaceArray($db,'company_certifications','company_id','dolci-terre',['BIO']);
    replaceArray($db,'company_b2b_interests','company_id','dolci-terre',[]);
}, $results, $errors);

seedRun($db, 'Azienda Tenuta Pepe', function(PDO $db) {
    ic($db,'tenuta-pepe','Tenuta Pepe','Tenuta Pepe S.a.s.','IT11234567890',
       'PRODUTTORE_FOOD','Tradizione vinicola dal 1986',
       "Vini d'autore dall'Irpinia: Taurasi DOCG e Fiano di Avellino.",
       "Tenuta Pepe e una delle cantine storiche dell'Irpinia, fondata nel 1986 dalla famiglia Pepe con l'obiettivo di valorizzare i grandi vitigni autoctoni campani. I vigneti si estendono su 15 ettari di terreni argilloso-calcarei, ideali per l'Aglianico e il Fiano. La cantina combina metodi tradizionali con tecnologia enologica moderna, producendo vini eleganti che esprimono il terroir irpino.",
       1986,12,'lioni','Contrada Vigne 5, 83047 Lioni (AV)',40.878,15.183,
       'info@tenutapepe.it','+39 0827 42567','https://www.tenutapepe.it',
       '#','#','#','PLATINUM',1,'','','Roberto Pepe',
       "L'Irpinia e la Borgogna del Sud. I nostri vini lo dimostrano.");
    replaceArray($db,'company_certifications','company_id','tenuta-pepe',['DOCG','DOC','BIO']);
    replaceArray($db,'company_b2b_interests','company_id','tenuta-pepe',['Export','Enoteca','Ristorazione','Hotel']);
    replaceAwards($db,'tenuta-pepe',[[2024,'Tre Bicchieri Taurasi','Gambero Rosso'],[2023,'93 punti Fiano','James Suckling']]);
}, $results, $errors);

seedRun($db, 'Azienda Sella delle Spine', function(PDO $db) {
    ic($db,'sella-delle-spine','Sella delle Spine','Sella delle Spine Azienda Agricola','IT12345678901',
       'PRODUTTORE_FOOD','Dove nasce il Greco di Tufo',
       "Greco di Tufo DOCG e bianchi d'alta quota.",
       "Sella delle Spine e una piccola cantina familiare che coltiva Greco di Tufo sui pendii vulcanici dell'Irpinia. I vigneti, esposti a sud-est, beneficiano delle brezze montane e delle escursioni termiche che conferiscono al Greco una freschezza e una mineralita uniche. La produzione e volutamente limitata per garantire la massima qualita in ogni bottiglia.",
       1998,4,'calitri','Contrada Spine 3, 83045 Calitri (AV)',40.9,15.44,
       'info@selladellespine.com','+39 0827 35012','https://www.selladellespine.com',
       '#','','','BASE',1,'','','Anna Spinelli',
       'Il Greco di Tufo e la voce della terra vulcanica. Noi lo lasciamo parlare.');
    replaceArray($db,'company_certifications','company_id','sella-delle-spine',['DOCG','BIO']);
    replaceArray($db,'company_b2b_interests','company_id','sella-delle-spine',['Enoteca','Export']);
    replaceAwards($db,'sella-delle-spine',[]);
}, $results, $errors);

seedRun($db, 'Azienda Bifulco', function(PDO $db) {
    ic($db,'bifulco','Bifulco','Bifulco Alimentari S.r.l.','IT13456789012',
       'PRODUTTORE_FOOD','Pasta e tradizione irpina',
       'Pasta artigianale e prodotti da forno della tradizione irpina.',
       "Bifulco e un pastificio artigianale che produce pasta di semola di grano duro con trafilatura al bronzo e essiccazione lenta. I fusilli al ferretto, i cavatelli e le lagane sono le specialita della casa, preparate con lo stesso metodo che le massaie irpine usano da secoli. Il grano proviene dalle campagne dell'Alta Irpinia, macinato in piccoli mulini locali.",
       1972,9,'lioni','Via Industria 18, 83047 Lioni (AV)',40.88,15.185,
       'info@bifulco.it','+39 0827 42890','https://www.bifulco.it',
       '','#','','BASE',1,'','','Carlo Bifulco',
       "La pasta buona si riconosce dal profumo del grano, non dall'etichetta.");
    replaceArray($db,'company_certifications','company_id','bifulco',['De.Co.']);
    replaceArray($db,'company_b2b_interests','company_id','bifulco',['Distribuzione','Ristorazione']);
    replaceAwards($db,'bifulco',[]);
}, $results, $errors);

seedRun($db, "Azienda A' Ku Dunniad", function(PDO $db) use ($YT) {
    ic($db,'akudunniad',"A' Ku Dunniad","A' Ku Dunniad Cooperativa Sociale",'IT14567890123',
       'MISTO',"Il sapore della comunita",
       'Cooperativa sociale: prodotti tipici, ospitalita e inclusione.',
       'A\' Ku Dunniad (dal dialetto irpino "come una volta") e una cooperativa sociale che unisce produzione agroalimentare, turismo responsabile e inclusione sociale. I prodotti — conserve, confetture, pasta fresca — nascono dal lavoro di una comunita che ha scelto di restare e innovare nei borghi dell\'Alta Irpinia. Un modello di economia circolare e sostenibile.',
       2015,14,'calitri','Via del Borgo 7, 83045 Calitri (AV)',40.895,15.435,
       'info@akudunniad.it','+39 0827 35890','https://akudunniad.it',
       '#','#','','PREMIUM',1,$YT,'','Teresa Calitri',
       "Restare e la forma piu coraggiosa di innovazione.");
    replaceArray($db,'company_certifications','company_id','akudunniad',['BIO']);
    replaceArray($db,'company_b2b_interests','company_id','akudunniad',['Turismo','Inclusione sociale','Distribuzione']);
    replaceAwards($db,'akudunniad',[[2023,'Premio Innovazione Sociale','Ashoka Italia']]);
}, $results, $errors);

// ─────────────────────────────────────────────────────────────
// ESPERIENZE (13)
// ─────────────────────────────────────────────────────────────
seedRun($db, 'Esperienza Degustazione Taurasi', function(PDO $db) {
    $db->prepare("INSERT INTO experiences
        (id, slug, title, tagline, description_short, description_long,
         category, provider_id, borough_id, lat, lng,
         duration_minutes, max_participants, min_participants, price_per_person,
         cancellation_policy, difficulty_level, accessibility_info,
         rating, reviews_count, is_active)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE title=VALUES(title),
        description_long=VALUES(description_long),
        rating=VALUES(rating), reviews_count=VALUES(reviews_count),
        updated_at=CURRENT_TIMESTAMP")
    ->execute([
        'degustazione-taurasi','degustazione-taurasi-boccella-rosa',
        'Degustazione Taurasi & Vini d\'Irpinia',
        'Un viaggio sensoriale tra i grandi rossi del Sud',
        'Degustazione guidata di 5 vini DOCG con abbinamento di salumi e formaggi locali nella cantina storica di Boccella Rosa.',
        "Immergiti nel mondo dell'Aglianico e dei grandi vitigni irpini con una degustazione guidata nella cantina storica di Boccella Rosa, a Castelfranci. L'enologo di famiglia vi accompagnera attraverso 5 etichette — dal Greco di Tufo al Taurasi Riserva — raccontando il territorio, le tecniche di vinificazione e i segreti di ogni annata. La degustazione e accompagnata da un tagliere di salumi artigianali di De Leo Carni e Caciocavallo Podolico stagionato. Al termine, visita alla barricaia e alle vigne con vista panoramica.",
        'GASTRONOMIA','boccella-rosa','castelfranci',40.93,15.04,
        120,12,2,45.00,
        'Cancellazione gratuita fino a 48 ore prima. Rimborso del 50% fino a 24 ore prima.',
        'FACILE','Cantina accessibile con rampa. Visita vigne su terreno non pavimentato.',
        4.9,67,1
    ]);
    $eid = 'degustazione-taurasi';
    replaceLangs($db,$eid,['Italiano','English']);
    replaceArray($db,'experience_includes','experience_id',$eid,['Degustazione 5 vini','Tagliere salumi e formaggi','Visita cantina e vigne','Calice omaggio']);
    replaceArray($db,'experience_excludes','experience_id',$eid,['Trasporto','Pasti completi']);
    replaceArray($db,'experience_bring','experience_id',$eid,['Abbigliamento comodo','Scarpe chiuse per visita vigne']);
    replaceArray($db,'experience_seasonal_tags','experience_id',$eid,["tutto l'anno",'weekend']);
    replaceTimeline($db,$eid,[
        ['10:00','Accoglienza','Benvenuto con spumante di benvenuto nella terrazza panoramica'],
        ['10:20','Visita cantina','Tour della barricaia e della sala vinificazione'],
        ['10:50','Degustazione guidata','5 vini con scheda tecnica e racconto del terroir'],
        ['11:40','Tagliere & abbinamento','Salumi, formaggi e olio EVO con gli ultimi due vini'],
        ['12:00','Passeggiata in vigna','Visita ai vigneti con vista sulla valle del Calore'],
    ]);
}, $results, $errors);

seedRun($db, 'Esperienza Cooking Class Irpina', function(PDO $db) {
    $db->prepare("INSERT INTO experiences
        (id, slug, title, tagline, description_short, description_long,
         category, provider_id, borough_id, lat, lng,
         duration_minutes, max_participants, min_participants, price_per_person,
         cancellation_policy, difficulty_level, accessibility_info,
         rating, reviews_count, is_active)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE title=VALUES(title),
        description_long=VALUES(description_long),
        rating=VALUES(rating), reviews_count=VALUES(reviews_count),
        updated_at=CURRENT_TIMESTAMP")
    ->execute([
        'cooking-class-irpina','cooking-class-cucina-irpina',
        'Cooking Class: La Cucina Irpina',
        'Mani in pasta nella tradizione contadina',
        'Impara a preparare fusilli al ferretto, ragu di castrato e pastiera irpina con le massaie del borgo.',
        "Una mattinata immersi nei sapori dell'Irpinia: sotto la guida di Nonna Rosa e delle cuoche del borgo di Torella dei Lombardi, imparerai a preparare tre piatti iconici della cucina irpina. Si comincia con i fusilli al ferretto — lavorati uno a uno con il tradizionale bastoncino di metallo — conditi con ragu di castrato cotto per 6 ore. Si prosegue con una zuppa di castagne e funghi porcini, e si chiude con la pastiera irpina, variante locale del dolce napoletano. Alla fine, tutti a tavola per gustare insieme i piatti preparati, accompagnati dai vini di Carmasciando.",
        'GASTRONOMIA','carmasciando','torella-dei-lombardi',40.94,15.113,
        240,8,3,75.00,
        'Cancellazione gratuita fino a 72 ore prima.',
        'FACILE','Cucina al piano terra, accessibile.',
        4.8,43,1
    ]);
    $eid = 'cooking-class-irpina';
    replaceLangs($db,$eid,['Italiano','English']);
    replaceArray($db,'experience_includes','experience_id',$eid,['Ingredienti','Grembiule e attrezzi','Pranzo completo','Vino in accompagnamento','Ricettario digitale']);
    replaceArray($db,'experience_excludes','experience_id',$eid,['Trasporto']);
    replaceArray($db,'experience_bring','experience_id',$eid,['Curiosita e appetito!']);
    replaceArray($db,'experience_seasonal_tags','experience_id',$eid,["tutto l'anno",'famiglie']);
    replaceTimeline($db,$eid,[
        ['09:30','Accoglienza','Caffe e presentazione del menu del giorno'],
        ['10:00','Fusilli al ferretto','Impasto, lavorazione a mano e condimento'],
        ['11:00','Zuppa di castagne','Preparazione della zuppa con funghi porcini'],
        ['11:45','Pastiera irpina','Preparazione del dolce tradizionale'],
        ['12:30','A tavola!','Pranzo conviviale con i piatti preparati'],
    ]);
}, $results, $errors);

seedRun($db, 'Esperienza Tour Caseificio Podolico', function(PDO $db) {
    $db->prepare("INSERT INTO experiences
        (id, slug, title, tagline, description_short, description_long,
         category, provider_id, borough_id, lat, lng,
         duration_minutes, max_participants, min_participants, price_per_person,
         cancellation_policy, difficulty_level, accessibility_info,
         rating, reviews_count, is_active)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE title=VALUES(title),
        description_long=VALUES(description_long),
        rating=VALUES(rating), reviews_count=VALUES(reviews_count),
        updated_at=CURRENT_TIMESTAMP")
    ->execute([
        'tour-caseificio','tour-caseificio-caciocavallo-podolico',
        'Il Caciocavallo Podolico: Dal Pascolo alla Tavola',
        'Un formaggio raro, una storia millenaria',
        'Visita al caseificio artigianale con mungitura, caseificazione dal vivo e degustazione guidata di 5 stagionature.',
        "Scopri il segreto del Caciocavallo Podolico, uno dei formaggi piu rari d'Italia, nella Caciocavalleria De D. a Lacedonia. La giornata inizia con la visita ai pascoli dove le vacche Podoliche brucano libere. Si prosegue con la dimostrazione di caseificazione dal vivo: dalla cagliatura alla filatura a mano del caciocavallo, un'arte che si tramanda da generazioni. La visita si conclude nelle grotte di stagionatura in tufo. Degustazione finale di 5 stagionature diverse (3, 6, 12, 18 e 24 mesi), accompagnate da miele di castagno e confettura di fichi.",
        'GASTRONOMIA','caciocavalleria','lacedonia',41.05,15.42,
        180,10,2,55.00,
        'Cancellazione gratuita fino a 48 ore prima.',
        'FACILE','Cantina accessibile con rampa. Visita pascoli su terreno non pavimentato.',
        4.9,38,1
    ]);
    $eid = 'tour-caseificio';
    replaceLangs($db,$eid,['Italiano']);
    replaceArray($db,'experience_includes','experience_id',$eid,['Visita pascoli','Dimostrazione caseificazione','Degustazione 5 stagionature','Miele e confettura','Forma di caciocavallo giovane omaggio']);
    replaceArray($db,'experience_excludes','experience_id',$eid,['Trasporto','Pranzo']);
    replaceArray($db,'experience_bring','experience_id',$eid,['Scarpe da campagna','Abbigliamento comodo']);
    replaceArray($db,'experience_seasonal_tags','experience_id',$eid,['primavera','estate','autunno']);
    replaceTimeline($db,$eid,[
        ['09:00','Pascoli','Visita alle vacche Podoliche al pascolo'],
        ['09:45','Caseificazione','Dimostrazione di cagliatura e filatura a mano'],
        ['10:45','Grotte di stagionatura','Visita alle grotte in tufo con formaggi in affinamento'],
        ['11:15','Degustazione','5 stagionature con miele e confettura'],
    ]);
}, $results, $errors);

seedRun($db, 'Esperienza Trekking Monti Picentini', function(PDO $db) {
    $db->prepare("INSERT INTO experiences
        (id, slug, title, tagline, description_short, description_long,
         category, provider_id, borough_id, lat, lng,
         duration_minutes, max_participants, min_participants, price_per_person,
         cancellation_policy, difficulty_level, accessibility_info,
         rating, reviews_count, is_active)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE title=VALUES(title),
        description_long=VALUES(description_long),
        rating=VALUES(rating), reviews_count=VALUES(reviews_count),
        updated_at=CURRENT_TIMESTAMP")
    ->execute([
        'trekking-monti-picentini','trekking-castagneti-monti-picentini',
        'Trekking nei Castagneti dei Monti Picentini',
        'Tra boschi secolari e silenzi di montagna',
        'Escursione guidata di mezza giornata attraverso i castagneti monumentali di Montella, con pranzo al sacco.',
        "Un'escursione guidata alla scoperta dei castagneti secolari dei Monti Picentini, patrimonio naturale e produttivo dell'Irpinia. Il sentiero attraversa boschi di castagni centenari — alcuni con tronchi di oltre 6 metri di circonferenza — passando per sorgenti naturali, punti panoramici sulla valle del Calore e antichi gratali (essiccatoi tradizionali per le castagne). La guida naturalistica illustrera la biodiversita del bosco, le tecniche storiche di castanicoltura e le leggende legate ai Monti Picentini. In stagione (ottobre-novembre) e possibile partecipare alla raccolta delle castagne.",
        'NATURA','re-del-bosco','montella',40.843,15.017,
        300,15,4,35.00,
        'Cancellazione gratuita fino a 24 ore prima. Annullamento automatico in caso di maltempo con rimborso totale.',
        'MEDIO',"Sentiero non pavimentato con dislivello di 400m. Non adatto a persone con mobilita ridotta.",
        4.7,52,1
    ]);
    $eid = 'trekking-monti-picentini';
    replaceLangs($db,$eid,['Italiano','English']);
    replaceArray($db,'experience_includes','experience_id',$eid,['Guida naturalistica','Pranzo al sacco con prodotti locali','Assicurazione','Caldarroste in stagione']);
    replaceArray($db,'experience_excludes','experience_id',$eid,['Trasporto al punto di partenza','Bastoncini da trekking']);
    replaceArray($db,'experience_bring','experience_id',$eid,['Scarponi da trekking','Zaino','Acqua (1.5L)','Giacca impermeabile','Crema solare']);
    replaceArray($db,'experience_seasonal_tags','experience_id',$eid,['primavera','estate','autunno']);
    replaceTimeline($db,$eid,[
        ['08:30','Ritrovo','Punto di partenza: piazza di Montella'],
        ['09:00','Sentiero dei castagni','Salita tra i castagneti secolari'],
        ['10:30','Sorgente del Calore','Sosta alla sorgente con racconto storico'],
        ['11:30','Pranzo panoramico','Pranzo al sacco con vista sulla valle'],
        ['12:30','I Gratali','Visita agli essiccatoi tradizionali'],
        ['13:30','Rientro','Discesa e rientro a Montella'],
    ]);
}, $results, $errors);

seedRun($db, "Esperienza Sentiero dei Borghi", function(PDO $db) {
    $db->prepare("INSERT INTO experiences
        (id, slug, title, tagline, description_short, description_long,
         category, provider_id, borough_id, lat, lng,
         duration_minutes, max_participants, min_participants, price_per_person,
         cancellation_policy, difficulty_level, accessibility_info,
         rating, reviews_count, is_active)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE title=VALUES(title),
        description_long=VALUES(description_long),
        rating=VALUES(rating), reviews_count=VALUES(reviews_count),
        updated_at=CURRENT_TIMESTAMP")
    ->execute([
        'trekking-alta-irpinia','sentiero-dei-borghi-alta-irpinia',
        "Il Sentiero dei Borghi dell'Alta Irpinia",
        'Da borgo a borgo, passo dopo passo',
        "Trekking panoramico tra i borghi di Nusco e Sant'Angelo dei Lombardi, con soste enogastronomiche.",
        "Un percorso ad anello che collega due dei borghi piu affascinanti dell'Alta Irpinia: Nusco — il \"Balcone dell'Irpinia\" a 914 metri — e Sant'Angelo dei Lombardi, sede dell'antica Abbazia del Goleto. Il sentiero attraversa uliveti, pascoli e boschi di querce, offrendo panorami mozzafiato sulla valle dell'Ofanto. Due soste golose sono previste: una in un frantoio con assaggio di olio nuovo, l'altra in una masseria con bruschetta e vino.",
        'NATURA','akudunniad','nusco',40.888,15.09,
        360,12,4,40.00,
        'Cancellazione gratuita fino a 48 ore prima.',
        'MEDIO',null,
        4.6,29,1
    ]);
    $eid = 'trekking-alta-irpinia';
    replaceLangs($db,$eid,['Italiano']);
    replaceArray($db,'experience_includes','experience_id',$eid,['Guida escursionistica','Due soste enogastronomiche','Assicurazione']);
    replaceArray($db,'experience_excludes','experience_id',$eid,['Trasporto','Pranzo completo']);
    replaceArray($db,'experience_bring','experience_id',$eid,['Scarponi da trekking','Acqua','Zaino','Bastoncini consigliati']);
    replaceArray($db,'experience_seasonal_tags','experience_id',$eid,['primavera','autunno']);
    replaceTimeline($db,$eid,[
        ['08:00','Partenza da Nusco','Ritrovo nella piazza del borgo'],
        ['09:30','Sosta frantoio','Assaggio olio nuovo con bruschetta'],
        ['11:00','Abbazia del Goleto','Visita guidata dell\'abbazia medievale'],
        ['12:30','Sosta in masseria','Degustazione vino e salumi'],
        ['14:00','Rientro a Nusco','Ultimo tratto panoramico'],
    ]);
}, $results, $errors);

seedRun($db, 'Esperienza Tour Borghi Medievali', function(PDO $db) {
    $db->prepare("INSERT INTO experiences
        (id, slug, title, tagline, description_short, description_long,
         category, provider_id, borough_id, lat, lng,
         duration_minutes, max_participants, min_participants, price_per_person,
         cancellation_policy, difficulty_level, accessibility_info,
         rating, reviews_count, is_active)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE title=VALUES(title),
        description_long=VALUES(description_long),
        rating=VALUES(rating), reviews_count=VALUES(reviews_count),
        updated_at=CURRENT_TIMESTAMP")
    ->execute([
        'tour-borghi-medievali','tour-borghi-medievali-irpinia',
        "Tour dei Borghi Medievali dell'Irpinia",
        'Pietre che raccontano secoli di storia',
        'Visita guidata a Calitri, il borgo dei murales, e Bisaccia con il castello ducale e il museo archeologico.',
        "Un viaggio nel tempo attraverso due dei borghi piu suggestivi dell'Alta Irpinia. Si parte da Calitri, dove il borgo antico e diventato una galleria d'arte a cielo aperto grazie ai murales che decorano le facciate delle case abbandonate dopo il terremoto del 1980. La guida racconta la storia del sisma, della ricostruzione e della rinascita culturale. Si prosegue verso Bisaccia, dove il castello ducale domina la valle dell'Ofanto. Il museo archeologico ospita reperti dalla preistoria all'eta medievale, incluso il famoso \"Guerriero di Bisaccia\". La giornata si conclude con un aperitivo panoramico.",
        'CULTURA','akudunniad','calitri',40.9,15.44,
        300,20,4,30.00,
        'Cancellazione gratuita fino a 24 ore prima.',
        'FACILE','Parte del percorso su strade acciottolate. Il museo e accessibile.',
        4.5,34,1
    ]);
    $eid = 'tour-borghi-medievali';
    replaceLangs($db,$eid,['Italiano','English','Deutsch']);
    replaceArray($db,'experience_includes','experience_id',$eid,['Guida storico-artistica','Ingresso museo','Aperitivo finale']);
    replaceArray($db,'experience_excludes','experience_id',$eid,['Trasporto','Pranzo']);
    replaceArray($db,'experience_bring','experience_id',$eid,['Scarpe comode','Cappello in estate']);
    replaceArray($db,'experience_seasonal_tags','experience_id',$eid,["tutto l'anno"]);
    replaceTimeline($db,$eid,[
        ['09:30','Calitri — Borgo antico','Passeggiata tra i murales e le case restaurate'],
        ['11:00','Trasferimento a Bisaccia','20 minuti di auto/bus'],
        ['11:30','Castello Ducale','Visita al castello e al panorama'],
        ['12:30','Museo Archeologico','Il Guerriero di Bisaccia e i reperti'],
        ['13:30','Aperitivo panoramico','Degustazione con vista sulla valle'],
    ]);
}, $results, $errors);

seedRun($db, 'Esperienza Abbazia del Goleto', function(PDO $db) {
    $db->prepare("INSERT INTO experiences
        (id, slug, title, tagline, description_short, description_long,
         category, provider_id, borough_id, lat, lng,
         duration_minutes, max_participants, min_participants, price_per_person,
         cancellation_policy, difficulty_level, accessibility_info,
         rating, reviews_count, is_active)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE title=VALUES(title),
        description_long=VALUES(description_long),
        rating=VALUES(rating), reviews_count=VALUES(reviews_count),
        updated_at=CURRENT_TIMESTAMP")
    ->execute([
        'abbazia-goleto','visita-abbazia-goleto-sant-angelo',
        "L'Abbazia del Goleto: Mille Anni di Spiritualita",
        'Un gioiello benedettino nel cuore dell\'Irpinia',
        'Visita guidata all\'Abbazia del Goleto, fondata nel 1133, con racconto storico e momento di meditazione.',
        "L'Abbazia del Goleto, fondata nel 1133 da San Guglielmo da Vercelli, e uno dei complessi monastici piu importanti del Mezzogiorno. La visita guidata attraversa il chiostro, la chiesa superiore con le sue colonne romaniche, la Torre Febronia e i resti dell'antico monastero femminile. La guida racconta la storia affascinante dell'abbazia con aneddoti, leggende e connessioni con la storia del territorio. La visita si conclude con un momento di silenzio meditativo nel chiostro, accompagnato da tisana alle erbe dell'orto monastico.",
        'CULTURA','torronificio-del-casale','sant-angelo-dei-lombardi',40.927,15.175,
        120,25,2,15.00,
        'Cancellazione gratuita fino a 24 ore prima.',
        'FACILE','Parte del complesso accessibile. Alcuni gradini nella chiesa superiore.',
        4.6,41,1
    ]);
    $eid = 'abbazia-goleto';
    replaceLangs($db,$eid,['Italiano','English']);
    replaceArray($db,'experience_includes','experience_id',$eid,['Guida storica','Ingresso abbazia','Tisana nel chiostro']);
    replaceArray($db,'experience_excludes','experience_id',$eid,['Trasporto']);
    replaceArray($db,'experience_bring','experience_id',$eid,['Abbigliamento rispettoso del luogo sacro']);
    replaceArray($db,'experience_seasonal_tags','experience_id',$eid,["tutto l'anno"]);
    replaceTimeline($db,$eid,[
        ['10:00','Accoglienza','Introduzione storica nel sagrato'],
        ['10:20','Chiesa e chiostro','Visita guidata degli spazi principali'],
        ['11:00','Torre Febronia','Salita alla torre con panorama'],
        ['11:30','Momento meditativo','Silenzio nel chiostro con tisana'],
    ]);
}, $results, $errors);

seedRun($db, 'Esperienza Workshop Ceramica Calitri', function(PDO $db) {
    $db->prepare("INSERT INTO experiences
        (id, slug, title, tagline, description_short, description_long,
         category, provider_id, borough_id, lat, lng,
         duration_minutes, max_participants, min_participants, price_per_person,
         cancellation_policy, difficulty_level, accessibility_info,
         rating, reviews_count, is_active)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE title=VALUES(title),
        description_long=VALUES(description_long),
        rating=VALUES(rating), reviews_count=VALUES(reviews_count),
        updated_at=CURRENT_TIMESTAMP")
    ->execute([
        'workshop-ceramica-calitri','workshop-ceramica-calitri',
        'Workshop di Ceramica Tradizionale a Calitri',
        'Le tue mani, l\'argilla, la tradizione',
        'Laboratorio pratico di ceramica con maestro artigiano: modellazione al tornio, decorazione e cottura.',
        "Calitri vanta una tradizione ceramica che risale al XVIII secolo. In questo workshop pratico, il maestro ceramista Donato ti guidera nella creazione di un manufatto in argilla locale. Si parte dalla storia della ceramica calitrana — i colori, le forme, le tecniche tramandate — per poi passare alla pratica: modellazione al tornio, decorazione a mano libera con gli smalti tradizionali color terracotta e verde oliva. Il pezzo completato verra cotto nel forno del laboratorio e spedito a casa entro 10 giorni.",
        'ARTIGIANATO','akudunniad','calitri',40.895,15.435,
        180,6,2,65.00,
        'Cancellazione gratuita fino a 72 ore prima.',
        'FACILE',null,
        4.8,25,1
    ]);
    $eid = 'workshop-ceramica-calitri';
    replaceLangs($db,$eid,['Italiano','English']);
    replaceArray($db,'experience_includes','experience_id',$eid,['Materiali','Uso del tornio','Cottura e spedizione del pezzo','Certificato di autenticita']);
    replaceArray($db,'experience_excludes','experience_id',$eid,['Trasporto']);
    replaceArray($db,'experience_bring','experience_id',$eid,['Vestiti comodi (ci si sporca!)','Grembiule fornito']);
    replaceArray($db,'experience_seasonal_tags','experience_id',$eid,["tutto l'anno",'famiglie','coppie']);
    replaceTimeline($db,$eid,[
        ['15:00','Introduzione','Storia della ceramica calitrana'],
        ['15:30','Al tornio','Modellazione guidata del tuo pezzo'],
        ['16:30','Decorazione','Pittura a mano con smalti tradizionali'],
        ['17:30','Cottura & saluto','Il pezzo va in forno, brindisi finale'],
    ]);
}, $results, $errors);

seedRun($db, 'Esperienza Forest Bathing', function(PDO $db) {
    $db->prepare("INSERT INTO experiences
        (id, slug, title, tagline, description_short, description_long,
         category, provider_id, borough_id, lat, lng,
         duration_minutes, max_participants, min_participants, price_per_person,
         cancellation_policy, difficulty_level, accessibility_info,
         rating, reviews_count, is_active)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE title=VALUES(title),
        description_long=VALUES(description_long),
        rating=VALUES(rating), reviews_count=VALUES(reviews_count),
        updated_at=CURRENT_TIMESTAMP")
    ->execute([
        'forest-bathing-irpinia','forest-bathing-boschi-irpinia',
        'Forest Bathing nei Boschi dell\'Irpinia',
        'Shinrin-yoku: il bagno nella foresta',
        'Immersione sensoriale nei boschi di faggio e castagno con guida certificata in forest therapy.',
        "Il Forest Bathing (Shinrin-yoku) e una pratica nata in Giappone che consiste nell'immergersi consapevolmente nell'atmosfera del bosco. Nei boschi di faggio e castagno dell'Alta Irpinia, una guida certificata in forest therapy vi accompagnera in un percorso sensoriale di 3 ore: camminate lente, soste di ascolto, esercizi di respirazione e contemplazione. L'obiettivo non e la prestazione sportiva, ma il benessere psicofisico. La sessione si chiude con una cerimonia del te sotto gli alberi.",
        'BENESSERE','re-del-bosco','montella',40.845,15.02,
        180,10,3,40.00,
        'Cancellazione gratuita fino a 48 ore prima.',
        'FACILE','Percorso pianeggiante e breve. Adatto a tutti.',
        4.7,18,1
    ]);
    $eid = 'forest-bathing-irpinia';
    replaceLangs($db,$eid,['Italiano']);
    replaceArray($db,'experience_includes','experience_id',$eid,['Guida certificata','Tappetino e cuscino','Cerimonia del te','Kit aromaterapia bosco']);
    replaceArray($db,'experience_excludes','experience_id',$eid,['Trasporto']);
    replaceArray($db,'experience_bring','experience_id',$eid,['Scarpe comode','Abbigliamento a strati','Niente telefono (consigliato)']);
    replaceArray($db,'experience_seasonal_tags','experience_id',$eid,['primavera','estate','autunno']);
    replaceTimeline($db,$eid,[
        ['09:00','Cerchio di apertura','Presentazione e intento della sessione'],
        ['09:20','Camminata consapevole','Passeggiata lenta nel bosco con soste sensoriali'],
        ['10:30','Sosta di ascolto','Meditazione sonora tra gli alberi'],
        ['11:15','Cerimonia del te','Te di erbe selvatiche sotto le chiome'],
        ['11:45','Cerchio di chiusura',"Condivisione dell'esperienza"],
    ]);
}, $results, $errors);

seedRun($db, 'Esperienza E-Bike Tour', function(PDO $db) {
    $db->prepare("INSERT INTO experiences
        (id, slug, title, tagline, description_short, description_long,
         category, provider_id, borough_id, lat, lng,
         duration_minutes, max_participants, min_participants, price_per_person,
         cancellation_policy, difficulty_level, accessibility_info,
         rating, reviews_count, is_active)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE title=VALUES(title),
        description_long=VALUES(description_long),
        rating=VALUES(rating), reviews_count=VALUES(reviews_count),
        updated_at=CURRENT_TIMESTAMP")
    ->execute([
        'e-bike-borghi','e-bike-tour-borghi-irpinia',
        "E-Bike Tour tra i Borghi dell'Irpinia",
        'Pedalando sulla storia',
        'Tour in e-bike di 40 km tra borghi, vigneti e panorami mozzafiato, con soste gourmet.',
        "Un tour in bicicletta elettrica che collega tre borghi dell'Alta Irpinia: partenza da Lioni, salita verso Nusco (il \"Balcone dell'Irpinia\") e discesa verso Torella dei Lombardi. Il percorso di 40 km attraversa strade bianche, sentieri tra vigneti di Aglianico, uliveti e boschi di querce. Le e-bike a pedalata assistita rendono il percorso accessibile anche a chi non e allenato. Due soste gourmet sono previste: una in cantina per degustazione lampo, l'altra in un frantoio con bruschetta all'olio nuovo.",
        'AVVENTURA','tenuta-pepe','lioni',40.878,15.183,
        300,10,3,65.00,
        'Cancellazione gratuita fino a 48 ore prima. Annullamento in caso di pioggia con riprogrammazione.',
        'MEDIO',null,
        4.8,22,1
    ]);
    $eid = 'e-bike-borghi';
    replaceLangs($db,$eid,['Italiano','English']);
    replaceArray($db,'experience_includes','experience_id',$eid,['E-bike e casco','Guida ciclistica','Due soste gourmet','Aperitivo finale','Assicurazione']);
    replaceArray($db,'experience_excludes','experience_id',$eid,['Pranzo completo']);
    replaceArray($db,'experience_bring','experience_id',$eid,['Abbigliamento sportivo','Scarpe chiuse','Crema solare','Acqua']);
    replaceArray($db,'experience_seasonal_tags','experience_id',$eid,['primavera','estate','autunno']);
    replaceTimeline($db,$eid,[
        ['09:00','Briefing e partenza','Regolazione e-bike e partenza da Lioni'],
        ['10:30','Nusco panorama',"Sosta sul Balcone dell'Irpinia"],
        ['11:30','Sosta in cantina','Degustazione lampo in vigna'],
        ['12:30','Frantoio','Bruschetta con olio nuovo'],
        ['13:30','Rientro a Lioni','Aperitivo e tagliere in piazza'],
    ]);
}, $results, $errors);

seedRun($db, 'Esperienza Vendemmia Partecipata', function(PDO $db) {
    $db->prepare("INSERT INTO experiences
        (id, slug, title, tagline, description_short, description_long,
         category, provider_id, borough_id, lat, lng,
         duration_minutes, max_participants, min_participants, price_per_person,
         cancellation_policy, difficulty_level, accessibility_info,
         rating, reviews_count, is_active)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE title=VALUES(title),
        description_long=VALUES(description_long),
        rating=VALUES(rating), reviews_count=VALUES(reviews_count),
        updated_at=CURRENT_TIMESTAMP")
    ->execute([
        'vendemmia-partecipata','vendemmia-partecipata-carmasciando',
        'Vendemmia Partecipata',
        'Raccogli l\'uva, vivi la tradizione',
        "Una giornata in vigna: raccolta delle uve Aglianico, pigiatura tradizionale e pranzo contadino.",
        "La vendemmia partecipata di Carmasciando e un'esperienza unica che ti permette di vivere una delle tradizioni piu antiche dell'Irpinia. La mattinata si trascorre nei vigneti di Aglianico, raccogliendo le uve a mano nelle ceste tradizionali. Si prosegue con la pigiatura in vasca (con i piedi, come una volta!) e una lezione sulla fermentazione. A mezzogiorno, grande pranzo contadino sotto il pergolato: pasta al ragu, carne alla brace, verdure dell'orto, dolci e vino a volonta. Si torna a casa con una bottiglia di vino della vendemmia precedente.",
        'GASTRONOMIA','carmasciando','torella-dei-lombardi',40.94,15.113,
        420,20,6,85.00,
        'Cancellazione gratuita fino a 7 giorni prima (evento stagionale).',
        'MEDIO',null,
        4.9,56,1
    ]);
    $eid = 'vendemmia-partecipata';
    replaceLangs($db,$eid,['Italiano','English']);
    replaceArray($db,'experience_includes','experience_id',$eid,['Guida enologica','Attrezzatura vendemmia','Pranzo contadino completo','Vino','Bottiglia omaggio']);
    replaceArray($db,'experience_excludes','experience_id',$eid,['Trasporto']);
    replaceArray($db,'experience_bring','experience_id',$eid,['Scarpe chiuse da campagna','Vestiti che si possono sporcare','Cappello']);
    replaceArray($db,'experience_seasonal_tags','experience_id',$eid,['settembre','ottobre','vendemmia']);
    replaceTimeline($db,$eid,[
        ['08:30','Accoglienza','Colazione in vigna con pane e olio'],
        ['09:00','Raccolta uve','Vendemmia a mano nei vigneti'],
        ['11:00','Pigiatura','Pigiatura tradizionale con i piedi'],
        ['12:00','Lezione enologica','Dal mosto al vino: la fermentazione'],
        ['12:30','Pranzo contadino','Grande pranzo sotto il pergolato'],
        ['15:00','Saluto','Bottiglia omaggio e saluto'],
    ]);
}, $results, $errors);

seedRun($db, 'Esperienza Notte del Torrone', function(PDO $db) {
    $db->prepare("INSERT INTO experiences
        (id, slug, title, tagline, description_short, description_long,
         category, provider_id, borough_id, lat, lng,
         duration_minutes, max_participants, min_participants, price_per_person,
         cancellation_policy, difficulty_level, accessibility_info,
         rating, reviews_count, is_active)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE title=VALUES(title),
        description_long=VALUES(description_long),
        rating=VALUES(rating), reviews_count=VALUES(reviews_count),
        updated_at=CURRENT_TIMESTAMP")
    ->execute([
        'notte-torrone','notte-del-torrone-sant-angelo',
        'La Notte del Torrone',
        "Il dolce rito dell'Irpinia",
        'Laboratorio serale di torrone artigianale con il mastro torronaio del Casale, seguito da degustazione.',
        "Una serata magica nel laboratorio del Torronificio del Casale, dove il mastro torronaio svela i segreti di una ricetta che risale al 1890. Sotto la luce calda delle lampade, si impara a scaldare il miele, montare l'albume, tostare le nocciole e versare il torrone nelle forme tradizionali. Ogni partecipante prepara il proprio torrone da portare a casa. La serata si chiude con una degustazione di 5 varieta di torrone e croccantini, accompagnati da un bicchiere di passito irpino.",
        'GASTRONOMIA','torronificio-del-casale','sant-angelo-dei-lombardi',40.927,15.175,
        150,8,2,50.00,
        'Cancellazione gratuita fino a 48 ore prima.',
        'FACILE',null,
        4.9,31,1
    ]);
    $eid = 'notte-torrone';
    replaceLangs($db,$eid,['Italiano']);
    replaceArray($db,'experience_includes','experience_id',$eid,['Ingredienti','Torrone fatto da te (da portare a casa)','Degustazione 5 varieta','Passito irpino']);
    replaceArray($db,'experience_excludes','experience_id',$eid,['Trasporto']);
    replaceArray($db,'experience_bring','experience_id',$eid,['Niente di speciale — grembiule fornito']);
    replaceArray($db,'experience_seasonal_tags','experience_id',$eid,['autunno','inverno','Natale']);
    replaceTimeline($db,$eid,[
        ['19:00','Benvenuto','Caffe e introduzione alla storia del torrone'],
        ['19:30','Preparazione','Cottura miele, tostatura nocciole, montatura albumi'],
        ['20:15','Il tuo torrone','Versamento e formatura del torrone personale'],
        ['20:45','Degustazione','5 varieta di torrone con passito'],
    ]);
}, $results, $errors);

seedRun($db, "Esperienza Raccolta Olive & Frantoio", function(PDO $db) {
    $db->prepare("INSERT INTO experiences
        (id, slug, title, tagline, description_short, description_long,
         category, provider_id, borough_id, lat, lng,
         duration_minutes, max_participants, min_participants, price_per_person,
         cancellation_policy, difficulty_level, accessibility_info,
         rating, reviews_count, is_active)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE title=VALUES(title),
        description_long=VALUES(description_long),
        rating=VALUES(rating), reviews_count=VALUES(reviews_count),
        updated_at=CURRENT_TIMESTAMP")
    ->execute([
        'olio-raccolta','raccolta-olive-frantoio-contedoro',
        "Raccolta Olive & Frantoio: L'Oro Verde dell'Irpinia",
        "Dall'albero alla bottiglia in un giorno",
        "Giornata nel uliveto di Conte d'Oro: raccolta olive, visita al frantoio e degustazione guidata di oli.",
        "Vivi l'esperienza completa dell'olivicoltura irpina con Conte d'Oro. La mattinata si trascorre nell'uliveto di Guardia dei Lombardi, raccogliendo le olive Ravece con il metodo tradizionale dei pettini e delle reti. Si prosegue al frantoio, dove assisterai alla spremitura a freddo delle olive appena raccolte. L'olio nuovo, verde e piccante, verra assaggiato direttamente dal separatore. La giornata si chiude con una degustazione guidata di 4 oli (Ravece, Ogliarola, Marinese e Blend), accompagnati da pane casereccio appena sfornato.",
        'GASTRONOMIA','contedoro','guardia-dei-lombardi',40.957,15.198,
        300,12,4,55.00,
        'Cancellazione gratuita fino a 72 ore prima.',
        'FACILE',null,
        4.7,27,1
    ]);
    $eid = 'olio-raccolta';
    replaceLangs($db,$eid,['Italiano','English']);
    replaceArray($db,'experience_includes','experience_id',$eid,['Raccolta olive guidata','Visita frantoio','Degustazione 4 oli','Pane casereccio','Bottiglia olio nuovo omaggio']);
    replaceArray($db,'experience_excludes','experience_id',$eid,['Trasporto','Pranzo']);
    replaceArray($db,'experience_bring','experience_id',$eid,['Scarpe da campagna','Vestiti comodi','Cappello']);
    replaceArray($db,'experience_seasonal_tags','experience_id',$eid,['ottobre','novembre','olio nuovo']);
    replaceTimeline($db,$eid,[
        ['09:00','Uliveto','Raccolta olive con pettini e reti'],
        ['11:00','Frantoio','Spremitura a freddo delle olive appena raccolte'],
        ['12:00','Assaggio olio nuovo','Il primo olio: verde, piccante, vivo'],
        ['12:30','Degustazione guidata','4 monocultivar con pane casereccio'],
        ['13:30','Bottiglia omaggio','La tua bottiglia di olio nuovo'],
    ]);
}, $results, $errors);

// ─────────────────────────────────────────────────────────────
// PRODOTTI ARTIGIANALI (7)
// ─────────────────────────────────────────────────────────────
seedRun($db, 'Artigianato Piatto Ceramica Calitri', function(PDO $db) {
    $db->prepare("INSERT INTO craft_products
        (id, slug, name, description_short, description_long,
         technique_description, price, dimensions, weight_grams,
         artisan_id, borough_id,
         is_unique_piece, production_series_qty, lead_time_days,
         is_custom_order_available, stock_qty, rating, reviews_count, is_active)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
        name=VALUES(name), description_long=VALUES(description_long),
        rating=VALUES(rating), reviews_count=VALUES(reviews_count),
        updated_at=CURRENT_TIMESTAMP")
    ->execute([
        'piatto-ceramica-calitri','piatto-ceramica-tradizionale-calitri',
        'Piatto in Ceramica Tradizionale di Calitri',
        'Piatto decorato a mano con motivi tradizionali irpini in terracotta e verde oliva.',
        "Questo piatto rappresenta l'antica tradizione ceramica di Calitri, borgo famoso per la lavorazione dell'argilla dal XVIII secolo. Realizzato al tornio con argilla locale, il piatto viene decorato a mano con gli smalti tradizionali color terracotta e verde oliva che caratterizzano la ceramica irpina. Ogni pezzo e unico grazie alla decorazione manuale, che riprende i motivi geometrici e floreali della tradizione contadina. Diametro 28 cm, adatto sia come oggetto decorativo che per uso da tavola.",
        'Modellazione al tornio, decorazione a mano libera, doppia cottura a 980°C',
        45.00,'Ø 28 cm × h 3 cm',850,
        'akudunniad','calitri',
        0,12,15,1,8,4.8,23,1
    ]);
    $cid = 'piatto-ceramica-calitri';
    replaceArray($db,'craft_material_types','craft_id',$cid,['ceramica','argilla']);
    replaceCraftCustom($db,$cid,[
        ['Colore dominante',['Terracotta','Verde oliva','Blu cobalto'],null],
        ['Dimensione',['Ø 22cm','Ø 28cm','Ø 32cm'],10],
    ]);
    replaceCraftProcess($db,$cid,[
        ['Modellazione al tornio',"L'argilla locale viene lavorata al tornio per creare la forma del piatto"],
        ['Decorazione a mano','Ogni pezzo viene decorato a mano con smalti tradizionali'],
    ]);
}, $results, $errors);

seedRun($db, 'Artigianato Vaso Ceramica Calitri', function(PDO $db) {
    $db->prepare("INSERT INTO craft_products
        (id, slug, name, description_short, description_long,
         technique_description, price, dimensions, weight_grams,
         artisan_id, borough_id,
         is_unique_piece, lead_time_days,
         is_custom_order_available, stock_qty, rating, reviews_count, is_active)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
        name=VALUES(name), description_long=VALUES(description_long),
        rating=VALUES(rating), reviews_count=VALUES(reviews_count),
        updated_at=CURRENT_TIMESTAMP")
    ->execute([
        'vaso-ceramica-calitri','vaso-ceramica-alta-calitri',
        'Vaso in Ceramica Alta di Calitri',
        'Vaso decorativo cilindrico con decorazioni geometriche tradizionali.',
        "Vaso cilindrico alto realizzato con argilla locale di Calitri, decorato con motivi geometrici ispirati ai pavimenti delle chiese medievali irpine. La forma slanciata e l'altezza di 35 cm lo rendono perfetto come elemento decorativo o come contenitore per fiori secchi e composizioni. Ogni vaso e firmato dall'artigiano e accompagnato da certificato di autenticita.",
        'Modellazione a colombino, decorazione a mano, cristallina trasparente',
        65.00,'Ø 12 cm × h 35 cm',1200,
        'akudunniad','calitri',
        1,20,
        1,1,4.9,12,1
    ]);
    $cid = 'vaso-ceramica-calitri';
    replaceArray($db,'craft_material_types','craft_id',$cid,['ceramica']);
    replaceCraftCustom($db,$cid,[]);
    replaceCraftProcess($db,$cid,[
        ['Tecnica a colombino','Il vaso viene costruito sovrapponendo "colombini" di argilla'],
        ['Decorazione geometrica','Motivi geometrici incisi e dipinti a mano'],
    ]);
}, $results, $errors);

seedRun($db, 'Artigianato Coperta Lana', function(PDO $db) {
    $db->prepare("INSERT INTO craft_products
        (id, slug, name, description_short, description_long,
         technique_description, price, dimensions, weight_grams,
         artisan_id, borough_id,
         is_unique_piece, production_series_qty, lead_time_days,
         is_custom_order_available, stock_qty, rating, reviews_count, is_active)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
        name=VALUES(name), description_long=VALUES(description_long),
        rating=VALUES(rating), reviews_count=VALUES(reviews_count),
        updated_at=CURRENT_TIMESTAMP")
    ->execute([
        'coperta-lana-tessuta','coperta-lana-tessuta-a-mano',
        'Coperta in Lana Tessuta a Mano',
        'Coperta calda in lana locale tessuta al telaio tradizionale con motivi a righe.',
        "Coperta in pura lana di pecora locale, tessuta interamente a mano su telaio tradizionale. Le lane vengono filate a mano e tinte con coloranti naturali estratti da piante locali: robbia per il rosso, guado per il blu, reseda per il giallo. Il risultato e una coperta morbida, calda e resistente nel tempo, con motivi a righe irregolari che ricordano i tessuti della tradizione pastorale irpina. Dimensioni generose per letto matrimoniale.",
        'Filatura a mano, tintura con coloranti naturali, tessitura al telaio',
        185.00,'200 cm × 150 cm',2200,
        'akudunniad','calitri',
        0,5,30,1,3,4.9,18,1
    ]);
    $cid = 'coperta-lana-tessuta';
    replaceArray($db,'craft_material_types','craft_id',$cid,['tessuto','lana']);
    replaceCraftCustom($db,$cid,[
        ['Colori dominanti',['Rosso-crema','Blu-grigio','Giallo-verde'],null],
        ['Dimensione',['Singolo 150×120','Matrimoniale 200×150'],40],
    ]);
    replaceCraftProcess($db,$cid,[
        ['Filatura della lana','La lana grezza viene cardata e filata a mano'],
        ['Tintura naturale','Tintura in bagno con estratti vegetali'],
        ['Tessitura al telaio','Tessitura manuale su telaio tradizionale in legno'],
    ]);
}, $results, $errors);

seedRun($db, 'Artigianato Tagliere Castagno', function(PDO $db) {
    $db->prepare("INSERT INTO craft_products
        (id, slug, name, description_short, description_long,
         technique_description, price, dimensions, weight_grams,
         artisan_id, borough_id,
         is_unique_piece, production_series_qty, lead_time_days,
         is_custom_order_available, stock_qty, rating, reviews_count, is_active)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
        name=VALUES(name), description_long=VALUES(description_long),
        rating=VALUES(rating), reviews_count=VALUES(reviews_count),
        updated_at=CURRENT_TIMESTAMP")
    ->execute([
        'tagliere-castagno-intagliato','tagliere-castagno-intagliato',
        'Tagliere in Legno di Castagno Intagliato',
        'Tagliere artigianale in legno di castagno dei Picentini con manico intagliato.',
        "Tagliere da cucina ricavato da un unico blocco di legno di castagno proveniente dai boschi dei Monti Picentini. Il legno di castagno, tradizionalmente usato in Irpinia per utensili da cucina e botti, e naturalmente resistente all'acqua e antimicrobico. Il manico e intagliato a mano con motivi floreali. Ogni tagliere e levigato a mano, oliato con olio di lino e pronto all'uso. Perfetto per servire salumi e formaggi o come elemento decorativo.",
        'Intaglio a mano, levigatura, trattamento con olio di lino',
        38.00,'40 cm × 20 cm × 2 cm',650,
        're-del-bosco','montella',
        0,15,10,1,12,4.7,31,1
    ]);
    $cid = 'tagliere-castagno-intagliato';
    replaceArray($db,'craft_material_types','craft_id',$cid,['legno']);
    replaceCraftCustom($db,$cid,[
        ['Dimensione',['Piccolo 30×15','Grande 40×20','Extra 50×25'],12],
    ]);
    replaceCraftProcess($db,$cid,[
        ['Selezione del legno','Castagno stagionato dei Monti Picentini'],
        ['Intaglio a mano','Il manico viene intagliato con sgorbie tradizionali'],
        ['Finitura naturale','Levigatura fine e oliatura con olio di lino'],
    ]);
}, $results, $errors);

seedRun($db, 'Artigianato Ciotola Noce', function(PDO $db) {
    $db->prepare("INSERT INTO craft_products
        (id, slug, name, description_short, description_long,
         technique_description, price, dimensions, weight_grams,
         artisan_id, borough_id,
         is_unique_piece,
         is_custom_order_available, stock_qty, rating, reviews_count, is_active)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
        name=VALUES(name), description_long=VALUES(description_long),
        rating=VALUES(rating), reviews_count=VALUES(reviews_count),
        updated_at=CURRENT_TIMESTAMP")
    ->execute([
        'ciotola-legno-noce','ciotola-tornita-legno-noce',
        'Ciotola Tornita in Legno di Noce',
        'Ciotola decorativa in legno di noce locale, tornita a mano e lucidata.',
        "Elegante ciotola decorativa ricavata da un blocco di legno di noce locale. La tornatura manuale esalta le venature naturali del legno, creando un gioco di colori dal beige al marrone scuro. La forma organica e irregolare rende ogni pezzo unico. Adatta come svuota-tasche, porta-frutta o semplice oggetto decorativo. Il legno e trattato con cera d'api naturale che esalta il profumo del noce.",
        "Tornita al tornio, levigatura fine, lucidatura con cera d'api",
        55.00,'Ø 18 cm × h 8 cm (dimensioni variabili)',380,
        're-del-bosco','montella',
        1,
        0,1,4.8,9,1
    ]);
    $cid = 'ciotola-legno-noce';
    replaceArray($db,'craft_material_types','craft_id',$cid,['legno']);
    replaceCraftCustom($db,$cid,[]);
    replaceCraftProcess($db,$cid,[
        ['Tornio','Il blocco di noce viene tornito per creare la forma'],
        ['Finitura con cera','Lucidatura manuale con cera d\'api naturale'],
    ]);
}, $results, $errors);

seedRun($db, 'Artigianato Appendiabiti Ferro Battuto', function(PDO $db) {
    $db->prepare("INSERT INTO craft_products
        (id, slug, name, description_short, description_long,
         technique_description, price, dimensions, weight_grams,
         artisan_id, borough_id,
         is_unique_piece, production_series_qty, lead_time_days,
         is_custom_order_available, stock_qty, rating, reviews_count, is_active)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
        name=VALUES(name), description_long=VALUES(description_long),
        rating=VALUES(rating), reviews_count=VALUES(reviews_count),
        updated_at=CURRENT_TIMESTAMP")
    ->execute([
        'appendiabiti-ferro-battuto','appendiabiti-ferro-battuto-parete',
        'Appendiabiti da Parete in Ferro Battuto',
        'Appendiabiti artigianale in ferro battuto con 5 ganci forgiati a mano.',
        "Appendiabiti da parete realizzato completamente a mano nella fucina tradizionale. Il ferro viene scaldato nella forgia a carbone e battuto sull'incudine per creare la forma. I 5 ganci sono forgiati individualmente con terminazione a ricciolo, tipica della tradizione del ferro battuto irpino. La struttura portante presenta decorazioni vegetali. Finitura con vernice trasparente antiruggine. Un pezzo funzionale che porta l'arte della forgiatura nella casa moderna.",
        'Forgiatura a caldo, battitura a mano, saldatura, verniciatura antiruggine',
        95.00,'60 cm × 15 cm × 8 cm',2800,
        'akudunniad','nusco',
        0,6,25,1,4,4.6,14,1
    ]);
    $cid = 'appendiabiti-ferro-battuto';
    replaceArray($db,'craft_material_types','craft_id',$cid,['ferro']);
    replaceCraftCustom($db,$cid,[
        ['Numero ganci',['3 ganci','5 ganci','7 ganci'],20],
        ['Finitura',['Nero opaco','Nero lucido','Ruggine naturale protetta'],null],
    ]);
    replaceCraftProcess($db,$cid,[
        ['Forgiatura','Il ferro viene scaldato a 1200°C e battuto sull\'incudine'],
        ['Assemblaggio','I ganci vengono saldati alla struttura portante'],
        ['Finitura','Verniciatura antiruggine a pennello'],
    ]);
}, $results, $errors);

seedRun($db, 'Artigianato Cesto Vimini', function(PDO $db) {
    $db->prepare("INSERT INTO craft_products
        (id, slug, name, description_short, description_long,
         technique_description, price, dimensions, weight_grams,
         artisan_id, borough_id,
         is_unique_piece, production_series_qty, lead_time_days,
         is_custom_order_available, stock_qty, rating, reviews_count, is_active)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
        name=VALUES(name), description_long=VALUES(description_long),
        rating=VALUES(rating), reviews_count=VALUES(reviews_count),
        updated_at=CURRENT_TIMESTAMP")
    ->execute([
        'cesto-vimini-raccolta','cesto-vimini-tradizionale-raccolta',
        'Cesto in Vimini per Raccolta Tradizionale',
        'Cesto intrecciato a mano in vimini locale, perfetto per raccolta e decorazione.',
        "Cesto tradizionale intrecciato a mano con vimini raccolto lungo i fiumi dell'Irpinia. La tecnica di intreccio e quella tramandata dai contadini per la raccolta di castagne, olive e uva. Il manico rinforzato permette di trasportare carichi pesanti. Ogni cesto e unico per le variazioni naturali del vimini. Con il tempo il colore evolve dal verde-beige al miele. Perfetto per la spesa al mercato, come porta-legna o elemento decorativo.",
        'Intreccio a mano con tecnica tradizionale, manico rinforzato',
        42.00,'Ø 35 cm × h 25 cm (con manico h 40 cm)',450,
        'akudunniad','calitri',
        0,20,12,1,15,4.5,27,1
    ]);
    $cid = 'cesto-vimini-raccolta';
    replaceArray($db,'craft_material_types','craft_id',$cid,['vimini']);
    replaceCraftCustom($db,$cid,[
        ['Dimensione',['Piccolo Ø25','Medio Ø35','Grande Ø45'],15],
    ]);
    replaceCraftProcess($db,$cid,[
        ['Raccolta vimini','Vimini locale raccolto in inverno lungo i fiumi'],
        ['Intreccio','Intreccio a mano con tecnica tradizionale'],
    ]);
}, $results, $errors);

// ─────────────────────────────────────────────────────────────
// RENDER
// ─────────────────────────────────────────────────────────────
$pageTitle = 'Seed Tutti i Dati';
require '_layout.php';
?>

<div class="max-w-2xl mx-auto">
  <div class="bg-slate-800 rounded-xl border border-slate-700 p-8">
    <h2 class="text-xl font-bold text-white mb-6">🌱 Seed — Tutti i Dati</h2>
    <p class="text-slate-400 text-sm mb-4">Popola: 25 Borghi · 13 Aziende · 13 Esperienze · 7 Artigianato</p>

    <?php if ($errors): ?>
    <div class="mb-4 p-4 bg-red-900/40 border border-red-600 rounded-lg">
      <p class="text-red-300 font-semibold mb-2">Errori (<?= count($errors) ?>):</p>
      <ul class="text-sm text-red-300 space-y-1">
        <?php foreach ($errors as $e): ?>
        <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <?php if ($results): ?>
    <div class="mb-4 p-4 bg-emerald-900/40 border border-emerald-600 rounded-lg">
      <p class="text-emerald-300 font-semibold mb-2">Completato (<?= count($results) ?>):</p>
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
      <a href="seed_all.php"
         onclick="return confirm('Ri-eseguire il seed di tutti i dati?')"
         class="px-5 py-2.5 bg-slate-600 hover:bg-slate-500 text-white text-sm rounded-lg transition-colors">
        Ri-esegui seed
      </a>
    </div>
  </div>
</div>

<?php require '_footer.php'; ?>
