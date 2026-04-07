<?php
// ============================================================
// MetaBorghi — Sondaggio Pubblico per Operatori
// Pagina pubblica — nessun login richiesto
// ============================================================
require_once __DIR__ . '/config/db.php';

// ── Crea tabella survey_submissions se non esiste ──────────
$db = getDB();
$db->exec("CREATE TABLE IF NOT EXISTS `survey_submissions` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `entity_type`     VARCHAR(50)   NOT NULL,
  `submitter_name`  VARCHAR(200)  DEFAULT NULL,
  `submitter_email` VARCHAR(200)  DEFAULT NULL,
  `submitter_phone` VARCHAR(50)   DEFAULT NULL,
  `entity_name`     VARCHAR(300)  DEFAULT NULL,
  `data`            JSON          NOT NULL,
  `status`          ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `submitted_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reviewed_at`     TIMESTAMP     NULL,
  `reviewed_by`     VARCHAR(200)  DEFAULT NULL,
  `notes`           TEXT          DEFAULT NULL,
  INDEX `idx_status` (`status`),
  INDEX `idx_entity` (`entity_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$submitted = false;
$error     = '';

// ── Helper: genera slug da testo ───────────────────────────
function makeSlug(string $text): string {
    $map = ['à'=>'a','á'=>'a','â'=>'a','ã'=>'a','ä'=>'a','è'=>'e','é'=>'e','ê'=>'e','ë'=>'e',
            'ì'=>'i','í'=>'i','î'=>'i','ï'=>'i','ò'=>'o','ó'=>'o','ô'=>'o','õ'=>'o','ö'=>'o',
            'ù'=>'u','ú'=>'u','û'=>'u','ü'=>'u','ý'=>'y','ç'=>'c','ñ'=>'n','ß'=>'ss'];
    $text = strtr(strtolower($text), $map);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', trim($text));
    return substr($text, 0, 80);
}

// ── Gestione POST ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entityType = trim($_POST['entity_type'] ?? '');
    $subName    = trim($_POST['submitter_name']  ?? '');
    $subEmail   = trim($_POST['submitter_email'] ?? '');
    $subPhone   = trim($_POST['submitter_phone'] ?? '');
    $entityName = trim($_POST['name'] ?? '');

    $allowed = ['azienda','esperienza','artigianato','prodotto_food','ristorazione','ospitalita'];
    if (!in_array($entityType, $allowed, true)) {
        $error = 'Tipo di attività non valido.';
    } elseif (!$subName || !$subEmail || !$entityName) {
        $error = 'Nome referente, email e nome attività sono obbligatori.';
    } elseif (!filter_var($subEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Indirizzo email non valido.';
    } else {
        // Raccoglie tutti i campi POST (esclude metadati)
        $exclude = ['entity_type','submitter_name','submitter_email','submitter_phone'];
        $data = [];
        foreach ($_POST as $k => $v) {
            if (!in_array($k, $exclude, true)) {
                $data[$k] = is_array($v) ? implode(', ', $v) : trim($v);
            }
        }
        // Auto-genera id/slug se non forniti
        if (empty($data['slug'])) {
            $data['slug'] = makeSlug($entityName);
        }
        if (empty($data['id'])) {
            $data['id'] = $data['slug'];
        }

        $stmt = $db->prepare("INSERT INTO survey_submissions
            (entity_type, submitter_name, submitter_email, submitter_phone, entity_name, data)
            VALUES (?,?,?,?,?,?)");
        $stmt->execute([$entityType, $subName, $subEmail, $subPhone, $entityName, json_encode($data, JSON_UNESCAPED_UNICODE)]);
        $submitted = true;
    }
}

$preType = $_GET['type'] ?? '';
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MetaBorghi — Scheda di iscrizione operatori</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
  body { background: #0f172a; }
  .section-card { background: #1e293b; border: 1px solid #334155; border-radius: 1rem; padding: 1.5rem; margin-bottom: 1.5rem; }
  .field-group { margin-bottom: 1.25rem; }
  label.field-label { display:block; font-size:0.8rem; color:#94a3b8; margin-bottom:0.375rem; font-weight:500; }
  label.field-label .req { color:#f87171; margin-left:2px; }
  input[type=text], input[type=email], input[type=tel], input[type=number], input[type=url],
  select, textarea {
    width:100%; background:#0f172a; color:white; border:1px solid #475569;
    border-radius:0.5rem; padding:0.5rem 0.75rem; font-size:0.875rem;
    transition: border-color .15s;
  }
  input:focus, select:focus, textarea:focus { outline:none; border-color:#00D084; }
  .hint { font-size:0.7rem; color:#64748b; margin-top:0.25rem; }
  .section-title { font-size:0.95rem; font-weight:700; color:white; margin-bottom:1rem;
    padding-bottom:0.5rem; border-bottom:1px solid #334155; display:flex; align-items:center; gap:0.5rem; }
  .type-btn { border:2px solid #334155; border-radius:0.75rem; padding:1rem;
    cursor:pointer; transition:all .15s; text-align:center; background:#1e293b; }
  .type-btn:hover { border-color:#00D084; background:#0f172a; }
  .type-btn.selected { border-color:#00D084; background:#052e16; }
  .type-btn .icon { font-size:1.75rem; margin-bottom:0.375rem; }
  .type-btn .name { font-size:0.8rem; font-weight:600; color:white; }
  .type-btn .desc { font-size:0.7rem; color:#94a3b8; margin-top:0.125rem; }
</style>
</head>
<body class="min-h-screen text-white py-10 px-4">
<div class="max-w-3xl mx-auto">

  <!-- Header -->
  <div class="text-center mb-8">
    <div class="inline-flex items-center gap-3 mb-4">
      <span class="text-4xl">🏔️</span>
      <div class="text-left">
        <h1 class="text-2xl font-bold text-white">MetaBorghi</h1>
        <p class="text-sm text-slate-400">Alta Irpinia · Campania</p>
      </div>
    </div>
    <h2 class="text-xl font-bold text-white mb-2">Scheda di iscrizione operatori</h2>
    <p class="text-slate-400 text-sm max-w-xl mx-auto">
      Compila questo modulo per inserire la tua attività nella piattaforma MetaBorghi.
      Non è richiesto nessun account: ti contatteremo noi per completare la scheda.
    </p>
  </div>

  <?php if ($submitted): ?>
  <!-- Grazie! -->
  <div class="section-card text-center py-12">
    <div class="text-5xl mb-4">✅</div>
    <h3 class="text-2xl font-bold text-emerald-400 mb-3">Grazie! Abbiamo ricevuto la tua scheda.</h3>
    <p class="text-slate-300 mb-2">Il nostro team la esaminerà e ti contatteremo entro 48 ore.</p>
    <p class="text-slate-400 text-sm">Hai domande? Scrivici a <a href="mailto:info@metaborghi.org" class="text-emerald-400 hover:underline">info@metaborghi.org</a></p>
    <a href="survey.php" class="inline-block mt-6 px-6 py-2.5 bg-slate-700 hover:bg-slate-600 text-white text-sm rounded-lg transition-colors">
      Invia un\'altra scheda
    </a>
  </div>

  <?php else: ?>

  <?php if ($error): ?>
  <div class="mb-6 px-4 py-3 rounded-lg bg-red-900/40 border border-red-700 text-red-300 text-sm">
    ⚠️ <?= htmlspecialchars($error) ?>
  </div>
  <?php endif; ?>

  <form method="POST" id="surveyForm">

    <!-- ── STEP 1: Tipo attività ─────────────────────────── -->
    <div class="section-card">
      <div class="section-title">1️⃣ Che tipo di attività vuoi iscrivere?</div>
      <input type="hidden" name="entity_type" id="entityTypeInput" value="<?= htmlspecialchars($preType) ?>" required>
      <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-2" id="typeButtons">
        <?php
        $types = [
          'azienda'       => ['🏢', 'Azienda / Produttore',    'Caseificio, agricoltura, vitivinicoltura, trasformazione alimentare'],
          'esperienza'    => ['🎭', 'Esperienza / Tour',        'Tour guidati, degustazioni, workshop, visite culturali'],
          'artigianato'   => ['🏺', 'Prodotto Artigianale',     'Ceramica, legno, ferro, tessuti, tradizioni locali'],
          'prodotto_food' => ['🧀', 'Prodotto Alimentare',      'Formaggi, salumi, conserve, olio, vino, dolci tipici'],
          'ristorazione'  => ['🍽️', 'Ristorante / Locale',      'Trattoria, osteria, pizzeria, agriturismo, enoteca'],
          'ospitalita'    => ['🏨', 'Struttura Ricettiva',      'Masseria, agriturismo, B&B, hotel, appartamento'],
        ];
        foreach ($types as $val => [$icon, $name, $desc]):
            $sel = ($preType === $val || ($_POST['entity_type'] ?? '') === $val) ? ' selected' : '';
        ?>
        <div class="type-btn<?= $sel ?>" onclick="selectType('<?= $val ?>')" data-type="<?= $val ?>">
          <div class="icon"><?= $icon ?></div>
          <div class="name"><?= $name ?></div>
          <div class="desc"><?= $desc ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- ── STEP 2: Referente ────────────────────────────── -->
    <div class="section-card">
      <div class="section-title">2️⃣ Chi compila questo modulo?</div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="field-group">
          <label class="field-label">Nome e cognome <span class="req">*</span></label>
          <input type="text" name="submitter_name" value="<?= htmlspecialchars($_POST['submitter_name'] ?? '') ?>"
            placeholder="Mario Rossi" required>
        </div>
        <div class="field-group">
          <label class="field-label">Email <span class="req">*</span></label>
          <input type="email" name="submitter_email" value="<?= htmlspecialchars($_POST['submitter_email'] ?? '') ?>"
            placeholder="mario@esempio.it" required>
          <p class="hint">Utilizzeremo questa email per contattarti.</p>
        </div>
        <div class="field-group">
          <label class="field-label">Telefono</label>
          <input type="tel" name="submitter_phone" value="<?= htmlspecialchars($_POST['submitter_phone'] ?? '') ?>"
            placeholder="+39 0827 123456">
        </div>
      </div>
    </div>

    <!-- ── SEZIONI SPECIFICHE PER TIPO ───────────────────── -->
    <!-- Mostrate/nascoste via JS -->

    <?php
    // Helper per campo input
    $f = function(string $name, string $label, string $type = 'text', string $placeholder = '', bool $required = false, string $hint = '', $value = null) {
        $v   = htmlspecialchars($value ?? ($_POST[$name] ?? ''));
        $req = $required ? '<span class="req">*</span>' : '';
        $reqAttr = $required ? ' required' : '';
        $hintHtml = $hint ? "<p class=\"hint\">$hint</p>" : '';
        return "<div class=\"field-group\">
          <label class=\"field-label\">$label $req</label>
          <input type=\"$type\" name=\"$name\" value=\"$v\" placeholder=\"$placeholder\"$reqAttr>
          $hintHtml
        </div>";
    };
    $fta = function(string $name, string $label, string $placeholder = '', bool $required = false, string $hint = '', int $rows = 3) {
        $v   = htmlspecialchars($_POST[$name] ?? '');
        $req = $required ? '<span class="req">*</span>' : '';
        $reqAttr = $required ? ' required' : '';
        $hintHtml = $hint ? "<p class=\"hint\">$hint</p>" : '';
        return "<div class=\"field-group\">
          <label class=\"field-label\">$label $req</label>
          <textarea name=\"$name\" rows=\"$rows\" placeholder=\"$placeholder\"$reqAttr>$v</textarea>
          $hintHtml
        </div>";
    };
    $fsel = function(string $name, string $label, array $options, bool $required = false, string $hint = '') {
        $current = $_POST[$name] ?? '';
        $req = $required ? '<span class="req">*</span>' : '';
        $hintHtml = $hint ? "<p class=\"hint\">$hint</p>" : '';
        $opts = "<option value=\"\">— Seleziona —</option>";
        foreach ($options as $val => $lbl) {
            $sel = $current === $val ? ' selected' : '';
            $opts .= "<option value=\"$val\"$sel>" . htmlspecialchars($lbl) . "</option>";
        }
        return "<div class=\"field-group\">
          <label class=\"field-label\">$label $req</label>
          <select name=\"$name\">$opts</select>
          $hintHtml
        </div>";
    };
    ?>

    <!-- ────────────────── AZIENDA ─────────────────────── -->
    <div class="entity-section" data-for="azienda" style="display:none">
      <div class="section-card">
        <div class="section-title">🏢 Informazioni sull'azienda</div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <?= $f('name', 'Nome dell\'azienda', 'text', 'Caseificio AltIrpinia', true) ?>
          <?= $f('legal_name', 'Ragione sociale (se diversa)', 'text', 'Caseificio AltIrpinia S.R.L.') ?>
          <?= $f('vat_number', 'Partita IVA', 'text', '01234567890') ?>
          <?= $fsel('type', 'Tipo di attività', ['PRODUTTORE_FOOD'=>'Produttore alimentare','MISTO'=>'Attività mista','AGRITURISMO'=>'Agriturismo'], true) ?>
          <?= $f('founding_year', 'Anno di fondazione', 'number', '1960') ?>
          <?= $f('employees_count', 'Numero dipendenti', 'number', '8') ?>
        </div>
      </div>
      <div class="section-card">
        <div class="section-title">📍 Sede e contatti</div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <?= $fsel('borough_id', 'Borgo di appartenenza', [
            'andretta'=>'Andretta','aquilonia'=>'Aquilonia','bagnoli-irpino'=>'Bagnoli Irpino',
            'bisaccia'=>'Bisaccia','cairano'=>'Cairano','calabritto'=>'Calabritto',
            'calitri'=>'Calitri','caposele'=>'Caposele','cassano-irpino'=>'Cassano Irpino',
            'castelfranci'=>'Castelfranci','conza-della-campania'=>'Conza della Campania',
            'guardia-dei-lombardi'=>'Guardia dei Lombardi','lacedonia'=>'Lacedonia',
            'lioni'=>'Lioni','montella'=>'Montella','monteverde'=>'Monteverde',
            'morra-de-sanctis'=>'Morra De Sanctis','nusco'=>'Nusco',
            'rocca-san-felice'=>'Rocca San Felice','sant-andrea-di-conza'=>'Sant\'Andrea di Conza',
            'sant-angelo-dei-lombardi'=>'Sant\'Angelo dei Lombardi',
            'senerchia'=>'Senerchia','teora'=>'Teora',
            'torella-dei-lombardi'=>'Torella dei Lombardi','villamaina'=>'Villamaina',
          ], true) ?>
          <?= $f('address_full', 'Indirizzo completo', 'text', 'Via Roma 12, 83046 Lacedonia AV') ?>
          <?= $f('contact_email', 'Email di contatto', 'email', 'info@azienda.it') ?>
          <?= $f('contact_phone', 'Telefono', 'tel', '+39 0827 123456') ?>
          <?= $f('website_url', 'Sito web', 'url', 'https://www.azienda.it') ?>
        </div>
      </div>
      <div class="section-card">
        <div class="section-title">✍️ Presentazione</div>
        <?= $f('tagline', 'Slogan / Motto (una frase)', 'text', 'Il sapore autentico dell\'Alta Irpinia') ?>
        <?= $fta('description_short', 'Descrizione breve', 'In massimo 2-3 righe, descrivi cosa fa la tua azienda.', true, 'Max 250 caratteri. Comparirà nelle card di ricerca.', 2) ?>
        <?= $fta('description_long', 'La tua storia', 'Raccontaci la storia dell\'azienda, la missione, i valori, cosa vi distingue dagli altri...', false, '', 5) ?>
        <?= $f('founder_name', 'Nome del fondatore', 'text', 'Mario Rossi') ?>
        <?= $fta('founder_quote', 'Una citazione del fondatore', 'Una frase che rappresenta la vostra filosofia...', false, '', 2) ?>
      </div>
      <div class="section-card">
        <div class="section-title">📱 Social media</div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <?= $f('social_instagram', 'Instagram', 'text', '@nome_azienda o URL completo') ?>
          <?= $f('social_facebook', 'Facebook', 'text', 'URL pagina Facebook') ?>
          <?= $f('social_linkedin', 'LinkedIn', 'url', 'https://linkedin.com/company/...') ?>
        </div>
      </div>
    </div>

    <!-- ────────────────── ESPERIENZA ──────────────────── -->
    <div class="entity-section" data-for="esperienza" style="display:none">
      <div class="section-card">
        <div class="section-title">🎭 Informazioni sull'esperienza</div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <?= $f('name', 'Nome dell\'esperienza', 'text', 'Tour del Caseificio — i segreti del caciocavallo', true) ?>
          <?= $fsel('category', 'Categoria', [
            'GASTRONOMIA'=>'🍷 Gastronomia & Cibo','CULTURA'=>'🏛️ Cultura & Storia',
            'NATURA'=>'🌿 Natura & Trekking','ARTIGIANATO'=>'🏺 Artigianato',
            'BENESSERE'=>'🧘 Benessere & Relax','AVVENTURA'=>'🧗 Avventura & Sport',
          ], true) ?>
          <?= $f('provider_id', 'ID azienda organizzatrice (se hai già una scheda)', 'text', 'es: caseificio-altirpinia', false, 'Lascia vuoto se non lo conosci, lo colleghiamo noi.') ?>
          <?= $fsel('borough_id', 'Borgo dove si svolge', [
            'andretta'=>'Andretta','aquilonia'=>'Aquilonia','bagnoli-irpino'=>'Bagnoli Irpino',
            'bisaccia'=>'Bisaccia','cairano'=>'Cairano','calabritto'=>'Calabritto',
            'calitri'=>'Calitri','caposele'=>'Caposele','cassano-irpino'=>'Cassano Irpino',
            'castelfranci'=>'Castelfranci','conza-della-campania'=>'Conza della Campania',
            'guardia-dei-lombardi'=>'Guardia dei Lombardi','lacedonia'=>'Lacedonia',
            'lioni'=>'Lioni','montella'=>'Montella','monteverde'=>'Monteverde',
            'morra-de-sanctis'=>'Morra De Sanctis','nusco'=>'Nusco',
            'rocca-san-felice'=>'Rocca San Felice','sant-andrea-di-conza'=>'Sant\'Andrea di Conza',
            'sant-angelo-dei-lombardi'=>'Sant\'Angelo dei Lombardi',
            'senerchia'=>'Senerchia','teora'=>'Teora',
            'torella-dei-lombardi'=>'Torella dei Lombardi','villamaina'=>'Villamaina',
          ], true) ?>
          <?= $f('duration_minutes', 'Durata (in minuti)', 'number', '120', true, 'Es: 60 = 1 ora, 120 = 2 ore, 480 = giornata intera') ?>
          <?= $f('price_per_person', 'Prezzo per persona (€)', 'number', '25.00', true, 'Inserire il prezzo in euro, es: 25.00') ?>
          <?= $f('max_participants', 'Massimo partecipanti', 'number', '15') ?>
          <?= $f('min_participants', 'Minimo partecipanti', 'number', '2') ?>
          <?= $fsel('difficulty_level', 'Difficoltà', ['FACILE'=>'Facile','MEDIO'=>'Media','DIFFICILE'=>'Difficile']) ?>
        </div>
      </div>
      <div class="section-card">
        <div class="section-title">✍️ Descrizione</div>
        <?= $f('tagline', 'Slogan dell\'esperienza', 'text', 'Scopri i segreti del caciocavallo irpino') ?>
        <?= $fta('description_short', 'Descrizione breve', 'In 2-3 righe, cosa vivrà il partecipante?', true, 'Comparirà nelle card di ricerca.', 2) ?>
        <?= $fta('description_long', 'Descrizione dettagliata', 'Come si svolge l\'esperienza? Cosa si vede, si fa, si mangia? Qual è il programma?', false, '', 5) ?>
        <?= $fta('cancellation_policy', 'Politica di cancellazione', 'Es: Cancellazione gratuita fino a 24h prima', false, '', 2) ?>
        <?= $fta('accessibility_info', 'Accessibilità', 'Es: Adatta a persone con disabilità motoria, bambini dai 6 anni...', false, '', 2) ?>
      </div>
    </div>

    <!-- ────────────────── ARTIGIANATO ─────────────────── -->
    <div class="entity-section" data-for="artigianato" style="display:none">
      <div class="section-card">
        <div class="section-title">🏺 Informazioni sul prodotto artigianale</div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <?= $f('name', 'Nome del prodotto', 'text', 'Cestino in Vimini Tradizionale', true) ?>
          <?= $f('artisan_id', 'ID azienda/artigiano (se già iscritto)', 'text', 'es: artigiano-rossi', false, 'Lascia vuoto se non lo conosci.') ?>
          <?= $fsel('borough_id', 'Borgo di produzione', [
            'andretta'=>'Andretta','aquilonia'=>'Aquilonia','bagnoli-irpino'=>'Bagnoli Irpino',
            'bisaccia'=>'Bisaccia','cairano'=>'Cairano','calabritto'=>'Calabritto',
            'calitri'=>'Calitri','caposele'=>'Caposele','cassano-irpino'=>'Cassano Irpino',
            'castelfranci'=>'Castelfranci','conza-della-campania'=>'Conza della Campania',
            'guardia-dei-lombardi'=>'Guardia dei Lombardi','lacedonia'=>'Lacedonia',
            'lioni'=>'Lioni','montella'=>'Montella','monteverde'=>'Monteverde',
            'morra-de-sanctis'=>'Morra De Sanctis','nusco'=>'Nusco',
            'rocca-san-felice'=>'Rocca San Felice','sant-andrea-di-conza'=>'Sant\'Andrea di Conza',
            'sant-angelo-dei-lombardi'=>'Sant\'Angelo dei Lombardi',
            'senerchia'=>'Senerchia','teora'=>'Teora',
            'torella-dei-lombardi'=>'Torella dei Lombardi','villamaina'=>'Villamaina',
          ], true) ?>
          <?= $f('price', 'Prezzo (€)', 'number', '45.00', false, 'Prezzo base del prodotto in euro.') ?>
          <?= $f('dimensions', 'Dimensioni', 'text', 'es: 30×20×15 cm') ?>
          <?= $f('weight_grams', 'Peso (grammi)', 'number', '350') ?>
          <?= $f('lead_time_days', 'Tempo di lavorazione (giorni)', 'number', '7', false, 'Giorni necessari per realizzare un ordine su misura.') ?>
          <div class="field-group sm:col-span-2 flex gap-6">
            <label class="flex items-center gap-2 text-sm text-slate-300 cursor-pointer">
              <input type="checkbox" name="is_custom_order_available" value="1" <?= !empty($_POST['is_custom_order_available']) ? 'checked' : '' ?> class="rounded w-4 h-4">
              Accetto ordini personalizzati
            </label>
            <label class="flex items-center gap-2 text-sm text-slate-300 cursor-pointer">
              <input type="checkbox" name="is_unique_piece" value="1" <?= !empty($_POST['is_unique_piece']) ? 'checked' : '' ?> class="rounded w-4 h-4">
              Pezzo unico (non in serie)
            </label>
          </div>
        </div>
      </div>
      <div class="section-card">
        <div class="section-title">✍️ Descrizione</div>
        <?= $fta('description_short', 'Descrizione breve del prodotto', 'In 2-3 righe, cos\'è questo prodotto?', true, '', 2) ?>
        <?= $fta('description_long', 'Storia e dettagli', 'Origine della lavorazione, tradizione, materiali utilizzati, cosa rende unico questo prodotto...', false, '', 4) ?>
        <?= $fta('technique_description', 'Tecnica artigianale', 'Descrivi la tecnica di lavorazione, gli strumenti, i passaggi principali.', false, '', 3) ?>
      </div>
    </div>

    <!-- ────────────────── PRODOTTO FOOD ───────────────── -->
    <div class="entity-section" data-for="prodotto_food" style="display:none">
      <div class="section-card">
        <div class="section-title">🧀 Informazioni sul prodotto alimentare</div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <?= $f('name', 'Nome del prodotto', 'text', 'Caciocavallo Irpino d\'Alpeggio', true) ?>
          <?= $fsel('category', 'Categoria', [
            'FORMAGGI'=>'🧀 Formaggi','SALUMI'=>'🥩 Salumi & Insaccati',
            'CONSERVE'=>'🫙 Conserve & Sottoli','DOLCI'=>'🍰 Dolci & Pasticceria',
            'OLIO'=>'🫒 Olio extravergine','VINO'=>'🍷 Vino & Liquori',
            'CEREALI'=>'🌾 Cereali & Legumi','ALTRO'=>'📦 Altro',
          ], true) ?>
          <?= $f('producer_id', 'ID azienda produttrice (se già iscritta)', 'text', 'es: caseificio-altirpinia', false, 'Lascia vuoto se non lo conosci.') ?>
          <?= $fsel('borough_id', 'Borgo di produzione', [
            'andretta'=>'Andretta','aquilonia'=>'Aquilonia','bagnoli-irpino'=>'Bagnoli Irpino',
            'bisaccia'=>'Bisaccia','cairano'=>'Cairano','calabritto'=>'Calabritto',
            'calitri'=>'Calitri','caposele'=>'Caposele','cassano-irpino'=>'Cassano Irpino',
            'castelfranci'=>'Castelfranci','conza-della-campania'=>'Conza della Campania',
            'guardia-dei-lombardi'=>'Guardia dei Lombardi','lacedonia'=>'Lacedonia',
            'lioni'=>'Lioni','montella'=>'Montella','monteverde'=>'Monteverde',
            'morra-de-sanctis'=>'Morra De Sanctis','nusco'=>'Nusco',
            'rocca-san-felice'=>'Rocca San Felice','sant-andrea-di-conza'=>'Sant\'Andrea di Conza',
            'sant-angelo-dei-lombardi'=>'Sant\'Angelo dei Lombardi',
            'senerchia'=>'Senerchia','teora'=>'Teora',
            'torella-dei-lombardi'=>'Torella dei Lombardi','villamaina'=>'Villamaina',
          ], true) ?>
          <?= $f('price', 'Prezzo (€)', 'number', '18.50') ?>
          <?= $f('unit', 'Unità di vendita', 'text', 'es: pezzo da 500g, bottiglia 750ml') ?>
          <?= $f('weight_grams', 'Peso (grammi)', 'number', '500') ?>
          <?= $f('shelf_life_days', 'Scadenza (giorni dalla produzione)', 'number', '180') ?>
          <?= $f('origin_protected', 'Denominazione/certificazione', 'text', 'es: DOP, IGP, Presidio Slow Food', false, 'Lascia vuoto se nessuna.') ?>
          <label class="flex items-center gap-2 text-sm text-slate-300 cursor-pointer mt-2">
            <input type="checkbox" name="is_shippable" value="1" <?= !empty($_POST['is_shippable']) ? 'checked' : '' ?> class="rounded w-4 h-4">
            Il prodotto può essere spedito
          </label>
        </div>
      </div>
      <div class="section-card">
        <div class="section-title">✍️ Descrizione e ingredienti</div>
        <?= $f('tagline', 'Slogan del prodotto', 'text', 'Il sapore autentico della montagna irpina') ?>
        <?= $fta('description_short', 'Descrizione breve', 'In 2-3 righe, cos\'è questo prodotto?', true, '', 2) ?>
        <?= $fta('description_long', 'Descrizione dettagliata', 'Storia, metodo di produzione, stagionatura, caratteristiche organolettiche...', false, '', 4) ?>
        <?= $fta('ingredients', 'Ingredienti', 'Elenca gli ingredienti come da etichetta.', false, '', 2) ?>
        <?= $fta('allergens', 'Allergeni', 'Es: Latte, Glutine. Lascia vuoto se nessuno.', false, '', 1) ?>
        <?= $fta('storage_instructions', 'Istruzioni di conservazione', 'Es: Conservare in luogo fresco e asciutto, max 10°C.', false, '', 2) ?>
        <?= $fta('pairing_suggestions', 'Abbinamenti consigliati', 'Es: Vino Aglianico, miele di castagno, pere Williams.', false, '', 2) ?>
      </div>
    </div>

    <!-- ────────────────── RISTORAZIONE ────────────────── -->
    <div class="entity-section" data-for="ristorazione" style="display:none">
      <div class="section-card">
        <div class="section-title">🍽️ Informazioni sul locale</div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <?= $f('name', 'Nome del locale', 'text', 'Trattoria da Zi\' Nicola', true) ?>
          <?= $fsel('type', 'Tipo di locale', [
            'RISTORANTE'=>'Ristorante','TRATTORIA'=>'Trattoria','PIZZERIA'=>'Pizzeria',
            'AGRITURISMO'=>'Agriturismo','ENOTECA'=>'Enoteca / Wine bar',
            'BAR'=>'Bar / Caffè','OSTERIA'=>'Osteria',
          ], true) ?>
          <?= $fsel('borough_id', 'Borgo', [
            'andretta'=>'Andretta','aquilonia'=>'Aquilonia','bagnoli-irpino'=>'Bagnoli Irpino',
            'bisaccia'=>'Bisaccia','cairano'=>'Cairano','calabritto'=>'Calabritto',
            'calitri'=>'Calitri','caposele'=>'Caposele','cassano-irpino'=>'Cassano Irpino',
            'castelfranci'=>'Castelfranci','conza-della-campania'=>'Conza della Campania',
            'guardia-dei-lombardi'=>'Guardia dei Lombardi','lacedonia'=>'Lacedonia',
            'lioni'=>'Lioni','montella'=>'Montella','monteverde'=>'Monteverde',
            'morra-de-sanctis'=>'Morra De Sanctis','nusco'=>'Nusco',
            'rocca-san-felice'=>'Rocca San Felice','sant-andrea-di-conza'=>'Sant\'Andrea di Conza',
            'sant-angelo-dei-lombardi'=>'Sant\'Angelo dei Lombardi',
            'senerchia'=>'Senerchia','teora'=>'Teora',
            'torella-dei-lombardi'=>'Torella dei Lombardi','villamaina'=>'Villamaina',
          ], true) ?>
          <?= $f('address_full', 'Indirizzo completo', 'text', 'Via Municipio 5, 83046 Lacedonia AV') ?>
          <?= $f('cuisine_type', 'Tipo di cucina', 'text', 'Cucina irpina tradizionale') ?>
          <?= $fsel('price_range', 'Fascia di prezzo', ['BUDGET'=>'€ Budget (meno di 15€)','MEDIO'=>'€€ Medio (15–30€)','ALTO'=>'€€€ Alto (30–60€)','GOURMET'=>'€€€€ Gourmet (oltre 60€)']) ?>
          <?= $f('seats_indoor', 'Posti al coperto', 'number', '40') ?>
          <?= $f('seats_outdoor', 'Posti all\'aperto', 'number', '20') ?>
          <?= $f('opening_hours', 'Orari di apertura', 'text', 'Mar-Dom 12:30-15:00 e 19:30-22:30') ?>
          <?= $f('closing_day', 'Giorno di chiusura', 'text', 'Lunedì') ?>
        </div>
      </div>
      <div class="section-card">
        <div class="section-title">✍️ Presentazione</div>
        <?= $f('tagline', 'Slogan del locale', 'text', 'I sapori autentici dell\'Alta Irpinia') ?>
        <?= $fta('description_short', 'Descrizione breve', 'In 2-3 righe, cos\'è questo posto?', true, '', 2) ?>
        <?= $fta('description_long', 'La storia del locale', 'Da quando siete aperti, la filosofia, l\'ambiente, cosa vi rende unici...', false, '', 4) ?>
        <?= $fta('specialties', 'Piatti tipici/specialità', 'Es: Lagane e ceci, Fusilli al ragù di agnello, Agnello alla brace', false, 'Separati da virgola.', 2) ?>
        <?= $fta('menu_highlights', 'Punto di forza del menu', 'Cosa non si deve assolutamente perdere?', false, '', 2) ?>
        <?= $f('founder_name', 'Titolare / Chef', 'text', 'Nicola Marzano') ?>
      </div>
      <div class="section-card">
        <div class="section-title">📞 Contatti e prenotazioni</div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <?= $f('contact_email', 'Email', 'email', 'info@locale.it') ?>
          <?= $f('contact_phone', 'Telefono', 'tel', '+39 0827 123456') ?>
          <?= $f('website_url', 'Sito web', 'url', 'https://www.locale.it') ?>
          <?= $f('social_instagram', 'Instagram', 'text', '@nome_locale') ?>
          <?= $f('booking_url', 'Link prenotazione online', 'url', 'https://booking.locale.it', false, 'TheFork, Tripadvisor, ecc. — opzionale.') ?>
        </div>
      </div>
    </div>

    <!-- ────────────────── OSPITALITÀ ──────────────────── -->
    <div class="entity-section" data-for="ospitalita" style="display:none">
      <div class="section-card">
        <div class="section-title">🏨 Informazioni sulla struttura</div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <?= $f('name', 'Nome della struttura', 'text', 'Masseria del Vento', true) ?>
          <?= $fsel('type', 'Tipo di struttura', [
            'MASSERIA'=>'Masseria','AGRITURISMO'=>'Agriturismo',
            'BED_AND_BREAKFAST'=>'Bed & Breakfast','HOTEL'=>'Hotel',
            'HOSTEL'=>'Hostel / Ostello','APPARTAMENTO'=>'Appartamento / Casa vacanze',
          ], true) ?>
          <?= $fsel('borough_id', 'Borgo', [
            'andretta'=>'Andretta','aquilonia'=>'Aquilonia','bagnoli-irpino'=>'Bagnoli Irpino',
            'bisaccia'=>'Bisaccia','cairano'=>'Cairano','calabritto'=>'Calabritto',
            'calitri'=>'Calitri','caposele'=>'Caposele','cassano-irpino'=>'Cassano Irpino',
            'castelfranci'=>'Castelfranci','conza-della-campania'=>'Conza della Campania',
            'guardia-dei-lombardi'=>'Guardia dei Lombardi','lacedonia'=>'Lacedonia',
            'lioni'=>'Lioni','montella'=>'Montella','monteverde'=>'Monteverde',
            'morra-de-sanctis'=>'Morra De Sanctis','nusco'=>'Nusco',
            'rocca-san-felice'=>'Rocca San Felice','sant-andrea-di-conza'=>'Sant\'Andrea di Conza',
            'sant-angelo-dei-lombardi'=>'Sant\'Angelo dei Lombardi',
            'senerchia'=>'Senerchia','teora'=>'Teora',
            'torella-dei-lombardi'=>'Torella dei Lombardi','villamaina'=>'Villamaina',
          ], true) ?>
          <?= $f('address_full', 'Indirizzo completo', 'text', 'Contrada Vento, 83046 Lacedonia AV') ?>
          <?= $f('rooms_count', 'Numero di camere/unità', 'number', '6') ?>
          <?= $f('max_guests', 'Ospiti massimi', 'number', '12') ?>
          <?= $f('price_per_night_from', 'Prezzo per notte da (€)', 'number', '80.00') ?>
          <?= $f('stars_or_category', 'Stelle / Categoria', 'text', 'es: 3 stelle, 4 spighe, ecc.') ?>
          <?= $f('check_in_time', 'Check-in (ora)', 'text', '15:00') ?>
          <?= $f('check_out_time', 'Check-out (ora)', 'text', '11:00') ?>
          <?= $f('min_stay_nights', 'Soggiorno minimo (notti)', 'number', '2') ?>
        </div>
      </div>
      <div class="section-card">
        <div class="section-title">✍️ Presentazione</div>
        <?= $f('tagline', 'Slogan della struttura', 'text', 'Dove il vento incontra la storia') ?>
        <?= $fta('description_short', 'Descrizione breve', 'In 2-3 righe, cos\'è questa struttura?', true, '', 2) ?>
        <?= $fta('description_long', 'Descrizione completa', 'Storia della struttura, atmosfera, paesaggio, cosa offre...', false, '', 4) ?>
        <?= $fta('amenities', 'Servizi offerti', 'Es: WiFi, Piscina, Ristorante, Parcheggio, Cucina attrezzata, Animali ammessi', false, 'Separati da virgola.', 2) ?>
      </div>
      <div class="section-card">
        <div class="section-title">📞 Contatti e prenotazioni</div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <?= $f('contact_email', 'Email', 'email', 'info@struttura.it') ?>
          <?= $f('contact_phone', 'Telefono', 'tel', '+39 0827 123456') ?>
          <?= $f('website_url', 'Sito web', 'url', 'https://www.struttura.it') ?>
          <?= $f('social_instagram', 'Instagram', 'text', '@nome_struttura') ?>
          <?= $f('booking_url', 'Link prenotazione online', 'url', 'https://booking.struttura.it', false, 'Booking.com, Airbnb, ecc. — opzionale.') ?>
          <?= $f('booking_email', 'Email dedicata prenotazioni', 'email', 'prenotazioni@struttura.it', false, 'Se diversa dall\'email principale.') ?>
        </div>
      </div>
    </div>

    <!-- ── Note aggiuntive (sempre visibile) ─────────────── -->
    <div class="section-card" id="noteSection" style="display:none">
      <div class="section-title">💬 Note aggiuntive</div>
      <?= $fta('note_aggiuntive', 'Vuoi aggiungere qualcosa?', 'Informazioni che non trovi nei campi precedenti, richieste particolari, domande per il team MetaBorghi...', false, '', 3) ?>
    </div>

    <!-- ── Submit ─────────────────────────────────────────── -->
    <div class="section-card" id="submitSection" style="display:none">
      <div class="flex items-start gap-3 mb-5">
        <input type="checkbox" id="privacyCheck" name="privacy_ok" value="1" required class="rounded w-5 h-5 mt-0.5 shrink-0">
        <label for="privacyCheck" class="text-sm text-slate-400 cursor-pointer">
          Accetto la <a href="#" class="text-emerald-400 hover:underline">Privacy Policy</a> e autorizzo MetaBorghi / InnTour S.R.L. al trattamento dei miei dati per l'inserimento nella piattaforma.
        </label>
      </div>
      <button type="submit"
        class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl transition-colors text-base">
        Invia la scheda 🚀
      </button>
      <p class="text-xs text-slate-500 mt-3 text-center">
        Ti contatteremo entro 48 ore all'email indicata per confermare l'inserimento.
      </p>
    </div>

  </form>
  <?php endif; ?>

  <div class="text-center mt-8 text-xs text-slate-600">
    MetaBorghi · InnTour S.R.L. · <a href="mailto:info@metaborghi.org" class="hover:text-slate-400">info@metaborghi.org</a>
  </div>

</div>

<script>
function selectType(type) {
  // Aggiorna input hidden
  document.getElementById('entityTypeInput').value = type;
  // Toggle stile bottoni
  document.querySelectorAll('.type-btn').forEach(b => b.classList.toggle('selected', b.dataset.type === type));
  // Mostra/nasconde sezioni
  document.querySelectorAll('.entity-section').forEach(s => {
    s.style.display = (s.dataset.for === type) ? 'block' : 'none';
  });
  document.getElementById('noteSection').style.display    = 'block';
  document.getElementById('submitSection').style.display  = 'block';
  // Scroll alla prima sezione del tipo
  const target = document.querySelector(`.entity-section[data-for="${type}"]`);
  if (target) target.scrollIntoView({ behavior:'smooth', block:'start' });
}

// Ripristina stato se ritorno POST con errore
const preType = document.getElementById('entityTypeInput').value;
if (preType) selectType(preType);
</script>
</body>
</html>
