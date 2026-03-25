<?php
require_once __DIR__ . '/../config/db.php';
requireAdminSession();
$db = getDB();

// Definizione entità: [tabella, label, campi_chiave[], pagina_modifica]
$entities = [
    'boroughs' => [
        'label'  => 'Borghi',
        'fields' => ['name', 'slug', 'description', 'cover_image', 'population', 'altitude'],
        'page'   => 'borghi.php',
        'id_col' => 'id',
        'name_col' => 'name',
    ],
    'experiences' => [
        'label'  => 'Esperienze',
        'fields' => ['title', 'slug', 'description', 'cover_image', 'price_per_person', 'duration_minutes', 'borough_id'],
        'page'   => 'esperienze.php',
        'id_col' => 'id',
        'name_col' => 'title',
    ],
    'companies' => [
        'label'  => 'Aziende',
        'fields' => ['name', 'slug', 'description_short', 'cover_image', 'borough_id', 'type'],
        'page'   => 'aziende.php',
        'id_col' => 'id',
        'name_col' => 'name',
    ],
    'food_products' => [
        'label'  => 'Prodotti Food',
        'fields' => ['name', 'slug', 'description', 'cover_image', 'price', 'category'],
        'page'   => 'prodotti.php',
        'id_col' => 'id',
        'name_col' => 'name',
    ],
    'craft_products' => [
        'label'  => 'Artigianato',
        'fields' => ['name', 'slug', 'description', 'cover_image', 'price'],
        'page'   => 'artigianato.php',
        'id_col' => 'id',
        'name_col' => 'name',
    ],
    'accommodations' => [
        'label'  => 'Ospitalità',
        'fields' => ['name', 'slug', 'description', 'cover_image', 'price_per_night', 'borough_id'],
        'page'   => 'ospitalita.php',
        'id_col' => 'id',
        'name_col' => 'name',
    ],
    'restaurants' => [
        'label'  => 'Ristorazione',
        'fields' => ['name', 'slug', 'description', 'cover_image', 'borough_id'],
        'page'   => 'ristorazione.php',
        'id_col' => 'id',
        'name_col' => 'name',
    ],
];

// --- Calcolo qualità per ogni entità ---
$qualityData   = [];   // dati aggregati per le card
$incompleteRows = [];  // record incompleti per la tabella finale

foreach ($entities as $table => $meta) {
    $fields = $meta['fields'];
    $idCol  = $meta['id_col'];
    $nameCol = $meta['name_col'];

    // Costruisci il CASE WHEN per i campi chiave
    $conditions = array_map(function ($f) {
        return "(`$f` IS NOT NULL AND `$f` != '')";
    }, $fields);
    $completeExpr = implode(' AND ', $conditions);

    // Query aggregata: totale + completi
    try {
        $stmt = $db->query(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN $completeExpr THEN 1 ELSE 0 END) AS complete
             FROM `$table`"
        );
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $row = ['total' => 0, 'complete' => 0];
    }

    $total    = (int)($row['total']   ?? 0);
    $complete = (int)($row['complete'] ?? 0);
    $pct      = $total > 0 ? round(($complete / $total) * 100) : 0;

    $qualityData[$table] = [
        'label'    => $meta['label'],
        'total'    => $total,
        'complete' => $complete,
        'missing'  => $total - $complete,
        'pct'      => $pct,
        'page'     => $meta['page'],
    ];

    // Record incompleti (max 50 per tabella)
    if ($total > $complete) {
        $notCompleteExpr = "NOT ($completeExpr)";
        try {
            // Seleziona tutti i campi in un'unica query, evitando N+1
            $incStmt = $db->query(
                "SELECT *
                 FROM `$table`
                 WHERE $notCompleteExpr
                 ORDER BY `$idCol` ASC
                 LIMIT 50"
            );
            $rows = $incStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $rows = [];
        }

        // Determina quali campi mancano per ogni record
        foreach ($rows as $full) {
            $missingFields = [];
            foreach ($fields as $f) {
                if (!isset($full[$f]) || $full[$f] === null || $full[$f] === '') {
                    $missingFields[] = $f;
                }
            }

            $incompleteRows[] = [
                'table'         => $table,
                'label'         => $meta['label'],
                'id'            => $full[$idCol],
                'name'          => $full[$nameCol] ?? '(senza nome)',
                'page'          => $meta['page'],
                'missingFields' => $missingFields,
            ];
        }
    }
}

// Riepilogo globale
$totalAll    = array_sum(array_column($qualityData, 'total'));
$completeAll = array_sum(array_column($qualityData, 'complete'));
$globalPct   = $totalAll > 0 ? round(($completeAll / $totalAll) * 100) : 0;

$pageTitle = 'Qualità Dati';
require '_layout.php';
?>

<!-- KPI globali -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
  <div class="bg-slate-800 rounded-xl p-5 border border-slate-700">
    <div class="text-xs text-slate-400 mb-1">Record totali</div>
    <div class="text-3xl font-bold text-white"><?= number_format($totalAll) ?></div>
  </div>
  <div class="bg-slate-800 rounded-xl p-5 border border-slate-700">
    <div class="text-xs text-slate-400 mb-1">Record completi</div>
    <div class="text-3xl font-bold text-emerald-400"><?= number_format($completeAll) ?></div>
  </div>
  <div class="bg-slate-800 rounded-xl p-5 border border-slate-700">
    <div class="text-xs text-slate-400 mb-1">Completezza globale</div>
    <div class="text-3xl font-bold <?= $globalPct >= 80 ? 'text-emerald-400' : ($globalPct >= 50 ? 'text-yellow-400' : 'text-red-400') ?>">
      <?= $globalPct ?>%
    </div>
    <div class="mt-2 w-full bg-slate-700 rounded-full h-2">
      <div class="h-2 rounded-full transition-all <?= $globalPct >= 80 ? 'bg-emerald-500' : ($globalPct >= 50 ? 'bg-yellow-500' : 'bg-red-500') ?>"
           style="width:<?= $globalPct ?>%"></div>
    </div>
  </div>
</div>

<!-- Card per entità -->
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5 mb-10">
  <?php foreach ($qualityData as $table => $q): ?>
  <?php
    $barColor  = $q['pct'] >= 80 ? 'bg-emerald-500' : ($q['pct'] >= 50 ? 'bg-yellow-500' : 'bg-red-500');
    $textColor = $q['pct'] >= 80 ? 'text-emerald-400' : ($q['pct'] >= 50 ? 'text-yellow-400' : 'text-red-400');
    $badgeBg   = $q['pct'] >= 80 ? 'bg-emerald-900/50 text-emerald-400' : ($q['pct'] >= 50 ? 'bg-yellow-900/50 text-yellow-400' : 'bg-red-900/50 text-red-400');
  ?>
  <div class="bg-slate-800 rounded-xl border border-slate-700 p-5">
    <div class="flex items-start justify-between mb-3">
      <h3 class="font-semibold text-white text-base"><?= htmlspecialchars($q['label']) ?></h3>
      <span class="text-xs font-bold px-2.5 py-1 rounded-full <?= $badgeBg ?>"><?= $q['pct'] ?>%</span>
    </div>

    <!-- Barra progresso -->
    <div class="w-full bg-slate-700 rounded-full h-3 mb-3 overflow-hidden">
      <div class="h-3 rounded-full transition-all <?= $barColor ?>"
           style="width:<?= $q['pct'] ?>%"></div>
    </div>

    <!-- Contatori -->
    <div class="flex items-center justify-between text-sm">
      <span class="text-slate-400">
        <span class="<?= $textColor ?> font-bold"><?= number_format($q['complete']) ?></span>
        / <?= number_format($q['total']) ?> record completi
      </span>
      <?php if ($q['missing'] > 0): ?>
      <a href="<?= htmlspecialchars($q['page']) ?>"
         class="text-xs text-slate-400 hover:text-white underline transition-colors">
        <?= $q['missing'] ?> da completare
      </a>
      <?php else: ?>
      <span class="text-xs text-emerald-400 font-medium">Tutto completo</span>
      <?php endif; ?>
    </div>

    <!-- Dettaglio campi richiesti -->
    <div class="mt-3 pt-3 border-t border-slate-700">
      <p class="text-xs text-slate-500">Campi richiesti:</p>
      <p class="text-xs text-slate-400 mt-1 leading-relaxed">
        <?php
        $entityDef = $entities[$table] ?? null;
        if ($entityDef) {
            echo htmlspecialchars(implode(', ', $entityDef['fields']));
        }
        ?>
      </p>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Tabella record incompleti -->
<div class="bg-slate-800 rounded-xl border border-slate-700 p-6">
  <div class="flex items-center gap-3 mb-1">
    <h3 class="font-semibold text-white">Record incompleti</h3>
    <?php if (!empty($incompleteRows)): ?>
    <span class="bg-red-900/50 text-red-400 text-xs font-bold px-2.5 py-0.5 rounded-full">
      <?= count($incompleteRows) ?><?= array_sum(array_column($qualityData, 'missing')) > count($incompleteRows) ? '+' : '' ?>
    </span>
    <?php endif; ?>
  </div>
  <p class="text-xs text-slate-400 mb-5">
    Record a cui mancano uno o più campi chiave. Mostrati max 50 per entità.
  </p>

  <?php if (empty($incompleteRows)): ?>
  <div class="flex items-center gap-2 text-emerald-400 text-sm py-4">
    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
    </svg>
    <span>Tutti i record hanno i campi chiave compilati. Ottimo lavoro!</span>
  </div>
  <?php else: ?>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead>
        <tr class="text-left text-xs text-slate-400 border-b border-slate-700">
          <th class="pb-3 pr-4 font-medium">Entità</th>
          <th class="pb-3 pr-4 font-medium">ID</th>
          <th class="pb-3 pr-4 font-medium">Nome / Titolo</th>
          <th class="pb-3 pr-4 font-medium">Campi mancanti</th>
          <th class="pb-3 text-right font-medium">Azione</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-700/50">
        <?php foreach ($incompleteRows as $ir): ?>
        <tr class="hover:bg-slate-700/30 transition-colors">
          <td class="py-2.5 pr-4">
            <span class="text-xs font-semibold px-2 py-0.5 rounded bg-slate-700 text-slate-300">
              <?= htmlspecialchars($ir['label']) ?>
            </span>
          </td>
          <td class="py-2.5 pr-4 text-slate-400 font-mono text-xs">
            <?= htmlspecialchars((string)$ir['id']) ?>
          </td>
          <td class="py-2.5 pr-4 text-white font-medium max-w-[200px] truncate">
            <?= htmlspecialchars($ir['name']) ?>
          </td>
          <td class="py-2.5 pr-4">
            <div class="flex flex-wrap gap-1">
              <?php foreach ($ir['missingFields'] as $mf): ?>
              <span class="text-xs bg-red-900/40 text-red-400 border border-red-800/50 px-1.5 py-0.5 rounded font-mono">
                <?= htmlspecialchars($mf) ?>
              </span>
              <?php endforeach; ?>
            </div>
          </td>
          <td class="py-2.5 text-right">
            <a href="<?= htmlspecialchars($ir['page']) ?>?edit=<?= urlencode((string)$ir['id']) ?>"
               class="inline-flex items-center gap-1 text-xs text-emerald-400 hover:text-emerald-300 font-medium transition-colors underline">
              Modifica
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php require '_footer.php'; ?>
