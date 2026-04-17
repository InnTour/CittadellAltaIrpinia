<?php
require_once __DIR__ . '/../config/db.php';
requireAdminSession();
$db  = getDB();
$msg = '';

// ── DELETE ──────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $did = $_GET['delete'];
    $db->prepare("DELETE FROM entity_images WHERE entity_type='poi' AND entity_id=?")->execute([$did]);
    $db->prepare("DELETE FROM points_of_interest WHERE id=?")->execute([$did]);
    header('Location: punti-interesse.php' . (isset($_GET['borough']) ? '?borough=' . urlencode($_GET['borough']) : ''));
    exit;
}

// ── FILTRO BOROUGH ───────────────────────────────────────────
$filterBorough = trim($_GET['borough'] ?? '');
$borghi = $db->query("SELECT id, name_it FROM boroughs ORDER BY name_it")->fetchAll();

$sql = "SELECT p.*, b.name_it AS borough_name FROM points_of_interest p
        LEFT JOIN boroughs b ON b.id = p.borough_id";
$params = [];
if ($filterBorough) {
    $sql .= " WHERE p.borough_id = ?";
    $params[] = $filterBorough;
}
$sql .= " ORDER BY p.borough_id, p.sort_order, p.name_it";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$pois = $stmt->fetchAll();

$pageTitle = 'Punti di Interesse';
require '_layout.php';
?>
<div class="flex-1 overflow-auto p-6">
  <div class="max-w-5xl mx-auto">

    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-bold text-white">Punti di Interesse</h2>
        <p class="text-slate-400 text-sm mt-1">Schede POI per virtual tour Treedis</p>
      </div>
      <div class="flex gap-3 items-center">
        <?php if ($filterBorough): ?>
        <a href="/borghi/<?= htmlspecialchars($filterBorough) ?>/" target="_blank"
           class="text-cyan-400 hover:text-cyan-300 text-sm font-medium border border-cyan-700 px-3 py-2 rounded-lg transition-colors">
          Vedi su MetaBorghi ↗
        </a>
        <?php endif; ?>
        <a href="punti-interesse-edit.php" class="bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors">
          + Nuovo POI
        </a>
      </div>
    </div>

    <?php if ($msg): ?>
    <div class="bg-emerald-900/40 border border-emerald-600 text-emerald-200 rounded-xl p-4 mb-4 text-sm"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <form method="get" class="mb-4 flex gap-3 items-center">
      <select name="borough" onchange="this.form.submit()"
              class="bg-slate-800 border border-slate-600 text-white rounded-lg px-3 py-2 text-sm">
        <option value="">— Tutti i borghi —</option>
        <?php foreach ($borghi as $b): ?>
        <option value="<?= htmlspecialchars($b['id']) ?>"
                <?= $filterBorough === $b['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($b['name_it']) ?>
        </option>
        <?php endforeach; ?>
      </select>
      <span class="text-slate-400 text-sm"><?= count($pois) ?> POI</span>
    </form>

    <div class="bg-slate-800 rounded-2xl border border-slate-700 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-slate-900 text-slate-400 text-xs uppercase tracking-wider">
          <tr>
            <th class="text-left px-4 py-3">Nome IT</th>
            <th class="text-left px-4 py-3">Borough</th>
            <th class="text-left px-4 py-3">Categoria</th>
            <th class="text-left px-4 py-3">Media</th>
            <th class="text-left px-4 py-3">URL Iframe</th>
            <th class="text-left px-4 py-3">Azioni</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-700">
          <?php foreach ($pois as $p): ?>
          <tr class="hover:bg-slate-700/50 transition-colors">
            <td class="px-4 py-3 font-medium text-white"><?= htmlspecialchars($p['name_it']) ?></td>
            <td class="px-4 py-3 text-slate-300"><?= htmlspecialchars($p['borough_name'] ?? $p['borough_id']) ?></td>
            <td class="px-4 py-3 text-slate-400"><?= htmlspecialchars($p['category'] ?? '—') ?></td>
            <td class="px-4 py-3 text-slate-400 text-xs">
              <?= $p['audio_it'] ? '🎧 ' : '' ?>
              <?= $p['video_it'] ? '🎬 ' : '' ?>
              <?= $p['cover_image'] ? '🖼️ ' : '' ?>
            </td>
            <td class="px-4 py-3">
              <code class="text-xs text-emerald-400">/borghi/<?= htmlspecialchars($p['borough_id']) ?>/<?= htmlspecialchars($p['id']) ?></code>
            </td>
            <td class="px-4 py-3 flex gap-2">
              <a href="punti-interesse-edit.php?edit=<?= urlencode($p['id']) ?>"
                 class="text-xs bg-slate-700 hover:bg-slate-600 text-white px-3 py-1 rounded-lg transition-colors">Modifica</a>
              <a href="?delete=<?= urlencode($p['id']) ?><?= $filterBorough ? '&borough=' . urlencode($filterBorough) : '' ?>"
                 onclick="return confirm('Eliminare <?= htmlspecialchars(addslashes($p['name_it'])) ?>?')"
                 class="text-xs bg-red-900/60 hover:bg-red-800 text-red-300 px-3 py-1 rounded-lg transition-colors">Elimina</a>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$pois): ?>
          <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Nessun POI trovato. <a href="punti-interesse-edit.php" class="text-emerald-400 underline">Crea il primo</a>.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require '_footer.php'; ?>
