<?php
require_once __DIR__ . '/../config/db.php';
requireAdminSession();
$db = getDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = trim($_POST['id'] ?? '');
    if (!$id) { $msg = '❌ ID obbligatorio.'; goto render; }

    $exists = $db->prepare("SELECT id FROM food_products WHERE id=?");
    $exists->execute([$id]);

    $coverPath = handleCoverUpload('cover_image', 'food', $id);
    if ($coverPath) ensureCoverImageColumn($db, 'food_products');

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
    $sel = $stmt->fetch();
}

$pageTitle = 'Prodotti Food';
require '_layout.php';
?>

<?php if ($msg): ?>
  <div class="mb-4 px-4 py-3 rounded-lg text-sm <?= str_starts_with($msg,'✅') ? 'bg-emerald-900/40 border border-emerald-600 text-emerald-300' : 'bg-red-900/40 border border-red-600 text-red-300' ?>">
    <?= htmlspecialchars($msg) ?>
  </div>
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
        echo $inp('slug','Slug');
        echo $inp('name','Nome',  'text', true);
        echo $inp('producer_id','ID Produttore (azienda)');
        echo $inp('borough_id','ID Borgo');
        echo $inp('category','Categoria (es. FORMAGGI)');
        echo $inp('price','Prezzo €','number');
        echo $inp('unit','Unità (es. pezzo ca. 1.2 kg)');
        echo $inp('weight_grams','Peso grammi','number');
        echo $inp('shelf_life_days','Shelf life giorni','number');
        echo $inp('stock_qty','Quantità stock','number');
        echo $inp('min_order_qty','Qtà minima ordine','number');
        echo $inp('origin_protected','Origine protetta (es. Presidio Slow Food)','text',true);
        echo $inp('allergens','Allergeni');
        echo $inp('ingredients','Ingredienti','text',true);
        echo $inp('shipping_notes','Note spedizione','text',true);
        ?>
      </div>
      <?php foreach ([
        ['tagline','Tagline'],
        ['pairing_suggestions','Abbinamenti consigliati'],
        ['description_short','Descrizione breve'],
        ['description_long','Descrizione completa'],
        ['storage_instructions','Istruzioni conservazione'],
      ] as [$n,$l]): ?>
      <div>
        <label class="block text-xs text-slate-400 mb-1"><?= $l ?></label>
        <textarea name="<?= $n ?>" rows="<?= $n==='description_long'?4:2 ?>"
          class="w-full bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600 focus:outline-none focus:border-emerald-500"><?= htmlspecialchars($sel[$n]??'') ?></textarea>
      </div>
      <?php endforeach; ?>
      <div class="flex gap-4 flex-wrap text-sm">
        <?php foreach ([['is_shippable','Spedibile'],['is_active','Attivo'],['is_featured','In evidenza']] as [$n,$l]): ?>
        <label class="flex items-center gap-2 text-slate-300">
          <input type="checkbox" name="<?= $n ?>" <?= !empty($sel[$n])?'checked':'' ?> class="rounded">
          <?= $l ?>
        </label>
        <?php endforeach; ?>
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
    <?php else: ?>
    <div class="bg-slate-800 rounded-xl border border-slate-700 p-12 text-center">
      <div class="text-4xl mb-3">🧀</div>
      <p class="text-slate-400">Seleziona un prodotto o creane uno nuovo.</p>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require '_footer.php'; ?>
