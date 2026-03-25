<?php
require_once __DIR__ . '/../config/db.php';
requireAdminSession();
$db = getDB();

$pageTitle = 'Import Bulk CSV';

$entityMap = [
    'borghi'      => ['table' => 'boroughs',      'required' => ['name', 'slug', 'province'], 'columns' => ['name','slug','province','region','description','population','altitude','cover_image']],
    'esperienze'  => ['table' => 'experiences',   'required' => ['title', 'slug', 'category'], 'columns' => ['title','slug','category','description','price_per_person','duration_minutes','borough_id','cover_image']],
    'aziende'     => ['table' => 'companies',      'required' => ['name', 'slug', 'type'],     'columns' => ['name','slug','type','borough_id','description_short','website','phone','email']],
    'prodotti'    => ['table' => 'food_products',  'required' => ['name', 'slug', 'category'], 'columns' => ['name','slug','category','description','price','borough_id','cover_image']],
    'artigianato' => ['table' => 'craft_products', 'required' => ['name', 'slug'],             'columns' => ['name','slug','category','description','price','borough_id','cover_image']],
];

// ── Download template CSV ────────────────────────────────────
if (isset($_GET['template']) && array_key_exists($_GET['template'], $entityMap)) {
    $key    = $_GET['template'];
    $config = $entityMap[$key];
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="template_' . $key . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, $config['columns']);
    fclose($out);
    exit;
}

// ── Import POST ──────────────────────────────────────────────
$report = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entity = trim($_POST['entity'] ?? '');

    if (!array_key_exists($entity, $entityMap)) {
        $report = ['error' => 'Entità non valida.'];
    } elseif (empty($_FILES['csv_file']['tmp_name']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $report = ['error' => 'File CSV non caricato correttamente.'];
    } else {
        $config   = $entityMap[$entity];
        $table    = $config['table'];
        $required = $config['required'];
        $columns  = $config['columns'];

        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        if ($handle === false) {
            $report = ['error' => 'Impossibile leggere il file CSV.'];
        } else {
            $inserted = 0;
            $skipped  = 0;
            $errors   = [];
            $rowNum   = 0;

            // Prima riga = intestazioni
            $headers = fgetcsv($handle);
            if ($headers === false) {
                $report = ['error' => 'Il file CSV è vuoto o non leggibile.'];
                fclose($handle);
                goto render;
            }

            // Normalizza intestazioni (trim + lowercase)
            $headers = array_map(fn($h) => strtolower(trim($h)), $headers);

            while (($row = fgetcsv($handle)) !== false) {
                $rowNum++;

                // Salta righe completamente vuote
                if (count(array_filter($row, fn($v) => trim($v) !== '')) === 0) {
                    continue;
                }

                // Mappa intestazioni -> valori
                $data = [];
                foreach ($headers as $i => $col) {
                    $data[$col] = isset($row[$i]) ? trim($row[$i]) : '';
                }

                // Controlla campi required
                $missing = [];
                foreach ($required as $req) {
                    if (!isset($data[$req]) || $data[$req] === '') {
                        $missing[] = $req;
                    }
                }
                if (!empty($missing)) {
                    $errors[] = "Riga $rowNum: campi obbligatori mancanti — " . implode(', ', $missing);
                    continue;
                }

                // Costruisci i valori da inserire (solo colonne note)
                $insertCols = [];
                $insertVals = [];
                foreach ($columns as $col) {
                    if (isset($data[$col])) {
                        $insertCols[] = "`$col`";
                        $insertVals[] = $data[$col] !== '' ? $data[$col] : null;
                    }
                }

                if (empty($insertCols)) {
                    $errors[] = "Riga $rowNum: nessuna colonna riconosciuta.";
                    continue;
                }

                $colStr = implode(',', $insertCols);
                $phStr  = implode(',', array_fill(0, count($insertVals), '?'));

                try {
                    $stmt = $db->prepare("INSERT IGNORE INTO `$table` ($colStr) VALUES ($phStr)");
                    $stmt->execute($insertVals);
                    $affected = $stmt->rowCount();
                    if ($affected > 0) {
                        $inserted++;
                    } else {
                        $skipped++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Riga $rowNum: " . htmlspecialchars($e->getMessage());
                }
            }

            fclose($handle);

            $report = [
                'entity'   => $entity,
                'table'    => $table,
                'inserted' => $inserted,
                'skipped'  => $skipped,
                'errors'   => $errors,
                'total'    => $rowNum,
            ];
        }
    }
}

render:
require '_layout.php';
?>

<!-- Report risultato import -->
<?php if ($report !== null): ?>
  <?php if (isset($report['error'])): ?>
    <div class="mb-6 px-4 py-3 rounded-lg bg-red-900/40 border border-red-700 text-red-300 text-sm">
      ❌ <?= htmlspecialchars($report['error']) ?>
    </div>
  <?php else: ?>
    <div class="mb-6 bg-slate-800 rounded-xl border border-slate-700 p-5 space-y-4">
      <h3 class="font-semibold text-white text-base">Risultato import — <span class="text-emerald-400"><?= htmlspecialchars($report['entity']) ?></span> (tabella <code class="text-slate-300"><?= htmlspecialchars($report['table']) ?></code>)</h3>
      <div class="flex flex-wrap gap-4">
        <div class="flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-900/40 border border-emerald-700">
          <span class="text-2xl font-bold text-emerald-400"><?= $report['inserted'] ?></span>
          <span class="text-sm text-emerald-300">record inseriti</span>
        </div>
        <div class="flex items-center gap-2 px-4 py-2 rounded-lg bg-yellow-900/40 border border-yellow-700">
          <span class="text-2xl font-bold text-yellow-400"><?= $report['skipped'] ?></span>
          <span class="text-sm text-yellow-300">saltati (duplicati)</span>
        </div>
        <div class="flex items-center gap-2 px-4 py-2 rounded-lg bg-red-900/40 border border-red-700">
          <span class="text-2xl font-bold text-red-400"><?= count($report['errors']) ?></span>
          <span class="text-sm text-red-300">errori</span>
        </div>
        <div class="flex items-center gap-2 px-4 py-2 rounded-lg bg-slate-700 border border-slate-600">
          <span class="text-2xl font-bold text-slate-300"><?= $report['total'] ?></span>
          <span class="text-sm text-slate-400">righe elaborate</span>
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

<!-- Form import -->
<div class="grid md:grid-cols-3 gap-6">

  <!-- Pannello form -->
  <div class="md:col-span-2">
    <form method="POST" enctype="multipart/form-data" class="bg-slate-800 rounded-xl border border-slate-700 p-6 space-y-5" id="importForm">
      <h3 class="font-semibold text-white text-base">Carica file CSV</h3>

      <!-- Selezione entità -->
      <div>
        <label class="block text-xs font-medium text-slate-400 mb-1.5">Entità da importare</label>
        <select name="entity" id="entitySelect"
                class="w-full bg-slate-700 border border-slate-600 text-white text-sm rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-emerald-500">
          <option value="">— Seleziona entità —</option>
          <?php foreach ($entityMap as $key => $cfg): ?>
            <option value="<?= $key ?>"
              data-columns="<?= htmlspecialchars(implode(', ', $cfg['columns'])) ?>"
              data-required="<?= htmlspecialchars(implode(', ', $cfg['required'])) ?>"
              <?= (($_POST['entity'] ?? '') === $key ? 'selected' : '') ?>>
              <?= ucfirst($key) ?> (<?= $cfg['table'] ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- File input -->
      <div>
        <label class="block text-xs font-medium text-slate-400 mb-1.5">File CSV</label>
        <input type="file" name="csv_file" accept=".csv,text/csv"
               class="w-full bg-slate-700 border border-slate-600 text-slate-300 text-sm rounded-lg px-3 py-2.5 file:mr-3 file:py-1 file:px-3 file:rounded file:border-0 file:bg-emerald-700 file:text-white file:text-xs file:cursor-pointer cursor-pointer focus:outline-none focus:ring-2 focus:ring-emerald-500">
        <p class="text-xs text-slate-500 mt-1">Prima riga deve contenere le intestazioni delle colonne.</p>
      </div>

      <!-- Pulsante -->
      <div class="flex items-center gap-3 pt-1">
        <button type="submit"
                class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg transition-colors">
          ⬆️ Importa
        </button>
        <span class="text-xs text-slate-500">I duplicati (stesso slug) vengono ignorati automaticamente.</span>
      </div>
    </form>
  </div>

  <!-- Pannello info entità -->
  <div class="md:col-span-1 space-y-4">

    <!-- Formato atteso (dinamico) -->
    <div class="bg-slate-800 rounded-xl border border-slate-700 p-5" id="formatPanel">
      <h4 class="font-semibold text-sm text-white mb-3">📋 Formato atteso</h4>
      <div id="formatContent">
        <p class="text-xs text-slate-500">Seleziona un'entità per vedere le colonne richieste.</p>
      </div>
    </div>

    <!-- Template CSV download -->
    <div class="bg-slate-800 rounded-xl border border-slate-700 p-5">
      <h4 class="font-semibold text-sm text-white mb-3">⬇️ Scarica template CSV</h4>
      <div class="space-y-2">
        <?php foreach ($entityMap as $key => $cfg): ?>
          <a href="bulk-import.php?template=<?= urlencode($key) ?>"
             class="flex items-center justify-between px-3 py-2 rounded-lg bg-slate-700 hover:bg-slate-600 transition-colors text-sm text-slate-300 hover:text-white">
            <span><?= ucfirst($key) ?></span>
            <span class="text-xs text-slate-500">CSV ↓</span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>

  </div>
</div>

<script>
(function () {
  const select  = document.getElementById('entitySelect');
  const content = document.getElementById('formatContent');

  const entityData = <?= json_encode(array_map(fn($cfg) => [
      'columns'  => $cfg['columns'],
      'required' => $cfg['required'],
  ], $entityMap), JSON_UNESCAPED_UNICODE) ?>;

  function updateFormat() {
    const key = select.value;
    if (!key || !entityData[key]) {
      content.innerHTML = '<p class="text-xs text-slate-500">Seleziona un\'entità per vedere le colonne richieste.</p>';
      return;
    }
    const cfg = entityData[key];
    const items = cfg.columns.map(col => {
      const isRequired = cfg.required.includes(col);
      return `<div class="flex items-center justify-between py-1 border-b border-slate-700/50 last:border-0">
        <code class="text-xs ${isRequired ? 'text-emerald-400 font-semibold' : 'text-slate-300'}">${col}</code>
        ${isRequired ? '<span class="text-xs text-emerald-500 font-medium">richiesto</span>' : '<span class="text-xs text-slate-600">opzionale</span>'}
      </div>`;
    }).join('');
    content.innerHTML = `<div class="space-y-0.5">${items}</div>
      <p class="text-xs text-slate-500 mt-3">Le colonne <span class="text-emerald-400 font-semibold">verdi</span> sono obbligatorie. L'ordine delle colonne nel CSV deve corrispondere alle intestazioni.</p>`;
  }

  select.addEventListener('change', updateFormat);
  updateFormat(); // init on page load (e.g. after POST)
})();
</script>

<?php require '_footer.php'; ?>
