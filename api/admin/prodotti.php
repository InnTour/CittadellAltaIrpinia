<?php
require_once __DIR__ . '/../config/db.php';
requireAdminSession();
$db = getDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = trim($_POST['id'] ?? '');
    if (!$id) { $msg = '❌ ID obbligatorio.'; goto render; }

    $required_fields = ['name' => 'Nome', 'category' => 'Categoria', 'producer_id' => 'ID Produttore', 'borough_id' => 'ID Borgo'];
    foreach ($required_fields as $field => $fieldLabel) {
        if (empty(trim($_POST[$field] ?? ''))) {
            $msg = "❌ Il campo \"$fieldLabel\" è obbligatorio.";
            goto render;
        }
    }
    if (!isset($_POST['price']) || trim($_POST['price']) === '') {
        $msg = '❌ Il campo "Prezzo" è obbligatorio.';
        goto render;
    }

    $exists = $db->prepare("SELECT id FROM food_products WHERE id=?");
    $exists->execute([$id]);

    $coverPath = handleCoverUpload('cover_image', 'food', $id);
    if ($coverPath) ensureCoverImageColumn($db, 'food_products');

    /* Ensure extra columns exist */
    foreach ([
        'rating'              => 'REAL DEFAULT 0',
        'reviews_count'       => 'INTEGER DEFAULT 0',
        'origin_region'       => 'TEXT DEFAULT ""',
        'tags'                => 'TEXT DEFAULT "[]"',
        'traceability_chain'  => 'TEXT DEFAULT "[]"',
        'cover_video_url'     => 'TEXT DEFAULT NULL',
        'main_video_url'      => 'TEXT DEFAULT NULL',
    ] as $col => $colDef) {
        try { $db->exec("ALTER TABLE food_products ADD COLUMN `$col` $colDef"); } catch (\Exception $e) {}
    }

    /* Tags: textarea one per line -> JSON array */
    $tagsLines = array_values(array_filter(array_map('trim', explode("\n", $_POST['tags'] ?? ''))));
    $tagsJson  = json_encode($tagsLines, JSON_UNESCAPED_UNICODE);

    /* Traceability chain: dynamic rows -> JSON array of objects */
    $trLabels = $_POST['trace_label'] ?? [];
    $trDescs  = $_POST['trace_desc']  ?? [];
    $trChain  = [];
    foreach ($trLabels as $i => $lbl) {
        $lbl = trim($lbl);
        $dsc = trim($trDescs[$i] ?? '');
        if ($lbl === '' && $dsc === '') continue;
        $trChain[] = ['label' => $lbl, 'description' => $dsc];
    }
    $trJson = json_encode($trChain, JSON_UNESCAPED_UNICODE);

    $f = [
        'slug'                => trim($_POST['slug']                ?? $id),
        'name'                => trim($_POST['name']                ?? ''),
        'producer_id'         => trim($_POST['producer_id']         ?? ''),
        'borough_id'          => trim($_POST['borough_id']          ?? ''),
        'category'            => trim($_POST['category']            ?? ''),
        'description_short'   => trim($_POST['description_short']   ?? ''),
        'description_long'    => trim($_POST['description_long']    ?? ''),
        'tagline'             => trim($_POST['tagline']             ?? ''),
        'pairing_suggestions' => trim($_POST['pairing_suggestions'] ?? ''),
        'price'               => (float)($_POST['price']            ?? 0),
        'unit'                => trim($_POST['unit']                ?? ''),
        'weight_grams'        => (int)($_POST['weight_grams']       ?? 0),
        'shelf_life_days'     => (int)($_POST['shelf_life_days']    ?? 0),
        'storage_instructions'=> trim($_POST['storage_instructions'] ?? ''),
        'origin_protected'    => trim($_POST['origin_protected']    ?? ''),
        'allergens'           => trim($_POST['allergens']           ?? ''),
        'ingredients'         => trim($_POST['ingredients']         ?? ''),
        'stock_qty'           => (int)($_POST['stock_qty']          ?? 0),
        'min_order_qty'       => (int)($_POST['min_order_qty']      ?? 1),
        'is_shippable'        => isset($_POST['is_shippable'])  ? 1 : 0,
        'shipping_notes'      => trim($_POST['shipping_notes']      ?? ''),
        'is_active'           => isset($_POST['is_active'])    ? 1 : 0,
        'is_featured'         => isset($_POST['is_featured'])  ? 1 : 0,
        'rating'              => (float)($_POST['rating']           ?? 0),
        'reviews_count'       => (int)($_POST['reviews_count']      ?? 0),
        'origin_region'       => trim($_POST['origin_region']       ?? ''),
        'tags'                => $tagsJson,
        'traceability_chain'  => $trJson,
        'cover_video_url'     => trim($_POST['cover_video_url']     ?? ''),
        'main_video_url'      => trim($_POST['main_video_url']      ?? ''),
    ];
    if ($coverPath) $f['cover_image'] = $coverPath;

    if ($exists->fetch()) {
        $set = implode(',', array_map(fn($k) => "`$k`=?", array_keys($f)));
        $db->prepare("UPDATE food_products SET $set WHERE id=?")->execute([...array_values($f), $id]);
    } else {
        $cols = implode(',', array_map(fn($k) => "`$k`", array_keys($f)));
        $phs  = implode(',', array_fill(0, count($f), '?'));
        $db->prepare("INSERT INTO food_products (id,$cols) VALUES (?,$phs)")->execute([$id, ...array_values($f)]);
    }

    /* --- Image Gallery --- */
    processGalleryFromPost($db, 'food_product', $id, 'new_images');

    $msg = '✅ Prodotto salvato.';
}
render:

if (isset($_GET['delete'])) {
    $db->prepare("DELETE FROM food_products WHERE id=?")->execute([$_GET['delete']]);
    header('Location: prodotti.php');
    exit;
}

try {
    $list = $db->query("SELECT id, name, borough_id, category FROM food_products ORDER BY name ASC")->fetchAll();
} catch (PDOException $e) {
    $pageTitle = 'Prodotti Food';
    require '_layout.php';
    echo '<div class="bg-red-900/40 border border-red-600 rounded-xl p-6 text-red-300">
        <p class="font-bold mb-2">❌ Tabella <code>food_products</code> non trovata nel database.</p>
        <p class="text-sm mb-3">Esegui prima il seed per creare le tabelle e inserire i dati di esempio.</p>
        <a href="seed_lacedonia.php" class="inline-block px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm rounded-lg">🌱 Esegui Seed Lacedonia</a>
    </div>';
    require '_footer.php';
    exit;
}

$sel  = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM food_products WHERE id=?");
    $stmt->execute([$_GET['edit']]);
    $sel = $stmt->fetch() ?: null;
    if ($sel) {
        /* Image gallery */
        $sel['_images'] = fetchEntityImages($db, 'food_product', $sel['id']);

        /* Tags: JSON -> newline-separated for textarea */
        $tagsArr = json_decode($sel['tags'] ?? '[]', true);
        $sel['_tags_text'] = is_array($tagsArr) ? implode("\n", $tagsArr) : '';

        /* Traceability chain: JSON -> array of objects */
        $sel['_traceability'] = json_decode($sel['traceability_chain'] ?? '[]', true) ?: [];
    }
}

$pageTitle = 'Prodotti Food';
require '_layout.php';
?>

<?php if ($msg): ?>
  <?= adminMsg($msg) ?>
<?php endif; ?>

<div class="grid md:grid-cols-3 gap-6">
  <!-- Lista -->
  <div class="md:col-span-1 bg-slate-800 rounded-xl border border-slate-700 overflow-hidden">
    <div class="px-4 py-3 border-b border-slate-700 flex items-center justify-between">
      <h3 class="font-semibold text-sm">Prodotti Food (<?= count($list) ?>)</h3>
      <a href="prodotti.php?edit=__new__" class="text-xs bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1 rounded-lg">+ Nuovo</a>
    </div>
    <div class="divide-y divide-slate-700 max-h-[65vh] overflow-y-auto">
      <?php foreach ($list as $item): ?>
      <a href="prodotti.php?edit=<?= urlencode($item['id']) ?>"
         class="flex items-center justify-between px-4 py-3 hover:bg-slate-700 transition-colors <?= (isset($_GET['edit']) && $_GET['edit']===$item['id'] ? 'bg-slate-700' : '') ?>">
        <div>
          <div class="text-sm font-medium text-white"><?= htmlspecialchars($item['name']) ?></div>
          <div class="text-xs text-slate-400"><?= htmlspecialchars($item['borough_id']) ?> · <?= htmlspecialchars($item['category']) ?></div>
        </div>
        <span class="text-xs text-slate-500">›</span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Form -->
  <div class="md:col-span-2">
    <?php if ($sel !== null || isset($_GET['edit'])): ?>
    <form method="POST" enctype="multipart/form-data" class="bg-slate-800 rounded-xl border border-slate-700 p-6 space-y-4">
      <h3 class="font-semibold text-white mb-2"><?= $sel ? 'Modifica: ' . htmlspecialchars($sel['name']) : 'Nuovo prodotto food' ?></h3>

      <!-- Cover Image -->
      <?= adminCoverImage($sel) ?>

      <!-- Image Gallery -->
      <?= adminImageGallery('new_images', $sel['_images'] ?? [], 'Galleria immagini prodotto') ?>

      <div class="grid grid-cols-2 gap-4">
        <?php
        echo adminInput('id', 'ID', $sel, 'text', false, '', true);
        echo adminInput('slug', 'Slug', $sel);
        echo adminInput('name', 'Nome', $sel, 'text', true, '', true);
        echo adminInput('producer_id', 'ID Produttore (azienda)', $sel, 'text', false, '', true);
        echo adminInput('borough_id', 'ID Borgo', $sel, 'text', false, '', true);
        echo adminInput('category', 'Categoria (es. FORMAGGI)', $sel, 'text', false, '', true);
        echo adminInput('price', 'Prezzo €', $sel, 'number', false, '0.01', true);
        echo adminInput('unit', 'Unità (es. pezzo ca. 1.2 kg)', $sel);
        echo adminInput('weight_grams', 'Peso grammi', $sel, 'number');
        echo adminInput('shelf_life_days', 'Shelf life giorni', $sel, 'number');
        echo adminInput('stock_qty', 'Quantità stock', $sel, 'number');
        echo adminInput('min_order_qty', 'Qtà minima ordine', $sel, 'number');
        echo adminInput('rating', 'Rating', $sel, 'number', false, '0.1');
        echo adminInput('reviews_count', 'Recensioni', $sel, 'number');
        echo adminInput('origin_region', 'Regione di origine', $sel, 'text', true);
        echo adminInput('origin_protected', 'Origine protetta (es. Presidio Slow Food)', $sel, 'text', true);
        echo adminInput('allergens', 'Allergeni', $sel);
        echo adminInput('ingredients', 'Ingredienti', $sel, 'text', true);
        echo adminInput('shipping_notes', 'Note spedizione', $sel, 'text', true);
        echo adminInput('cover_video_url', 'Video copertina (YouTube/locale)', $sel, 'text', true);
        echo adminInput('main_video_url', 'URL Video embed', $sel, 'text', true);
        ?>
      </div>

      <?= adminTextarea('tagline', 'Tagline', $sel, 2) ?>
      <?= adminTextarea('pairing_suggestions', 'Abbinamenti consigliati', $sel, 2) ?>
      <?= adminTextarea('description_short', 'Descrizione breve', $sel, 2) ?>
      <?= adminTextarea('description_long', 'Descrizione completa', $sel, 4) ?>
      <?= adminTextarea('storage_instructions', 'Istruzioni conservazione', $sel, 2) ?>

      <!-- Tags (one per line) -->
      <div>
        <label class="block text-xs text-slate-400 mb-1">Tag (uno per riga)</label>
        <textarea name="tags" rows="3"
          class="w-full bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600 focus:outline-none focus:border-emerald-500"><?= htmlspecialchars($sel['_tags_text'] ?? '') ?></textarea>
      </div>

      <!-- Traceability Chain -->
      <fieldset class="border border-slate-600 rounded-lg p-4 space-y-3">
        <legend class="text-xs text-slate-400 px-2">Catena di tracciabilità</legend>
        <div id="trace-list">
          <?php
          $traces = $sel['_traceability'] ?? [];
          if (empty($traces)) $traces = [['label' => '', 'description' => '']];
          foreach ($traces as $i => $tr): ?>
          <div class="grid grid-cols-[1fr_2fr_auto] gap-2 mb-2 trace-row">
            <input type="text" name="trace_label[]" value="<?= htmlspecialchars($tr['label'] ?? '') ?>" placeholder="Etichetta"
              class="bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600 focus:outline-none focus:border-emerald-500">
            <input type="text" name="trace_desc[]" value="<?= htmlspecialchars($tr['description'] ?? '') ?>" placeholder="Descrizione"
              class="bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600 focus:outline-none focus:border-emerald-500">
            <button type="button" onclick="this.closest('.trace-row').remove()"
              class="px-2 text-red-400 hover:text-red-300 text-lg">&times;</button>
          </div>
          <?php endforeach; ?>
        </div>
        <button type="button" onclick="addTraceRow()" class="text-xs bg-slate-600 hover:bg-slate-500 text-white px-3 py-1 rounded-lg">+ Aggiungi fase</button>
      </fieldset>

      <div class="flex gap-4 flex-wrap text-sm">
        <?= adminCheckbox('is_shippable', 'Spedibile', $sel) ?>
        <?= adminCheckbox('is_active', 'Attivo', $sel) ?>
        <?= adminCheckbox('is_featured', 'In evidenza', $sel) ?>
      </div>

      <div class="flex gap-3 pt-2">
        <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg transition-colors">Salva</button>
        <?php if ($sel): ?>
        <a href="prodotti.php?delete=<?= urlencode($sel['id']) ?>"
           onclick="return confirm('Eliminare questo prodotto?')"
           class="px-6 py-2.5 bg-red-700 hover:bg-red-600 text-white text-sm rounded-lg transition-colors">Elimina</a>
        <?php endif; ?>
        <a href="prodotti.php" class="px-6 py-2.5 bg-slate-600 hover:bg-slate-500 text-white text-sm rounded-lg transition-colors">Annulla</a>
      </div>
    </form>

    <script>
    function addTraceRow() {
      const html = `<div class="grid grid-cols-[1fr_2fr_auto] gap-2 mb-2 trace-row">
        <input type="text" name="trace_label[]" placeholder="Etichetta"
          class="bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600 focus:outline-none focus:border-emerald-500">
        <input type="text" name="trace_desc[]" placeholder="Descrizione"
          class="bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600 focus:outline-none focus:border-emerald-500">
        <button type="button" onclick="this.closest('.trace-row').remove()"
          class="px-2 text-red-400 hover:text-red-300 text-lg">&times;</button>
      </div>`;
      document.getElementById('trace-list').insertAdjacentHTML('beforeend', html);
    }
    </script>

    <?php else: ?>
    <div class="bg-slate-800 rounded-xl border border-slate-700 p-12 text-center">
      <div class="text-4xl mb-3">🧀</div>
      <p class="text-slate-400">Seleziona un prodotto o creane uno nuovo.</p>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require '_footer.php'; ?>
