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

    $toLines = fn($s) => array_filter(array_map('trim', explode("\n", $s ?? '')));
    replaceArray($db, 'craft_material_types', 'craft_id', $id, $toLines($_POST['material_type'] ?? ''));

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
        $sel['material_type'] = implode("\n", fetchArray($db, 'craft_material_types', 'craft_id', $sel['id']));
    }
}

$pageTitle = 'Artigianato';
require '_layout.php';
?>

<?php if ($msg): ?>
  <div class="mb-4 px-4 py-3 rounded-lg text-sm <?= str_starts_with($msg,'✅') ? 'bg-emerald-900/40 border border-emerald-600 text-emerald-300' : 'bg-red-900/40 border border-red-600 text-red-300' ?>">
    <?= htmlspecialchars($msg) ?>
  </div>
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
        $inp = fn($n,$l,$t='text',$full=false) =>
          '<div class="' . ($full?'col-span-2':'') . '">
            <label class="block text-xs text-slate-400 mb-1">'.$l.'</label>
            <input type="'.$t.'" name="'.$n.'" value="'.htmlspecialchars($sel[$n]??'').'"
              class="w-full bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600 focus:outline-none focus:border-emerald-500">
          </div>';
        echo $inp('id','ID');
        echo $inp('name','Nome');
        echo $inp('artisan_id','ID Artigiano/Azienda');
        echo $inp('borough_id','ID Borgo');
        echo $inp('price','Prezzo €','number');
        echo $inp('lead_time_days','Giorni consegna','number');
        echo $inp('weight_grams','Peso (g)','number');
        echo $inp('production_series_qty','Quantità serie','number');
        echo $inp('stock_qty','Giacenza','number');
        echo $inp('rating','Rating','number');
        echo $inp('dimensions','Dimensioni','text',true);
        echo $inp('technique_description','Tecnica lavorazione','text',true);
        ?>
      </div>
      <div>
        <label class="block text-xs text-slate-400 mb-1">Descrizione breve</label>
        <textarea name="description_short" rows="2" class="w-full bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600"><?= htmlspecialchars($sel['description_short']??'') ?></textarea>
      </div>
      <div>
        <label class="block text-xs text-slate-400 mb-1">Descrizione completa</label>
        <textarea name="description_long" rows="4" class="w-full bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600"><?= htmlspecialchars($sel['description_long']??'') ?></textarea>
      </div>
      <div>
        <label class="block text-xs text-slate-400 mb-1">Materiali (uno per riga)</label>
        <textarea name="material_type" rows="3" class="w-full bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600"><?= htmlspecialchars($sel['material_type']??'') ?></textarea>
      </div>
      <div class="flex gap-4 flex-wrap text-sm">
        <?php foreach ([['is_active','Attivo'],['is_unique_piece','Pezzo unico'],['is_custom_order_available','Ordine personalizzato']] as [$n,$l]): ?>
        <label class="flex items-center gap-2 text-slate-300">
          <input type="checkbox" name="<?= $n ?>" <?= !empty($sel[$n])?'checked':'' ?>> <?= $l ?>
        </label>
        <?php endforeach; ?>
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
    <?php else: ?>
    <div class="bg-slate-800 rounded-xl border border-slate-700 p-12 text-center">
      <div class="text-4xl mb-3">🏺</div>
      <p class="text-slate-400">Seleziona un prodotto o creane uno nuovo.</p>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require '_footer.php'; ?>
