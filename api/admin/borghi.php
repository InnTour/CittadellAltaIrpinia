<?php
require_once __DIR__ . '/../config/db.php';
requireAdminSession();
$db = getDB();

$msg = '';

// ── Salvataggio ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = trim($_POST['id'] ?? '');
    if (!$id) { $msg = '❌ ID obbligatorio.'; goto render; }

    $exists = $db->prepare("SELECT id FROM boroughs WHERE id=?");
    $exists->execute([$id]);

    $coverPath = handleCoverUpload('cover_image', 'borough', $id);
    if ($coverPath) ensureCoverImageColumn($db, 'boroughs');

    $fields = [
        'slug'             => trim($_POST['slug']             ?? $id),
        'name'             => trim($_POST['name']             ?? ''),
        'province'         => trim($_POST['province']         ?? ''),
        'region'           => trim($_POST['region']           ?? 'Campania'),
        'population'       => (int)($_POST['population']      ?? 0),
        'altitude_meters'  => (int)($_POST['altitude_meters'] ?? 0),
        'area_km2'         => (float)($_POST['area_km2']      ?? 0),
        'lat'              => (float)($_POST['lat']           ?? 0),
        'lng'              => (float)($_POST['lng']           ?? 0),
        'main_video_url'   => trim($_POST['main_video_url']   ?? ''),
        'virtual_tour_url' => trim($_POST['virtual_tour_url'] ?? ''),
        'description'      => trim($_POST['description']      ?? ''),
        'companies_count'  => (int)($_POST['companies_count'] ?? 0),
        'hero_image_index' => (int)($_POST['hero_image_index'] ?? 0),
        'hero_image_alt'   => trim($_POST['hero_image_alt']   ?? ''),
    ];
    if ($coverPath) $fields['cover_image'] = $coverPath;

    if ($exists->fetch()) {
        $set = implode(',', array_map(fn($k) => "`$k`=?", array_keys($fields)));
        $db->prepare("UPDATE boroughs SET $set WHERE id=?")->execute([...array_values($fields), $id]);
    } else {
        $cols = implode(',', array_map(fn($k) => "`$k`", array_keys($fields)));
        $phs  = implode(',', array_fill(0, count($fields), '?'));
        $db->prepare("INSERT INTO boroughs (id,$cols) VALUES (?,$phs)")->execute([$id, ...array_values($fields)]);
    }

    // Array fields
    $toLines = fn($s) => array_filter(array_map('trim', explode("\n", $s ?? '')));
    replaceArray($db, 'borough_highlights',            'borough_id', $id, $toLines($_POST['highlights']          ?? ''));
    replaceArray($db, 'borough_notable_products',      'borough_id', $id, $toLines($_POST['notable_products']    ?? ''));
    replaceArray($db, 'borough_notable_experiences',   'borough_id', $id, $toLines($_POST['notable_experiences'] ?? ''));
    replaceArray($db, 'borough_notable_restaurants',   'borough_id', $id, $toLines($_POST['notable_restaurants'] ?? ''));

    $msg = '✅ Borgo salvato.';
}

render:

if (isset($_GET['delete'])) {
    $did = $_GET['delete'];
    foreach (['borough_highlights','borough_notable_products','borough_notable_experiences',
              'borough_notable_restaurants','borough_gallery_images'] as $t) {
        $db->prepare("DELETE FROM `$t` WHERE borough_id = ?")->execute([$did]);
    }
    $db->prepare("DELETE FROM boroughs WHERE id=?")->execute([$did]);
    header('Location: borghi.php');
    exit;
}

// ── Lista borghi ──────────────────────────────────────────
$borghi = $db->query("SELECT * FROM boroughs ORDER BY name ASC")->fetchAll();

// ── Borgo selezionato ─────────────────────────────────────
$sel = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM boroughs WHERE id=?");
    $stmt->execute([$_GET['edit']]);
    $sel = $stmt->fetch();
    if ($sel) {
        $sel['highlights']          = implode("\n", fetchArray($db, 'borough_highlights',            'borough_id', $sel['id']));
        $sel['notable_products']    = implode("\n", fetchArray($db, 'borough_notable_products',      'borough_id', $sel['id']));
        $sel['notable_experiences'] = implode("\n", fetchArray($db, 'borough_notable_experiences',   'borough_id', $sel['id']));
        $sel['notable_restaurants'] = implode("\n", fetchArray($db, 'borough_notable_restaurants',   'borough_id', $sel['id']));
    }
}

$pageTitle = 'Borghi';
require '_layout.php';
?>

<?php if ($msg): ?>
  <div class="mb-4 px-4 py-3 rounded-lg text-sm <?= str_starts_with($msg,'✅') ? 'bg-emerald-900/40 border border-emerald-600 text-emerald-300' : 'bg-red-900/40 border border-red-600 text-red-300' ?>">
    <?= htmlspecialchars($msg) ?>
  </div>
<?php endif; ?>

<div class="grid md:grid-cols-3 gap-6">

  <!-- Lista -->
  <div class="md:col-span-1">
    <div class="bg-slate-800 rounded-xl border border-slate-700 overflow-hidden">
      <div class="px-4 py-3 border-b border-slate-700 flex items-center justify-between">
        <h3 class="font-semibold text-sm">Borghi (<?= count($borghi) ?>)</h3>
        <a href="borghi.php?edit=__new__" class="text-xs bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1 rounded-lg">+ Nuovo</a>
      </div>
      <div class="divide-y divide-slate-700 max-h-[65vh] overflow-y-auto">
        <?php foreach ($borghi as $b): ?>
        <a href="borghi.php?edit=<?= urlencode($b['id']) ?>"
           class="flex items-center justify-between px-4 py-3 hover:bg-slate-700 transition-colors <?= (isset($_GET['edit']) && $_GET['edit']===$b['id'] ? 'bg-slate-700' : '') ?>">
          <div>
            <div class="text-sm font-medium text-white"><?= htmlspecialchars($b['name']) ?></div>
            <div class="text-xs text-slate-400"><?= htmlspecialchars($b['province']) ?> · <?= number_format((float)$b['area_km2'],1) ?> km²</div>
          </div>
          <span class="text-xs text-slate-500">›</span>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Form edit -->
  <div class="md:col-span-2">
    <?php if ($sel !== null || isset($_GET['edit'])): ?>
    <form method="POST" enctype="multipart/form-data" class="bg-slate-800 rounded-xl border border-slate-700 p-6 space-y-4">
      <h3 class="font-semibold text-white mb-2"><?= $sel ? 'Modifica: ' . htmlspecialchars($sel['name']) : 'Nuovo borgo' ?></h3>

      <!-- Cover Image Upload -->
      <div>
        <label class="block text-xs text-slate-400 mb-1">Immagine di copertina</label>
        <?php if (!empty($sel['cover_image'])): ?>
          <div class="mb-2"><img src="<?= htmlspecialchars($sel['cover_image']) ?>" alt="Cover" class="h-32 rounded-lg object-cover"></div>
        <?php endif; ?>
        <input type="file" name="cover_image" accept="image/*"
          class="w-full bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600 file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:bg-emerald-600 file:text-white file:text-xs file:cursor-pointer">
      </div>

      <div class="grid grid-cols-2 gap-4">
        <?php
        $f = fn($name, $label, $type='text', $full=false) =>
          '<div class="' . ($full ? 'col-span-2' : '') . '">
            <label class="block text-xs text-slate-400 mb-1">' . $label . '</label>
            <input type="' . $type . '" name="' . $name . '" value="' . htmlspecialchars($sel[$name] ?? '') . '"
              class="w-full bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600 focus:outline-none focus:border-emerald-500">
          </div>';
        echo $f('id', 'ID (slug univoco)');
        echo $f('name', 'Nome');
        echo $f('province', 'Provincia');
        echo $f('region', 'Regione');
        echo $f('population', 'Popolazione', 'number');
        echo $f('altitude_meters', 'Altitudine (m)', 'number');
        echo $f('area_km2', 'Area (km²)', 'number');
        echo $f('lat', 'Latitudine', 'number');
        echo $f('lng', 'Longitudine', 'number');
        echo $f('hero_image_index', 'Indice immagine hero (0-24)', 'number');
        echo $f('hero_image_alt', 'Alt immagine hero', 'text', true);
        echo $f('main_video_url', 'URL Video YouTube embed', 'text', true);
        echo $f('virtual_tour_url', 'URL Tour Virtuale embed', 'text', true);
        ?>
      </div>

      <div>
        <label class="block text-xs text-slate-400 mb-1">Descrizione</label>
        <textarea name="description" rows="4"
          class="w-full bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600 focus:outline-none focus:border-emerald-500"><?= htmlspecialchars($sel['description'] ?? '') ?></textarea>
      </div>

      <?php foreach ([
        ['highlights',          'Highlights (una per riga)'],
        ['notable_products',    'Prodotti tipici (uno per riga)'],
        ['notable_experiences', 'Esperienze (una per riga)'],
        ['notable_restaurants', 'Ristoranti (uno per riga)'],
      ] as [$name, $label]): ?>
      <div>
        <label class="block text-xs text-slate-400 mb-1"><?= $label ?></label>
        <textarea name="<?= $name ?>" rows="3"
          class="w-full bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600 focus:outline-none focus:border-emerald-500"><?= htmlspecialchars($sel[$name] ?? '') ?></textarea>
      </div>
      <?php endforeach; ?>

      <div class="flex gap-3 pt-2">
        <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg transition-colors">Salva</button>
        <?php if ($sel): ?>
        <a href="borghi.php?delete=<?= urlencode($sel['id']) ?>"
           onclick="return confirm('Eliminare questo borgo?')"
           class="px-6 py-2.5 bg-red-700 hover:bg-red-600 text-white text-sm rounded-lg transition-colors">Elimina</a>
        <?php endif; ?>
        <a href="borghi.php" class="px-6 py-2.5 bg-slate-600 hover:bg-slate-500 text-white text-sm rounded-lg transition-colors">Annulla</a>
      </div>
    </form>
    <?php else: ?>
    <div class="bg-slate-800 rounded-xl border border-slate-700 p-12 text-center">
      <div class="text-4xl mb-3">🏔️</div>
      <p class="text-slate-400">Seleziona un borgo dalla lista per modificarlo,<br>oppure crea un nuovo borgo.</p>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require '_footer.php'; ?>
