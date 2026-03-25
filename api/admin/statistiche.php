<?php
require_once __DIR__ . '/../config/db.php';
requireAdminSession();
$db = getDB();

// Assicura che le tabelle analytics esistano
try {
    $db->query("SELECT 1 FROM page_views LIMIT 1");
} catch (PDOException $e) {
    $db->exec("CREATE TABLE IF NOT EXISTS `page_views` (
      `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
      `entity_type` VARCHAR(50) NOT NULL,
      `entity_id` VARCHAR(100) NOT NULL,
      `page_url` TEXT DEFAULT NULL,
      `referrer` TEXT DEFAULT NULL,
      `user_agent` TEXT DEFAULT NULL,
      `ip_hash` VARCHAR(64) DEFAULT NULL,
      `session_id` VARCHAR(100) DEFAULT NULL,
      `viewed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      INDEX `idx_entity` (`entity_type`, `entity_id`),
      INDEX `idx_viewed_at` (`viewed_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->exec("CREATE TABLE IF NOT EXISTS `daily_stats` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `stat_date` DATE NOT NULL,
      `entity_type` VARCHAR(50) NOT NULL,
      `entity_id` VARCHAR(100) NOT NULL,
      `views_count` INT DEFAULT 0,
      `unique_views` INT DEFAULT 0,
      UNIQUE KEY `uq_daily` (`stat_date`, `entity_type`, `entity_id`),
      INDEX `idx_date` (`stat_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

// Export CSV
if (($_GET['export'] ?? '') === 'csv') {
    $csvPeriod = (int)($_GET['period'] ?? 30);
    $csvPeriod = max(1, min(365, $csvPeriod));
    $csvFrom   = date('Y-m-d', strtotime("-{$csvPeriod} days"));

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="statistiche-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8

    fputcsv($out, ['Tipo entità', 'ID entità', 'Data', 'Visualizzazioni', 'Visitatori unici']);

    $stmt = $db->prepare(
        "SELECT entity_type, entity_id, stat_date, views_count, unique_views
         FROM daily_stats WHERE stat_date >= ? ORDER BY stat_date DESC, views_count DESC"
    );
    $stmt->execute([$csvFrom]);
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

$period = (int)($_GET['period'] ?? 30);
$period = max(1, min(365, $period));
$dateFrom = date('Y-m-d', strtotime("-{$period} days"));

// KPI principali
$totalViews = $db->prepare("SELECT COALESCE(SUM(views_count),0) FROM daily_stats WHERE stat_date >= ?");
$totalViews->execute([$dateFrom]);
$kpiViews = (int)$totalViews->fetchColumn();

$totalUnique = $db->prepare("SELECT COALESCE(SUM(unique_views),0) FROM daily_stats WHERE stat_date >= ?");
$totalUnique->execute([$dateFrom]);
$kpiUnique = (int)$totalUnique->fetchColumn();

$totalToday = $db->prepare("SELECT COALESCE(SUM(views_count),0) FROM daily_stats WHERE stat_date = CURDATE()");
$totalToday->execute();
$kpiToday = (int)$totalToday->fetchColumn();

// Conteggio contenuti attivi
$contentCounts = [];
$tables = ['boroughs' => 'borghi', 'companies' => 'aziende', 'experiences' => 'esperienze',
           'craft_products' => 'artigianato', 'food_products' => 'prodotti', 'accommodations' => 'ospitalita',
           'restaurants' => 'ristorazione'];
foreach ($tables as $t => $label) {
    try { $contentCounts[$label] = (int)$db->query("SELECT COUNT(*) FROM `$t`")->fetchColumn(); }
    catch (PDOException $e) { $contentCounts[$label] = 0; }
}
$kpiTotalContent = array_sum($contentCounts);

// Views per tipo
$byType = $db->prepare("SELECT entity_type, SUM(views_count) as views, SUM(unique_views) as uniques
    FROM daily_stats WHERE stat_date >= ? GROUP BY entity_type ORDER BY views DESC");
$byType->execute([$dateFrom]);
$viewsByType = $byType->fetchAll();

// Top 10 entità
$top = $db->prepare("SELECT entity_type, entity_id, SUM(views_count) as views
    FROM daily_stats WHERE stat_date >= ? GROUP BY entity_type, entity_id ORDER BY views DESC LIMIT 10");
$top->execute([$dateFrom]);
$topEntities = $top->fetchAll();

// Top per Borgo
$topBoroughStmt = $db->prepare("SELECT entity_id, SUM(views_count) as total_views, SUM(unique_views) as total_unique
    FROM daily_stats
    WHERE stat_date >= DATE_SUB(CURDATE(), INTERVAL $period DAY)
    AND entity_type = 'borough'
    GROUP BY entity_id
    ORDER BY total_views DESC
    LIMIT 10");
$topBoroughStmt->execute();
$topBoroughs = $topBoroughStmt->fetchAll();

// Borghi senza visite (ultimi 30 giorni)
$noVisitsStmt = $db->prepare("SELECT id, name FROM boroughs
    WHERE id NOT IN (
        SELECT DISTINCT entity_id FROM daily_stats
        WHERE entity_type = 'borough'
        AND stat_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    )
    ORDER BY name ASC");
$noVisitsStmt->execute();
$boroughsNoVisits = $noVisitsStmt->fetchAll();

// Trend ultimi giorni
$trend = $db->prepare("SELECT stat_date, SUM(views_count) as views FROM daily_stats
    WHERE stat_date >= ? GROUP BY stat_date ORDER BY stat_date ASC");
$trend->execute([$dateFrom]);
$dailyTrend = $trend->fetchAll();

// Map entity_type per label italiane
$typeLabels = [
    'borough' => 'Borghi', 'company' => 'Aziende', 'experience' => 'Esperienze',
    'craft' => 'Artigianato', 'food' => 'Prodotti Food', 'accommodation' => 'Ospitalità',
    'restaurant' => 'Ristorazione',
];

$pageTitle = 'Statistiche';
require '_layout.php';
?>

<!-- KPI Cards -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
  <div class="bg-slate-800 rounded-xl p-5 border border-slate-700">
    <div class="text-xs text-slate-400 mb-1">Visualizzazioni (<?= $period ?>gg)</div>
    <div class="text-3xl font-bold text-emerald-400"><?= number_format($kpiViews) ?></div>
  </div>
  <div class="bg-slate-800 rounded-xl p-5 border border-slate-700">
    <div class="text-xs text-slate-400 mb-1">Visitatori unici</div>
    <div class="text-3xl font-bold text-cyan-400"><?= number_format($kpiUnique) ?></div>
  </div>
  <div class="bg-slate-800 rounded-xl p-5 border border-slate-700">
    <div class="text-xs text-slate-400 mb-1">Visite oggi</div>
    <div class="text-3xl font-bold text-yellow-400"><?= number_format($kpiToday) ?></div>
  </div>
  <div class="bg-slate-800 rounded-xl p-5 border border-slate-700">
    <div class="text-xs text-slate-400 mb-1">Contenuti totali</div>
    <div class="text-3xl font-bold text-purple-400"><?= number_format($kpiTotalContent) ?></div>
  </div>
</div>

<!-- Period selector -->
<div class="mb-6 flex flex-wrap gap-3 items-center">
  <span class="text-sm text-slate-400 font-medium">Periodo:</span>
  <?php foreach ([7 => '7 giorni', 30 => '30 giorni', 90 => '90 giorni', 365 => '1 anno'] as $p => $label): ?>
  <a href="statistiche.php?period=<?= $p ?>"
     class="px-5 py-2.5 text-sm font-semibold rounded-full transition-all shadow-sm
       <?= $period === $p
         ? 'bg-emerald-500 text-white shadow-emerald-500/30 shadow-lg ring-2 ring-emerald-400'
         : 'bg-slate-700 text-slate-300 hover:bg-slate-600 hover:text-white border border-slate-600' ?>">
    <?= $label ?>
  </a>
  <?php endforeach; ?>
</div>

<!-- Export CSV -->
<div class="mb-6 flex items-center gap-3">
  <a href="statistiche.php?export=csv&period=<?= $period ?>"
     class="inline-flex items-center gap-2 px-5 py-2.5 bg-slate-700 hover:bg-slate-600 text-slate-200 text-sm font-semibold rounded-full border border-slate-600 transition-all">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
    </svg>
    Scarica CSV (<?= $period ?> giorni)
  </a>
</div>

<div class="grid md:grid-cols-2 gap-6 mb-8">
  <!-- Trend giornaliero -->
  <div class="bg-slate-800 rounded-xl border border-slate-700 p-6">
    <h3 class="font-semibold text-white mb-4">Trend visualizzazioni</h3>
    <?php if (empty($dailyTrend)): ?>
    <p class="text-slate-400 text-sm">Nessun dato disponibile. Le statistiche verranno popolate quando gli utenti visiteranno il sito.</p>
    <?php else: ?>
    <div class="space-y-1">
      <?php
      $maxViews = max(array_column($dailyTrend, 'views')) ?: 1;
      foreach (array_slice($dailyTrend, -14) as $day): // ultimi 14 giorni max
        $pct = round(($day['views'] / $maxViews) * 100);
      ?>
      <div class="flex items-center gap-3 text-xs">
        <span class="text-slate-400 w-20 flex-shrink-0"><?= date('d/m', strtotime($day['stat_date'])) ?></span>
        <div class="flex-1 bg-slate-700 rounded-full h-4 overflow-hidden">
          <div class="bg-emerald-500 h-full rounded-full" style="width:<?= $pct ?>%"></div>
        </div>
        <span class="text-slate-300 w-12 text-right"><?= number_format($day['views']) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Views per categoria -->
  <div class="bg-slate-800 rounded-xl border border-slate-700 p-6">
    <h3 class="font-semibold text-white mb-4">Visualizzazioni per categoria</h3>
    <?php if (empty($viewsByType)): ?>
    <p class="text-slate-400 text-sm">Nessun dato disponibile.</p>
    <?php else: ?>
    <?php
    $totalCatViews = array_sum(array_column($viewsByType, 'views')) ?: 1;
    ?>
    <div class="space-y-4">
      <?php foreach ($viewsByType as $vt): ?>
      <?php $pct = round(($vt['views'] / $totalCatViews) * 100); ?>
      <div>
        <div class="flex items-center justify-between mb-1">
          <span class="text-sm text-slate-300"><?= htmlspecialchars($typeLabels[$vt['entity_type']] ?? $vt['entity_type']) ?></span>
          <div class="flex gap-3 text-xs items-center">
            <span class="bg-emerald-900/50 text-emerald-400 font-bold px-2 py-0.5 rounded-full"><?= $pct ?>% del traffico</span>
            <span class="text-emerald-400"><?= number_format($vt['views']) ?> views</span>
            <span class="text-cyan-400"><?= number_format($vt['uniques']) ?> unici</span>
          </div>
        </div>
        <div class="w-full bg-slate-700 rounded-full h-2">
          <div class="bg-emerald-500 h-2 rounded-full transition-all" style="width:<?= $pct ?>%"></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Top 10 entità -->
<div class="bg-slate-800 rounded-xl border border-slate-700 p-6 mb-8">
  <h3 class="font-semibold text-white mb-4">Top 10 contenuti più visti</h3>
  <?php if (empty($topEntities)): ?>
  <p class="text-slate-400 text-sm">Nessun dato disponibile. Integra il tracking nel frontend per iniziare a raccogliere dati.</p>
  <div class="mt-4 p-4 bg-slate-900 rounded-lg">
    <p class="text-xs text-slate-400 mb-2">Esempio di integrazione frontend (JavaScript):</p>
    <pre class="text-xs text-emerald-400 overflow-x-auto"><code>// Traccia una visualizzazione
fetch('/api/v1/analytics.php', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  body: JSON.stringify({
    entity_type: 'borough',    // borough|company|experience|craft|food|accommodation|restaurant
    entity_id: 'lacedonia',    // slug dell'entità
    page_url: window.location.href,
    referrer: document.referrer,
    session_id: sessionStorage.getItem('mb_session') || crypto.randomUUID()
  })
});</code></pre>
  </div>
  <?php else: ?>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead>
        <tr class="text-left text-xs text-slate-400 border-b border-slate-700">
          <th class="pb-2 pr-4">#</th>
          <th class="pb-2 pr-4">Tipo</th>
          <th class="pb-2 pr-4">ID</th>
          <th class="pb-2 text-right">Views</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-700">
        <?php foreach ($topEntities as $i => $te): ?>
        <tr>
          <td class="py-2 pr-4 text-slate-500"><?= $i + 1 ?></td>
          <td class="py-2 pr-4 text-slate-300"><?= htmlspecialchars($typeLabels[$te['entity_type']] ?? $te['entity_type']) ?></td>
          <td class="py-2 pr-4 text-white font-medium"><?= htmlspecialchars($te['entity_id']) ?></td>
          <td class="py-2 text-right text-emerald-400 font-bold"><?= number_format($te['views']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- Top per Borgo -->
<div class="bg-slate-800 rounded-xl border border-slate-700 p-6 mb-8">
  <h3 class="font-semibold text-white mb-1">Top borghi per visite</h3>
  <p class="text-xs text-slate-400 mb-4">Visite totali aggregate per borgo (pagina borgo + sue esperienze, aziende, ecc.) — ultimi <?= $period ?> giorni</p>
  <?php if (empty($topBoroughs)): ?>
  <p class="text-slate-400 text-sm">Nessun dato disponibile. Le statistiche verranno popolate non appena gli utenti visiteranno le pagine borgo.</p>
  <?php else: ?>
  <?php $maxBoroughViews = max(array_column($topBoroughs, 'total_views')) ?: 1; ?>
  <div class="space-y-3">
    <?php foreach ($topBoroughs as $i => $bv): ?>
    <?php $pct = round(($bv['total_views'] / $maxBoroughViews) * 100); ?>
    <div class="flex items-center gap-3">
      <span class="text-slate-500 text-xs w-5 shrink-0 text-right"><?= $i + 1 ?></span>
      <span class="text-white text-sm font-medium w-28 shrink-0 truncate"><?= htmlspecialchars($bv['entity_id']) ?></span>
      <div class="flex-1 bg-slate-700 rounded-full h-3 overflow-hidden">
        <div class="bg-emerald-500 h-full rounded-full" style="width:<?= $pct ?>%"></div>
      </div>
      <span class="text-emerald-400 text-xs font-bold w-16 text-right"><?= number_format($bv['total_views']) ?> views</span>
      <span class="text-cyan-400 text-xs w-16 text-right"><?= number_format($bv['total_unique']) ?> unici</span>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- Borghi senza visite (ultimi 30 giorni) -->
<div class="bg-slate-800 rounded-xl border border-slate-700 p-6 mb-8">
  <div class="flex items-center gap-3 mb-1">
    <h3 class="font-semibold text-white">Borghi senza visite (ultimi 30 giorni)</h3>
    <?php if (!empty($boroughsNoVisits)): ?>
    <span class="bg-red-900/50 text-red-400 text-xs font-bold px-2.5 py-0.5 rounded-full"><?= count($boroughsNoVisits) ?></span>
    <?php endif; ?>
  </div>
  <p class="text-xs text-slate-400 mb-4">Questi borghi non hanno ricevuto visite registrate negli ultimi 30 giorni. Considera di promuoverli o verificare il tracking.</p>
  <?php if (empty($boroughsNoVisits)): ?>
  <div class="flex items-center gap-2 text-emerald-400 text-sm">
    <span class="text-lg">✓</span>
    <span>Tutti i borghi hanno almeno una visita registrata negli ultimi 30 giorni.</span>
  </div>
  <?php else: ?>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead>
        <tr class="text-left text-xs text-slate-400 border-b border-slate-700">
          <th class="pb-2 pr-4">ID Borgo</th>
          <th class="pb-2 pr-4">Nome</th>
          <th class="pb-2 text-right">Azione</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-700/50">
        <?php foreach ($boroughsNoVisits as $nb): ?>
        <tr>
          <td class="py-2 pr-4 text-slate-400 font-mono text-xs"><?= htmlspecialchars($nb['id']) ?></td>
          <td class="py-2 pr-4 text-white"><?= htmlspecialchars($nb['name']) ?></td>
          <td class="py-2 text-right">
            <a href="borghi.php?edit=<?= urlencode($nb['id']) ?>"
               class="text-xs text-emerald-400 hover:text-emerald-300 underline">Modifica</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- Panoramica contenuti -->
<div class="bg-slate-800 rounded-xl border border-slate-700 p-6">
  <h3 class="font-semibold text-white mb-4">Panoramica contenuti nel database</h3>
  <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
    <?php
    $icons = ['borghi'=>'🏔️','aziende'=>'🏢','esperienze'=>'🎭','artigianato'=>'🏺',
              'prodotti'=>'🧀','ospitalita'=>'🏨','ristorazione'=>'🍽️'];
    foreach ($contentCounts as $label => $count): ?>
    <div class="bg-slate-900 rounded-lg p-3 text-center">
      <div class="text-lg"><?= $icons[$label] ?? '📊' ?></div>
      <div class="text-xl font-bold text-white"><?= $count ?></div>
      <div class="text-xs text-slate-400"><?= ucfirst($label) ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<?php require '_footer.php'; ?>
