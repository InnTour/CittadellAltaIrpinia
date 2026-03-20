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
<div class="mb-6 flex gap-2">
  <?php foreach ([7 => '7 giorni', 30 => '30 giorni', 90 => '90 giorni', 365 => '1 anno'] as $p => $label): ?>
  <a href="statistiche.php?period=<?= $p ?>"
     class="px-4 py-2 text-sm rounded-lg transition-colors <?= $period === $p ? 'bg-emerald-600 text-white' : 'bg-slate-700 text-slate-300 hover:bg-slate-600' ?>">
    <?= $label ?>
  </a>
  <?php endforeach; ?>
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
    <div class="space-y-3">
      <?php foreach ($viewsByType as $vt): ?>
      <div class="flex items-center justify-between">
        <span class="text-sm text-slate-300"><?= htmlspecialchars($typeLabels[$vt['entity_type']] ?? $vt['entity_type']) ?></span>
        <div class="flex gap-4 text-xs">
          <span class="text-emerald-400"><?= number_format($vt['views']) ?> views</span>
          <span class="text-cyan-400"><?= number_format($vt['uniques']) ?> unici</span>
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
