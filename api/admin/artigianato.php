<?php
require_once __DIR__ . '/../config/db.php';
requireAdminSession();
$db = getDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = trim($_POST['id'] ?? '');
    if (!$id) { $msg = '❌ ID obbligatorio.'; goto render; }

    $exists = $db->prepare("SELECT id FROM craft_products WHERE id=?");
    $exists->execute([$id]);

    $coverPath = handleCoverUpload('cover_image', 'craft', $id);
    if ($coverPath) ensureCoverImageColumn($db, 'craft_products');

    $f = [
        'slug'                      => trim($_POST['slug']                        ?? $id),
        'name'                      => trim($_POST['name']                        ?? ''),
        'description_short'         => trim($_POST['description_short']           ?? ''),
        'description_long'          => trim($_POST['description_long']            ?? ''),
        'price'                     => (float)($_POST['price']                   ?? 0),
        'is_custom_order_available' => isset($_POST['is_custom_order_available'])  ? 1 : 0,
        'lead_time_days'            => (int)($_POST['lead_time_days']             ?? 0),
        'technique_description'     => trim($_POST['technique_description']       ?? ''),
        'dimensions'                => trim($_POST['dimensions']                  ?? ''),
        'weight_grams'              => (int)($_POST['weight_grams']               ?? 0),
        'artisan_id'                => trim($_POST['artisan_id']                  ?? ''),
        'borough_id'                => trim($_POST['borough_id']                  ?? ''),
        'is_unique_piece'           => isset($_POST['is_unique_piece'])            ? 1 : 0,
        'production_series_qty'     => (int)($_POST['production_series_qty']      ?? 0),
        'rating'                    => (float)($_POST['rating']                   ?? 0),
        'reviews_count'             => (int)($_POST['reviews_count']              ?? 0),
        'stock_qty'                 => (int)($_POST['stock_qty']                  ?? 0),
        'is_active'                 => isset($_POST['is_active'])                  ? 1 : 0,
    ];
    if ($coverPath) $f['cover_image'] = $coverPath;

    if ($exists->fetch()) {
        $set = implode(',', array_map(fn($k) => "`$k`=?", array_keys($f)));
        $db->prepare("UPDATE craft_products SET $set WHERE id=?")->execute([...array_values($f), $id]);
    } else {
        $cols = implode(',', array_map(fn($k) => "`$k`", array_keys($f)));
        $phs  = implode(',', array_fill(0, count($f), '?'));
        $db->prepare("INSERT INTO craft_products (id,$cols) VALUES (?,$phs)")->execute([$id, ...array_values($f)]);
    }

    /* --- Image Gallery --- */
    processGalleryFromPost($db, 'craft', $id, 'new_images');

    /* --- Material Types (one per line) --- */
    $toLines = fn($s) => array_filter(array_map('trim', explode("\n", $s ?? '')));
    replaceArray($db, 'craft_material_types', 'craft_id', $id, $toLines($_POST['material_type'] ?? ''));

    /* --- Process Steps (title + description) --- */
    $db->prepare("DELETE FROM craft_process_steps WHERE craft_id = ?")->execute([$id]);
    $pTitles = $_POST['process_title'] ?? [];
    $pDescs  = $_POST['process_desc']  ?? [];
    $stmtStep = $db->prepare("INSERT INTO craft_process_steps (craft_id, title, description, sort_order) VALUES (?, ?, ?, ?)");
    foreach ($pTitles as $i => $t) {
        $t = trim($t);
        $d = trim($pDescs[$i] ?? '');
        if ($t === '' && $d === '') continue;
        $stmtStep->execute([$id, $t, $d, $i]);
    }

    /* --- Customization Options (name + values + price_modifier) --- */
    $db->prepare("DELETE FROM craft_customization_options WHERE craft_id = ?")->execute([$id]);
    $cNames  = $_POST['cust_name']  ?? [];
    $cValues = $_POST['cust_values'] ?? [];
    $cPrices = $_POST['cust_price']  ?? [];
    $stmtCust = $db->prepare("INSERT INTO craft_customization_options (craft_id, name, `values`, price_modifier) VALUES (?, ?, ?, ?)");
    foreach ($cNames as $i => $cn) {
        $cn = trim($cn);
        $cv = trim($cValues[$i] ?? '');
        $cp = (float)($cPrices[$i] ?? 0);
        if ($cn === '') continue;
        $stmtCust->execute([$id, $cn, $cv, $cp]);
    }

    $msg = '✅ Prodotto artigianale salvato.';
}
render:

if (isset($_GET['delete'])) {
    $did = $_GET['delete'];
    foreach (['craft_material_types','craft_customization_options','craft_process_steps'] as $t) {
        $db->prepare("DELETE FROM `$t` WHERE craft_id = ?")->execute([$did]);
    }
    $db->prepare("DELETE FROM craft_products WHERE id=?")->execute([$did]);
    header('Location: artigianato.php');
    exit;
}

$list = $db->query("SELECT id, name, borough_id, price FROM craft_products ORDER BY name ASC")->fetchAll();
$sel = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM craft_products WHERE id=?");
    $stmt->execute([$_GET['edit']]);
    $sel = $stmt->fetch();
    if ($sel) {
        /* Material types */
        $sel['material_type'] = implode("\n", fetchArray($db, 'craft_material_types', 'craft_id', $sel['id']));

        /* Image gallery */
        $sel['_images'] = fetchEntityImages($db, 'craft', $sel['id']);

        /* Process steps */
        $stPs = $db->prepare("SELECT title, description FROM craft_process_steps WHERE craft_id = ? ORDER BY sort_order ASC");
        $stPs->execute([$sel['id']]);
        $sel['_process_steps'] = $stPs->fetchAll();

        /* Customization options */
        $stCo = $db->prepare("SELECT name, `values`, price_modifier FROM craft_customization_options WHERE craft_id = ? ORDER BY rowid ASC");
        $stCo->execute([$sel['id']]);
        $sel['_customizations'] = $stCo->fetchAll();
    }
}

$pageTitle = 'Artigianato';
require '_layout.php';
?>

<?php if ($msg): ?>
  <?= adminMsg($msg) ?>
<?php endif; ?>

<div class="grid md:grid-cols-3 gap-6">
  <div class="md:col-span-1 bg-slate-800 rounded-xl border border-slate-700 overflow-hidden">
    <div class="px-4 py-3 border-b border-slate-700 flex items-center justify-between">
      <h3 class="font-semibold text-sm">Prodotti (<?= count($list) ?>)</h3>
      <a href="artigianato.php?edit=__new__" class="text-xs bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1 rounded-lg">+ Nuovo</a>
    </div>
    <div class="divide-y divide-slate-700 max-h-[65vh] overflow-y-auto">
      <?php foreach ($list as $item): ?>
      <a href="artigianato.php?edit=<?= urlencode($item['id']) ?>"
         class="flex items-center justify-between px-4 py-3 hover:bg-slate-700 transition-colors <?= (isset($_GET['edit']) && $_GET['edit']===$item['id'] ? 'bg-slate-700' : '') ?>">
        <div>
          <div class="text-sm font-medium text-white"><?= htmlspecialchars($item['name']) ?></div>
          <div class="text-xs text-slate-400"><?= htmlspecialchars($item['borough_id']) ?> · €<?= number_format((float)$item['price'],0) ?></div>
        </div>
        <span class="text-xs text-slate-500">›</span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="md:col-span-2">
    <?php if ($sel !== null || isset($_GET['edit'])): ?>
    <form method="POST" enctype="multipart/form-data" class="bg-slate-800 rounded-xl border border-slate-700 p-6 space-y-4">
      <h3 class="font-semibold text-white mb-2"><?= $sel ? htmlspecialchars($sel['name']) : 'Nuovo prodotto' ?></h3>

      <!-- Cover Image -->
      <?= adminCoverImage($sel) ?>

      <!-- Image Gallery -->
      <?= adminImageGallery('new_images', $sel['_images'] ?? [], 'Galleria immagini prodotto') ?>

      <div class="grid grid-cols-2 gap-4">
        <?php
        echo adminInput('id', 'ID', $sel);
        echo adminInput('name', 'Nome', $sel);
        echo adminInput('slug', 'Slug', $sel);
        echo adminInput('artisan_id', 'ID Artigiano/Azienda', $sel);
        echo adminInput('borough_id', 'ID Borgo', $sel);
        echo adminInput('price', 'Prezzo €', $sel, 'number');
        echo adminInput('lead_time_days', 'Giorni consegna', $sel, 'number');
        echo adminInput('weight_grams', 'Peso (g)', $sel, 'number');
        echo adminInput('production_series_qty', 'Quantità serie', $sel, 'number');
        echo adminInput('stock_qty', 'Giacenza', $sel, 'number');
        echo adminInput('rating', 'Rating', $sel, 'number', false, '0.1');
        echo adminInput('reviews_count', 'Recensioni', $sel, 'number');
        echo adminInput('dimensions', 'Dimensioni', $sel, 'text', true);
        echo adminInput('technique_description', 'Tecnica lavorazione', $sel, 'text', true);
        ?>
      </div>

      <?= adminTextarea('description_short', 'Descrizione breve', $sel, 2) ?>
      <?= adminTextarea('description_long', 'Descrizione completa', $sel, 4) ?>
      <?= adminTextarea('material_type', 'Materiali (uno per riga)', $sel, 3) ?>

      <!-- Process Steps -->
      <fieldset class="border border-slate-600 rounded-lg p-4 space-y-3">
        <legend class="text-xs text-slate-400 px-2">Fasi di lavorazione</legend>
        <div id="process-steps-list">
          <?php
          $steps = $sel['_process_steps'] ?? [];
          if (empty($steps)) $steps = [['title' => '', 'description' => '']];
          foreach ($steps as $i => $step): ?>
          <div class="grid grid-cols-[1fr_2fr_auto] gap-2 mb-2 process-step-row">
            <input type="text" name="process_title[]" value="<?= htmlspecialchars($step['title'] ?? '') ?>" placeholder="Titolo fase"
              class="bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600 focus:outline-none focus:border-emerald-500">
            <input type="text" name="process_desc[]" value="<?= htmlspecialchars($step['description'] ?? '') ?>" placeholder="Descrizione"
              class="bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600 focus:outline-none focus:border-emerald-500">
            <button type="button" onclick="this.closest('.process-step-row').remove()"
              class="px-2 text-red-400 hover:text-red-300 text-lg">&times;</button>
          </div>
          <?php endforeach; ?>
        </div>
        <button type="button" onclick="addProcessStep()" class="text-xs bg-slate-600 hover:bg-slate-500 text-white px-3 py-1 rounded-lg">+ Aggiungi fase</button>
      </fieldset>

      <!-- Customization Options -->
      <fieldset class="border border-slate-600 rounded-lg p-4 space-y-3">
        <legend class="text-xs text-slate-400 px-2">Opzioni personalizzazione</legend>
        <div id="cust-options-list">
          <?php
          $custs = $sel['_customizations'] ?? [];
          if (empty($custs)) $custs = [['name' => '', 'values' => '', 'price_modifier' => 0]];
          foreach ($custs as $i => $c): ?>
          <div class="grid grid-cols-[1fr_2fr_6rem_auto] gap-2 mb-2 cust-row">
            <input type="text" name="cust_name[]" value="<?= htmlspecialchars($c['name'] ?? '') ?>" placeholder="Nome opzione"
              class="bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600 focus:outline-none focus:border-emerald-500">
            <input type="text" name="cust_values[]" value="<?= htmlspecialchars($c['values'] ?? '') ?>" placeholder="Valori (separati da virgola)"
              class="bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600 focus:outline-none focus:border-emerald-500">
            <input type="number" name="cust_price[]" value="<?= htmlspecialchars($c['price_modifier'] ?? 0) ?>" placeholder="€ +/-" step="0.01"
              class="bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600 focus:outline-none focus:border-emerald-500">
            <button type="button" onclick="this.closest('.cust-row').remove()"
              class="px-2 text-red-400 hover:text-red-300 text-lg">&times;</button>
          </div>
          <?php endforeach; ?>
        </div>
        <button type="button" onclick="addCustOption()" class="text-xs bg-slate-600 hover:bg-slate-500 text-white px-3 py-1 rounded-lg">+ Aggiungi opzione</button>
      </fieldset>

      <div class="flex gap-4 flex-wrap text-sm">
        <?= adminCheckbox('is_active', 'Attivo', $sel) ?>
        <?= adminCheckbox('is_unique_piece', 'Pezzo unico', $sel) ?>
        <?= adminCheckbox('is_custom_order_available', 'Ordine personalizzato', $sel) ?>
      </div>

      <div class="flex gap-3 pt-2">
        <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg">Salva</button>
        <?php if ($sel): ?>
        <a href="artigianato.php?delete=<?= urlencode($sel['id']) ?>"
           onclick="return confirm('Eliminare questo prodotto?')"
           class="px-6 py-2.5 bg-red-700 hover:bg-red-600 text-white text-sm rounded-lg transition-colors">Elimina</a>
        <?php endif; ?>
        <a href="artigianato.php" class="px-6 py-2.5 bg-slate-600 hover:bg-slate-500 text-white text-sm rounded-lg">Annulla</a>
      </div>
    </form>

    <script>
    function addProcessStep() {
      const html = `<div class="grid grid-cols-[1fr_2fr_auto] gap-2 mb-2 process-step-row">
        <input type="text" name="process_title[]" placeholder="Titolo fase"
          class="bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600 focus:outline-none focus:border-emerald-500">
        <input type="text" name="process_desc[]" placeholder="Descrizione"
          class="bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600 focus:outline-none focus:border-emerald-500">
        <button type="button" onclick="this.closest('.process-step-row').remove()"
          class="px-2 text-red-400 hover:text-red-300 text-lg">&times;</button>
      </div>`;
      document.getElementById('process-steps-list').insertAdjacentHTML('beforeend', html);
    }
    function addCustOption() {
      const html = `<div class="grid grid-cols-[1fr_2fr_6rem_auto] gap-2 mb-2 cust-row">
        <input type="text" name="cust_name[]" placeholder="Nome opzione"
          class="bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600 focus:outline-none focus:border-emerald-500">
        <input type="text" name="cust_values[]" placeholder="Valori (separati da virgola)"
          class="bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600 focus:outline-none focus:border-emerald-500">
        <input type="number" name="cust_price[]" placeholder="€ +/-" step="0.01"
          class="bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600 focus:outline-none focus:border-emerald-500">
        <button type="button" onclick="this.closest('.cust-row').remove()"
          class="px-2 text-red-400 hover:text-red-300 text-lg">&times;</button>
      </div>`;
      document.getElementById('cust-options-list').insertAdjacentHTML('beforeend', html);
    }
    </script>

    <?php else: ?>
    <div class="bg-slate-800 rounded-xl border border-slate-700 p-12 text-center">
      <div class="text-4xl mb-3">🏺</div>
      <p class="text-slate-400">Seleziona un prodotto o creane uno nuovo.</p>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require '_footer.php'; ?>
