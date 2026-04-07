<?php
require_once __DIR__ . '/../config/db.php';
requireAdminSession();
$db = getDB();

$pageTitle = 'Import Bulk CSV';

// ── Mappa completa entità → colonne DB ─────────────────────────────────────
// columns  = tutte le colonne importabili (prima riga del CSV template)
// example  = riga di esempio (seconda riga del CSV template)
// required = colonne obbligatorie (validazione all'import)
// help     = descrizione colonne (mostrata nell'UI)
$entityMap = [

    'borghi' => [
        'table'   => 'boroughs',
        'label'   => '🏔️ Borghi',
        'pk'      => 'id',
        'required'=> ['id','slug','name','province'],
        'columns' => [
            'id','slug','name','province','region','population',
            'altitude_meters','area_km2','lat','lng',
            'description','companies_count','hero_image_index','hero_image_alt','cover_image',
        ],
        'example' => [
            'lacedonia','lacedonia','Lacedonia','AV','Campania','2600',
            '712','27.83','41.0395000','15.4567000',
            'Antico borgo medievale dell\'Alta Irpinia, custode di storia e tradizioni.','5','0','Vista panoramica sul Vulture','',
        ],
        'help' => [
            'id'              => 'Identificativo unico kebab-case (es: nome-borgo). Uguale a slug.',
            'slug'            => 'URL-friendly, uguale a id.',
            'name'            => 'Nome completo del borgo.',
            'province'        => 'Sigla provincia (es: AV).',
            'region'          => 'Regione (default: Campania).',
            'population'      => 'Numero di abitanti (solo cifre).',
            'altitude_meters' => 'Altitudine in metri s.l.m.',
            'area_km2'        => 'Superficie in km² (es: 27.83).',
            'lat'             => 'Latitudine decimale (es: 41.0395000).',
            'lng'             => 'Longitudine decimale (es: 15.4567000).',
            'description'     => 'Descrizione narrativa del borgo.',
            'cover_image'     => 'URL immagine di copertina (lasciare vuoto se da caricare dopo).',
        ],
    ],

    'aziende' => [
        'table'   => 'companies',
        'label'   => '🏢 Aziende',
        'pk'      => 'id',
        'required'=> ['id','slug','name','type','borough_id'],
        'columns' => [
            'id','slug','name','legal_name','vat_number','type',
            'tagline','description_short','description_long',
            'founding_year','employees_count',
            'borough_id','address_full','lat','lng',
            'contact_email','contact_phone','website_url',
            'social_instagram','social_facebook','social_linkedin',
            'founder_name','founder_quote',
        ],
        'example' => [
            'caseificio-altirpinia','caseificio-altirpinia','Caseificio AltIrpinia',
            'Caseificio AltIrpinia S.R.L.','01234567890','PRODUTTORE_FOOD',
            'Il miglior caciocavallo dell\'Alta Irpinia',
            'Produciamo formaggi artigianali dal 1960 con latte fresco locale.',
            'La nostra storia nasce sulle montagne dell\'Irpinia...','1960','8',
            'lacedonia','Via Roma 12, 83046 Lacedonia AV','41.0395000','15.4567000',
            'info@caseificio.it','+39 0827 123456','https://www.caseificio.it',
            '@caseificio_altirpinia','https://facebook.com/caseificio','',
            'Mario Rossi','Ogni forma racconta la nostra terra.',
        ],
        'help' => [
            'id'                => 'Identificativo unico kebab-case (es: nome-azienda).',
            'type'              => 'PRODUTTORE_FOOD | MISTO | AGRITURISMO',
            'borough_id'        => 'ID del borgo di appartenenza (es: lacedonia).',
            'description_short' => 'Breve descrizione, max 250 caratteri.',
            'description_long'  => 'Descrizione estesa, storia, valori.',
            'founding_year'     => 'Anno di fondazione (solo cifre, es: 1960).',
            'employees_count'   => 'Numero di dipendenti (solo cifre).',
            'social_instagram'  => 'Handle o URL profilo Instagram.',
        ],
    ],

    'esperienze' => [
        'table'   => 'experiences',
        'label'   => '🎭 Esperienze',
        'pk'      => 'id',
        'required'=> ['id','slug','title','category','borough_id','duration_minutes','price_per_person'],
        'columns' => [
            'id','slug','title','tagline','description_short','description_long',
            'category','provider_id','borough_id','lat','lng',
            'duration_minutes','max_participants','min_participants',
            'price_per_person','difficulty_level',
            'cancellation_policy','accessibility_info','cover_image',
        ],
        'example' => [
            'tour-caseificio-lacedonia','tour-caseificio-lacedonia',
            'Tour del Caseificio — i segreti del caciocavallo',
            'Scopri come nasce il caciocavallo irpino d\'alpeggio',
            'Visita guidata al caseificio con degustazione finale.',
            'Una esperienza immersiva nella tradizione casearia...','GASTRONOMIA',
            'caseificio-altirpinia','lacedonia','41.0395000','15.4567000',
            '120','15','2','25.00','FACILE',
            'Cancellazione gratuita fino a 24h prima dell\'evento.',
            'Accessibile a tutti, incluse persone con mobilità ridotta.','',
        ],
        'help' => [
            'category'         => 'GASTRONOMIA | CULTURA | NATURA | ARTIGIANATO | BENESSERE | AVVENTURA',
            'provider_id'      => 'ID dell\'azienda organizzatrice (es: caseificio-altirpinia).',
            'duration_minutes' => 'Durata in minuti (es: 120 = 2 ore).',
            'price_per_person' => 'Prezzo per persona in euro con decimali (es: 25.00).',
            'difficulty_level' => 'FACILE | MEDIO | DIFFICILE',
            'max_participants' => 'Numero massimo di partecipanti.',
        ],
    ],

    'artigianato' => [
        'table'   => 'craft_products',
        'label'   => '🏺 Artigianato',
        'pk'      => 'id',
        'required'=> ['id','slug','name','artisan_id','borough_id'],
        'columns' => [
            'id','slug','name','description_short','description_long',
            'price','artisan_id','borough_id',
            'technique_description','dimensions','weight_grams',
            'lead_time_days','is_custom_order_available',
            'is_unique_piece','production_series_qty','stock_qty','cover_image',
        ],
        'example' => [
            'cestino-vimini-lacedonia','cestino-vimini-lacedonia',
            'Cestino in Vimini Tradizionale',
            'Cestino artigianale intrecciato a mano con vimini locali.',
            'Lavorazione tramandata di generazione in generazione...','45.00',
            'artigiano-rossi','lacedonia',
            'Intreccio manuale su telaio in legno di castagno.','30x20x15 cm','350',
            '7','1','0','','10','',
        ],
        'help' => [
            'artisan_id'                => 'ID dell\'azienda/artigiano produttore.',
            'price'                     => 'Prezzo in euro con decimali (es: 45.00).',
            'technique_description'     => 'Descrizione della tecnica artigianale utilizzata.',
            'dimensions'                => 'Dimensioni (es: 30x20x15 cm).',
            'weight_grams'              => 'Peso in grammi.',
            'lead_time_days'            => 'Giorni necessari per ordine su misura.',
            'is_custom_order_available' => '0 = no personalizzazione, 1 = sì.',
            'is_unique_piece'           => '0 = produzione in serie, 1 = pezzo unico.',
        ],
    ],

    'prodotti' => [
        'table'   => 'food_products',
        'label'   => '🧀 Prodotti Food',
        'pk'      => 'id',
        'required'=> ['id','slug','name','category','borough_id'],
        'columns' => [
            'id','slug','name','producer_id','borough_id',
            'category','description_short','description_long','tagline',
            'price','unit','weight_grams','shelf_life_days',
            'storage_instructions','origin_protected','allergens','ingredients',
            'pairing_suggestions','stock_qty','min_order_qty','is_shippable','cover_image',
        ],
        'example' => [
            'caciocavallo-irpino-alpeggio','caciocavallo-irpino-alpeggio',
            'Caciocavallo Irpino d\'Alpeggio','caseificio-altirpinia','lacedonia',
            'FORMAGGI','Formaggio a pasta filata stagionato 6 mesi, sapore intenso.',
            'Prodotto con latte intero fresco di vacca podolica allevata allo stato brado...',
            'Il sapore autentico della montagna irpina','18.50','pezzo ca. 500g','500',
            '180','Conservare in luogo fresco e asciutto, max 10°C.',
            'Presidio Slow Food','Latte, sale, caglio (origine animale).',
            'Latte vaccino intero (100%)','Vino Aglianico, miele di castagno','50','1','1','',
        ],
        'help' => [
            'category'          => 'FORMAGGI | SALUMI | CONSERVE | DOLCI | OLIO | VINO | CEREALI | ALTRO',
            'producer_id'       => 'ID dell\'azienda produttrice.',
            'price'             => 'Prezzo in euro con decimali (es: 18.50).',
            'unit'              => 'Unità di vendita (es: pezzo ca. 500g, bottiglia 750ml).',
            'shelf_life_days'   => 'Scadenza in giorni dalla produzione.',
            'origin_protected'  => 'Denominazione protetta (DOP, IGP, Presidio SF, ecc.) o vuoto.',
            'allergens'         => 'Allergeni dichiarati per etichettatura.',
            'is_shippable'      => '0 = solo ritiro, 1 = spedibile.',
        ],
    ],

    'ospitalita' => [
        'table'   => 'accommodations',
        'label'   => '🏨 Ospitalità',
        'pk'      => 'id',
        'required'=> ['id','slug','name','type','borough_id'],
        'columns' => [
            'id','slug','name','type','provider_id','borough_id',
            'address_full','lat','lng','description_short','description_long','tagline',
            'rooms_count','max_guests','price_per_night_from',
            'stars_or_category','check_in_time','check_out_time','min_stay_nights',
            'amenities','booking_email','booking_phone','booking_url',
            'contact_email','contact_phone','website_url','social_instagram','cover_image',
        ],
        'example' => [
            'masseria-del-vento','masseria-del-vento','Masseria del Vento',
            'MASSERIA','azienda-del-vento','lacedonia',
            'Contrada Vento, 83046 Lacedonia AV','41.0412000','15.4512000',
            'Masseria storica immersa nei boschi irpini con vista mozzafiato.',
            'La Masseria del Vento sorge su un colle panoramico...','Dove il vento incontra la storia',
            '6','12','80.00','3 stelle','15:00','11:00','2',
            'WiFi, Piscina stagionale, Ristorante, Parcheggio gratuito',
            'prenotazioni@masseria.it','+39 0827 123456','https://booking.masseria.it',
            'info@masseria.it','+39 0827 123456','https://www.masseria.it','@masseria_del_vento','',
        ],
        'help' => [
            'type'               => 'HOTEL | AGRITURISMO | MASSERIA | BED_AND_BREAKFAST | HOSTEL | APPARTAMENTO',
            'provider_id'        => 'ID azienda proprietaria/gestrice (opzionale).',
            'check_in_time'      => 'Orario check-in (es: 15:00).',
            'check_out_time'     => 'Orario check-out (es: 11:00).',
            'min_stay_nights'    => 'Soggiorno minimo in notti (es: 2).',
            'price_per_night_from' => 'Prezzo minimo per notte in euro.',
            'amenities'          => 'Servizi separati da virgola (es: WiFi, Piscina, Ristorante).',
        ],
    ],

    'ristorazione' => [
        'table'   => 'restaurants',
        'label'   => '🍽️ Ristorazione',
        'pk'      => 'id',
        'required'=> ['id','slug','name','type','borough_id'],
        'columns' => [
            'id','slug','name','type','borough_id',
            'address_full','lat','lng','description_short','description_long','tagline',
            'cuisine_type','price_range','seats_indoor','seats_outdoor',
            'opening_hours','closing_day','specialties','menu_highlights',
            'contact_email','contact_phone','website_url',
            'social_instagram','social_facebook','booking_url',
            'accepts_groups','max_group_size','founder_name','cover_image',
        ],
        'example' => [
            'trattoria-zi-nicola','trattoria-zi-nicola','Trattoria da Zi\' Nicola',
            'TRATTORIA','lacedonia',
            'Via Municipio 5, 83046 Lacedonia AV','41.0398000','15.4571000',
            'Cucina irpina autentica dal 1975, specialità lagane e ceci.',
            'La trattoria più amata del borgo, in attività da tre generazioni...',
            'I sapori autentici dell\'Alta Irpinia','Cucina irpina tradizionale','MEDIO',
            '40','20','Mar-Dom 12:30-15:00 e 19:30-22:30','Lunedì',
            'Lagane e ceci, Fusilli al ragù, Agnello alla brace',
            'Pasta fresca fatta a mano ogni giorno',
            'info@trattoria.it','+39 0827 987654','https://www.trattoria.it',
            '@trattoria_nicola','','https://booking.trattoria.it',
            '1','30','Nicola Marzano','',
        ],
        'help' => [
            'type'          => 'RISTORANTE | TRATTORIA | PIZZERIA | AGRITURISMO | ENOTECA | BAR | OSTERIA',
            'price_range'   => 'BUDGET | MEDIO | ALTO | GOURMET',
            'cuisine_type'  => 'Tipo di cucina (es: Irpina tradizionale, Pizzeria napoletana).',
            'specialties'   => 'Piatti tipici separati da virgola.',
            'opening_hours' => 'Orari di apertura (es: Lun-Ven 12:30-15:00 e 19:30-22:30).',
            'closing_day'   => 'Giorno/i di chiusura (es: Lunedì, Domenica sera).',
            'accepts_groups'=> '0 = no gruppi, 1 = accetta gruppi.',
        ],
    ],
];

// ── Download template CSV ────────────────────────────────────────────────────
if (isset($_GET['template']) && array_key_exists($_GET['template'], $entityMap)) {
    $key    = $_GET['template'];
    $cfg    = $entityMap[$key];
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="template_' . $key . '.csv"');
    // BOM per Excel su Windows
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    // Riga 1: intestazioni colonne
    fputcsv($out, $cfg['columns']);
    // Riga 2: dati di esempio
    fputcsv($out, $cfg['example']);
    fclose($out);
    exit;
}

// ── Import POST ──────────────────────────────────────────────────────────────
$report = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entity = trim($_POST['entity'] ?? '');
    $mode   = trim($_POST['mode'] ?? 'insert_ignore'); // insert_ignore | upsert

    if (!array_key_exists($entity, $entityMap)) {
        $report = ['error' => 'Entità non valida.'];
    } elseif (empty($_FILES['csv_file']['tmp_name']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $report = ['error' => 'File CSV non caricato correttamente.'];
    } else {
        $cfg      = $entityMap[$entity];
        $table    = $cfg['table'];
        $required = $cfg['required'];
        $columns  = $cfg['columns'];

        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        if ($handle === false) {
            $report = ['error' => 'Impossibile leggere il file CSV.'];
        } else {
            $inserted = 0;
            $updated  = 0;
            $skipped  = 0;
            $errors   = [];
            $rowNum   = 0;

            // Prima riga = intestazioni
            $rawHeaders = fgetcsv($handle);
            if ($rawHeaders === false) {
                $report = ['error' => 'Il file CSV è vuoto o non leggibile.'];
                fclose($handle);
                goto render;
            }
            // Rimuovi BOM se presente nella prima cella
            $rawHeaders[0] = ltrim($rawHeaders[0], "\xEF\xBB\xBF");
            $headers = array_map(fn($h) => strtolower(trim($h)), $rawHeaders);

            while (($row = fgetcsv($handle)) !== false) {
                $rowNum++;
                // Salta righe vuote e la riga di esempio (stessa dell'esempio config)
                if (count(array_filter($row, fn($v) => trim($v) !== '')) === 0) continue;

                // Mappa header → valore
                $data = [];
                foreach ($headers as $i => $col) {
                    $data[$col] = isset($row[$i]) ? trim($row[$i]) : '';
                }

                // Valida campi obbligatori
                $missing = [];
                foreach ($required as $req) {
                    if (empty($data[$req] ?? '')) $missing[] = $req;
                }
                if (!empty($missing)) {
                    $errors[] = "Riga $rowNum: campi obbligatori mancanti — " . implode(', ', $missing);
                    continue;
                }

                // Costruisci colonne e valori (solo colonne note)
                $insertCols = [];
                $insertVals = [];
                foreach ($columns as $col) {
                    if (array_key_exists($col, $data)) {
                        $insertCols[] = "`$col`";
                        $insertVals[] = ($data[$col] !== '') ? $data[$col] : null;
                    }
                }

                if (empty($insertCols)) {
                    $errors[] = "Riga $rowNum: nessuna colonna riconosciuta.";
                    continue;
                }

                $colStr = implode(',', $insertCols);
                $phStr  = implode(',', array_fill(0, count($insertVals), '?'));

                try {
                    if ($mode === 'upsert') {
                        // ON DUPLICATE KEY UPDATE — aggiorna tutti i campi non-PK
                        $updatePairs = array_filter($insertCols, fn($c) => $c !== '`' . $cfg['pk'] . '`');
                        $updateStr   = implode(',', array_map(fn($c) => "$c=VALUES($c)", $insertCols));
                        $stmt = $db->prepare("INSERT INTO `$table` ($colStr) VALUES ($phStr) ON DUPLICATE KEY UPDATE $updateStr");
                        $stmt->execute($insertVals);
                        $affected = $stmt->rowCount();
                        if ($affected === 1)     $inserted++;
                        elseif ($affected === 2) $updated++;
                        else                     $skipped++;
                    } else {
                        $stmt = $db->prepare("INSERT IGNORE INTO `$table` ($colStr) VALUES ($phStr)");
                        $stmt->execute($insertVals);
                        $affected = $stmt->rowCount();
                        if ($affected > 0) $inserted++;
                        else               $skipped++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Riga $rowNum: " . htmlspecialchars($e->getMessage());
                }
            }

            fclose($handle);
            $report = compact('entity','table','inserted','updated','skipped','errors','rowNum');
        }
    }
}

render:
require '_layout.php';
?>

<!-- Report -->
<?php if ($report !== null): ?>
  <?php if (isset($report['error'])): ?>
    <div class="mb-6 px-4 py-3 rounded-lg bg-red-900/40 border border-red-700 text-red-300 text-sm">❌ <?= htmlspecialchars($report['error']) ?></div>
  <?php else: ?>
    <div class="mb-6 bg-slate-800 rounded-xl border border-slate-700 p-5 space-y-4">
      <h3 class="font-semibold text-white">
        Risultato import — <span class="text-emerald-400"><?= htmlspecialchars($report['entity']) ?></span>
        <span class="text-slate-400 text-sm font-normal ml-1">(tabella: <code><?= htmlspecialchars($report['table']) ?></code>, <?= $report['rowNum'] ?> righe elaborate)</span>
      </h3>
      <div class="flex flex-wrap gap-3">
        <div class="px-4 py-2 rounded-lg bg-emerald-900/40 border border-emerald-700 text-center">
          <div class="text-2xl font-bold text-emerald-400"><?= $report['inserted'] ?></div>
          <div class="text-xs text-emerald-300">inseriti</div>
        </div>
        <?php if ($report['updated'] > 0): ?>
        <div class="px-4 py-2 rounded-lg bg-blue-900/40 border border-blue-700 text-center">
          <div class="text-2xl font-bold text-blue-400"><?= $report['updated'] ?></div>
          <div class="text-xs text-blue-300">aggiornati</div>
        </div>
        <?php endif; ?>
        <div class="px-4 py-2 rounded-lg bg-yellow-900/40 border border-yellow-700 text-center">
          <div class="text-2xl font-bold text-yellow-400"><?= $report['skipped'] ?></div>
          <div class="text-xs text-yellow-300">saltati (duplicati)</div>
        </div>
        <div class="px-4 py-2 rounded-lg bg-red-900/40 border border-red-700 text-center">
          <div class="text-2xl font-bold text-red-400"><?= count($report['errors']) ?></div>
          <div class="text-xs text-red-300">errori</div>
        </div>
      </div>
      <?php if (!empty($report['errors'])): ?>
        <div>
          <h4 class="text-sm font-semibold text-red-400 mb-2">Dettaglio errori:</h4>
          <ul class="space-y-1 max-h-48 overflow-y-auto">
            <?php foreach ($report['errors'] as $err): ?>
              <li class="text-xs text-red-300 bg-red-900/20 border border-red-800/50 rounded px-3 py-1.5"><?= htmlspecialchars($err) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>

<div class="grid md:grid-cols-3 gap-6">

  <!-- Form import -->
  <div class="md:col-span-2 space-y-5">
    <form method="POST" enctype="multipart/form-data" class="bg-slate-800 rounded-xl border border-slate-700 p-6 space-y-5">
      <h3 class="font-semibold text-white">Carica file CSV</h3>

      <div class="grid grid-cols-2 gap-4">
        <!-- Entità -->
        <div>
          <label class="block text-xs font-medium text-slate-400 mb-1.5">Entità da importare</label>
          <select name="entity" id="entitySelect"
            class="w-full bg-slate-700 border border-slate-600 text-white text-sm rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-emerald-500">
            <option value="">— Seleziona —</option>
            <?php foreach ($entityMap as $key => $cfg): ?>
              <option value="<?= $key ?>"
                data-columns='<?= json_encode($cfg['columns'], JSON_UNESCAPED_UNICODE) ?>'
                data-required='<?= json_encode($cfg['required'], JSON_UNESCAPED_UNICODE) ?>'
                data-help='<?= htmlspecialchars(json_encode($cfg['help'] ?? [], JSON_UNESCAPED_UNICODE)) ?>'
                <?= (($_POST['entity'] ?? '') === $key ? 'selected' : '') ?>>
                <?= $cfg['label'] ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <!-- Modalità -->
        <div>
          <label class="block text-xs font-medium text-slate-400 mb-1.5">Modalità import</label>
          <select name="mode" class="w-full bg-slate-700 border border-slate-600 text-white text-sm rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-emerald-500">
            <option value="insert_ignore">Salta duplicati (INSERT IGNORE)</option>
            <option value="upsert" <?= (($_POST['mode'] ?? '') === 'upsert' ? 'selected' : '') ?>>Aggiorna esistenti (UPSERT)</option>
          </select>
        </div>
      </div>

      <!-- File -->
      <div>
        <label class="block text-xs font-medium text-slate-400 mb-1.5">File CSV</label>
        <input type="file" name="csv_file" accept=".csv,text/csv"
          class="w-full bg-slate-700 border border-slate-600 text-slate-300 text-sm rounded-lg px-3 py-2.5
                 file:mr-3 file:py-1 file:px-3 file:rounded file:border-0 file:bg-emerald-700 file:text-white file:text-xs file:cursor-pointer">
        <p class="text-xs text-slate-500 mt-1">Prima riga = intestazioni colonne · Seconda riga = dati di esempio (nei template scaricati). Encoding: UTF-8.</p>
      </div>

      <div class="flex items-center gap-3 pt-1">
        <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg transition-colors">
          ⬆️ Importa
        </button>
      </div>
    </form>

    <!-- Info colonne dinamica -->
    <div class="bg-slate-800 rounded-xl border border-slate-700 p-5" id="formatPanel" style="display:none">
      <h4 class="font-semibold text-sm text-white mb-3">📋 Colonne per entità selezionata</h4>
      <div id="formatContent"></div>
    </div>
  </div>

  <!-- Sidebar template download -->
  <div class="space-y-4">
    <div class="bg-slate-800 rounded-xl border border-slate-700 p-5">
      <h4 class="font-semibold text-sm text-white mb-1">⬇️ Scarica template CSV</h4>
      <p class="text-xs text-slate-500 mb-3">Ogni template include la riga intestazioni e una riga di esempio.</p>
      <div class="space-y-2">
        <?php foreach ($entityMap as $key => $cfg): ?>
          <a href="bulk-import.php?template=<?= urlencode($key) ?>"
             class="flex items-center justify-between px-3 py-2 rounded-lg bg-slate-700 hover:bg-slate-600 transition-colors">
            <span class="text-sm text-slate-300 hover:text-white"><?= $cfg['label'] ?></span>
            <span class="text-xs text-slate-500">CSV ↓</span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="bg-slate-800 rounded-xl border border-slate-700 p-5">
      <h4 class="font-semibold text-sm text-white mb-2">💡 Sondaggio guidato</h4>
      <p class="text-xs text-slate-400 mb-3">Invia alle aziende il link del sondaggio per raccogliere i dati in formato accessibile.</p>
      <a href="/api/survey.php" target="_blank"
         class="flex items-center gap-2 px-3 py-2 rounded-lg bg-emerald-700/40 border border-emerald-600 hover:bg-emerald-700/60 text-emerald-300 text-sm transition-colors">
        📋 Apri sondaggio
      </a>
      <a href="/api/admin/sondaggi.php"
         class="flex items-center gap-2 px-3 py-2 mt-2 rounded-lg bg-slate-700 hover:bg-slate-600 text-slate-300 text-sm transition-colors">
        📥 Revisiona invii
      </a>
    </div>
  </div>
</div>

<script>
(function () {
  const select  = document.getElementById('entitySelect');
  const panel   = document.getElementById('formatPanel');
  const content = document.getElementById('formatContent');

  function updateFormat() {
    const opt = select.options[select.selectedIndex];
    if (!opt || !opt.value) { panel.style.display='none'; return; }
    const columns  = JSON.parse(opt.dataset.columns  || '[]');
    const required = JSON.parse(opt.dataset.required || '[]');
    const help     = JSON.parse(opt.dataset.help     || '{}');

    const rows = columns.map(col => {
      const isReq = required.includes(col);
      const hint  = help[col] ? `<span class="text-slate-500 ml-2 text-xs">${help[col]}</span>` : '';
      return `<div class="flex items-start gap-2 py-1.5 border-b border-slate-700/40 last:border-0">
        <code class="shrink-0 text-xs ${isReq ? 'text-emerald-400 font-bold' : 'text-slate-300'} w-40">${col}</code>
        <span>${isReq ? '<span class="text-xs text-emerald-600 font-semibold mr-1">●</span>' : '<span class="text-xs text-slate-600 mr-1">○</span>'}${hint}</span>
      </div>`;
    }).join('');

    content.innerHTML = `<div class="text-xs mb-2"><span class="text-emerald-400 font-semibold">●</span> Obbligatorio &nbsp; <span class="text-slate-500">○</span> Opzionale</div>${rows}`;
    panel.style.display = 'block';
  }

  select.addEventListener('change', updateFormat);
  updateFormat();
})();
</script>

<?php require '_footer.php'; ?>
