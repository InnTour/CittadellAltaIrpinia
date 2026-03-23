<?php
require_once __DIR__ . '/../config/db.php';
requireAdminSession();
$db = getDB();
$msg = '';

// Assicura che le colonne aggiunte dopo la migrazione iniziale esistano
ensureTableColumns($db, 'restaurants', [
    'social_linkedin'     => "TEXT DEFAULT NULL",
    'certifications'      => "TEXT DEFAULT NULL",
    'founder_name'        => "VARCHAR(200) DEFAULT NULL",
    'founder_quote'       => "TEXT DEFAULT NULL",
    'rating'              => "DECIMAL(3,2) DEFAULT 0",
    'reviews_count'       => "INT DEFAULT 0",
    'tier'                => "VARCHAR(20) DEFAULT 'BASE'",
    'is_verified'         => "TINYINT(1) DEFAULT 0",
    'cover_image'         => "VARCHAR(500) DEFAULT NULL",
    'main_video_url'      => "TEXT DEFAULT NULL",
    'virtual_tour_url'    => "TEXT DEFAULT NULL",
]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = trim($_POST['id'] ?? '');
    if (!$id) { $msg = '❌ ID obbligatorio.'; goto render; }

    $exists = $db->prepare("SELECT id FROM restaurants WHERE id=?");
    $exists->execute([$id]);

    $coverPath = handleCoverUpload('cover_image', 'restaurant', $id);
    if ($coverPath) ensureCoverImageColumn($db, 'restaurants');

    $f = [
        'slug'                 => trim($_POST['slug']                 ?? $id),
        'name'                 => trim($_POST['name']                 ?? ''),
        'type'                 => $_POST['type']                      ?? 'RISTORANTE',
        'borough_id'           => trim($_POST['borough_id']           ?? ''),
        'address_full'         => trim($_POST['address_full']         ?? ''),
        'lat'                  => (float)($_POST['lat']               ?? 0),
        'lng'                  => (float)($_POST['lng']               ?? 0),
        'description_short'    => trim($_POST['description_short']    ?? ''),
        'description_long'     => trim($_POST['description_long']     ?? ''),
        'tagline'              => trim($_POST['tagline']              ?? ''),
        'cuisine_type'         => trim($_POST['cuisine_type']         ?? ''),
        'price_range'          => $_POST['price_range']               ?? 'MEDIO',
        'seats_indoor'         => (int)($_POST['seats_indoor']        ?? 0),
        'seats_outdoor'        => (int)($_POST['seats_outdoor']       ?? 0),
        'opening_hours'        => trim($_POST['opening_hours']        ?? ''),
        'closing_day'          => trim($_POST['closing_day']          ?? ''),
        'specialties'          => trim($_POST['specialties']          ?? ''),
        'menu_highlights'      => trim($_POST['menu_highlights']      ?? ''),
        'contact_email'        => trim($_POST['contact_email']        ?? ''),
        'contact_phone'        => trim($_POST['contact_phone']        ?? ''),
        'website_url'          => trim($_POST['website_url']          ?? ''),
        'social_instagram'     => trim($_POST['social_instagram']     ?? ''),
        'social_facebook'      => trim($_POST['social_facebook']      ?? ''),
        'social_linkedin'      => trim($_POST['social_linkedin']      ?? ''),
        'booking_url'          => trim($_POST['booking_url']          ?? ''),
        'main_video_url'       => trim($_POST['main_video_url']       ?? ''),
        'virtual_tour_url'     => trim($_POST['virtual_tour_url']     ?? ''),
        'accepts_groups'       => isset($_POST['accepts_groups'])  ? 1 : 0,
        'max_group_size'       => (int)($_POST['max_group_size']      ?? 0),
        'certifications'       => trim($_POST['certifications']       ?? ''),
        'founder_name'         => trim($_POST['founder_name']         ?? ''),
        'founder_quote'        => trim($_POST['founder_quote']        ?? ''),
        'rating'               => (float)($_POST['rating']            ?? 0),
        'reviews_count'        => (int)($_POST['reviews_count']       ?? 0),
        'tier'                 => $_POST['tier']                      ?? 'BASE',
        'is_verified'          => isset($_POST['is_verified'])        ? 1 : 0,
        'b2b_open_for_contact' => isset($_POST['b2b_open_for_contact']) ? 1 : 0,
        'b2b_interests'        => trim($_POST['b2b_interests']        ?? ''),
        'is_active'            => isset($_POST['is_active'])    ? 1 : 0,
        'is_featured'          => isset($_POST['is_featured'])  ? 1 : 0,
    ];
    if ($coverPath) $f['cover_image'] = $coverPath;

    if ($exists->fetch()) {
        $set = implode(',', array_map(fn($k) => "`$k`=?", array_keys($f)));
        $db->prepare("UPDATE restaurants SET $set WHERE id=?")->execute([...array_values($f), $id]);
    } else {
        $cols = implode(',', array_map(fn($k) => "`$k`", array_keys($f)));
        $phs  = implode(',', array_fill(0, count($f), '?'));
        $db->prepare("INSERT INTO restaurants (id,$cols) VALUES (?,$phs)")->execute([$id, ...array_values($f)]);
    }

    processGalleryFromPost($db, 'restaurant', $id, 'new_images');

    $msg = '✅ Ristorante salvato.';
}
render:

if (isset($_GET['delete'])) {
    $db->prepare("DELETE FROM restaurants WHERE id=?")->execute([$_GET['delete']]);
    header('Location: ristorazione.php');
    exit;
}

try {
    $list = $db->query("SELECT id, name, borough_id, type FROM restaurants ORDER BY name ASC")->fetchAll();
} catch (PDOException $e) {
    $pageTitle = 'Ristorazione';
    require '_layout.php';
    echo '<div class="bg-red-900/40 border border-red-600 rounded-xl p-6 text-red-300">
        <p class="font-bold mb-2">❌ Tabella <code>restaurants</code> non trovata nel database.</p>
        <p class="text-sm mb-3">Esegui prima il seed per creare le tabelle e inserire i dati di esempio.</p>
        <a href="seed_lacedonia.php" class="inline-block px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm rounded-lg">🌱 Esegui Seed Lacedonia</a>
    </div>';
    require '_footer.php';
    exit;
}
$sel  = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM restaurants WHERE id=?");
    $stmt->execute([$_GET['edit']]);
    $sel = $stmt->fetch() ?: null;
    if ($sel) {
        $sel['_images'] = fetchEntityImages($db, 'restaurant', $sel['id']);
    }
}

$pageTitle = 'Ristorazione';
require '_layout.php';
?>

<?php if ($msg) echo adminMsg($msg); ?>

<div class="grid md:grid-cols-3 gap-6">
  <!-- Lista -->
  <div class="md:col-span-1 bg-slate-800 rounded-xl border border-slate-700 overflow-hidden">
    <div class="px-4 py-3 border-b border-slate-700 flex items-center justify-between">
      <h3 class="font-semibold text-sm">Ristoranti (<?= count($list) ?>)</h3>
      <a href="ristorazione.php?edit=__new__" class="text-xs bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1 rounded-lg">+ Nuovo</a>
    </div>
    <div class="divide-y divide-slate-700 max-h-[65vh] overflow-y-auto">
      <?php foreach ($list as $item): ?>
      <a href="ristorazione.php?edit=<?= urlencode($item['id']) ?>"
         class="flex items-center justify-between px-4 py-3 hover:bg-slate-700 transition-colors <?= (isset($_GET['edit']) && $_GET['edit']===$item['id'] ? 'bg-slate-700' : '') ?>">
        <div>
          <div class="text-sm font-medium text-white"><?= htmlspecialchars($item['name']) ?></div>
          <div class="text-xs text-slate-400"><?= htmlspecialchars($item['borough_id']) ?> · <?= $item['type'] ?></div>
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
      <h3 class="font-semibold text-white mb-2"><?= $sel ? 'Modifica: ' . htmlspecialchars($sel['name']) : 'Nuovo ristorante' ?></h3>

      <?php echo adminCoverImage($sel); ?>

      <div class="grid grid-cols-2 gap-4">
        <?php
        echo adminInput('id', 'ID', $sel);
        echo adminInput('slug', 'Slug', $sel);
        echo adminInput('name', 'Nome', $sel, 'text', true);
        echo adminInput('borough_id', 'ID Borgo', $sel);
        echo adminInput('cuisine_type', 'Tipo cucina', $sel);
        echo adminInput('address_full', 'Indirizzo completo', $sel, 'text', true);
        echo adminInput('lat', 'Latitudine', $sel, 'number', false, 'any');
        echo adminInput('lng', 'Longitudine', $sel, 'number', false, 'any');
        echo adminInput('seats_indoor', 'Posti interni', $sel, 'number');
        echo adminInput('seats_outdoor', 'Posti esterni', $sel, 'number');
        echo adminInput('opening_hours', 'Orari apertura', $sel, 'text', true);
        echo adminInput('closing_day', 'Giorno chiusura', $sel);
        echo adminInput('contact_email', 'Email', $sel, 'email');
        echo adminInput('contact_phone', 'Telefono', $sel);
        echo adminInput('website_url', 'Sito web', $sel, 'url', true);
        echo adminInput('social_instagram', 'Instagram', $sel);
        echo adminInput('social_facebook', 'Facebook', $sel);
        echo adminInput('social_linkedin', 'LinkedIn', $sel);
        echo adminInput('founder_name', 'Fondatore', $sel);
        echo adminInput('booking_url', 'URL prenotazione', $sel, 'url', true);
        echo adminInput('main_video_url', 'URL Video (YouTube/Vimeo)', $sel, 'url', true);
        echo adminInput('virtual_tour_url', 'URL Virtual Tour', $sel, 'url', true);
        echo adminInput('max_group_size', 'Max persone gruppo', $sel, 'number');
        echo adminInput('rating', 'Valutazione', $sel, 'number', false, '0.1');
        echo adminInput('reviews_count', 'Numero recensioni', $sel, 'number');
        echo adminSelect('type', 'Tipo', $sel, ['RISTORANTE','TRATTORIA','PIZZERIA','AGRITURISMO','ENOTECA','BAR','OSTERIA']);
        echo adminSelect('price_range', 'Fascia prezzo', $sel, ['BUDGET','MEDIO','ALTO','GOURMET']);
        echo adminSelect('tier', 'Tier', $sel, ['BASE','PREMIUM','PLATINUM']);
        ?>
      </div>

      <?php
      echo adminTextarea('tagline', 'Tagline', $sel);
      echo adminTextarea('description_short', 'Descrizione breve', $sel);
      echo adminTextarea('description_long', 'Descrizione completa', $sel, 4);
      echo adminTextarea('specialties', 'Specialità (una per riga)', $sel, 3, 'Una specialità per riga');
      echo adminTextarea('menu_highlights', 'Menu highlights (uno per riga)', $sel, 3, 'Un piatto per riga');
      echo adminTextarea('founder_quote', 'Citazione fondatore', $sel);
      echo adminTextarea('certifications', 'Certificazioni (una per riga)', $sel, 3, 'Una certificazione per riga');
      echo adminTextarea('b2b_interests', 'Interessi B2B (uno per riga)', $sel, 3);
      ?>

      <?php echo adminImageGallery('new_images', $sel['_images'] ?? [], 'Galleria immagini ristorante'); ?>

      <div class="flex gap-4 flex-wrap text-sm">
        <?php
        echo adminCheckbox('accepts_groups', 'Accetta gruppi', $sel);
        echo adminCheckbox('is_verified', 'Verificato', $sel);
        echo adminCheckbox('b2b_open_for_contact', 'Aperto B2B', $sel);
        echo adminCheckbox('is_active', 'Attivo', $sel);
        echo adminCheckbox('is_featured', 'In evidenza', $sel);
        ?>
      </div>

      <div class="flex gap-3 pt-2">
        <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg transition-colors">Salva</button>
        <?php if ($sel): ?>
        <a href="ristorazione.php?delete=<?= urlencode($sel['id']) ?>"
           onclick="return confirm('Eliminare questo ristorante?')"
           class="px-6 py-2.5 bg-red-700 hover:bg-red-600 text-white text-sm rounded-lg transition-colors">Elimina</a>
        <?php endif; ?>
        <a href="ristorazione.php" class="px-6 py-2.5 bg-slate-600 hover:bg-slate-500 text-white text-sm rounded-lg transition-colors">Annulla</a>
      </div>
    </form>
    <?php else: ?>
    <div class="bg-slate-800 rounded-xl border border-slate-700 p-12 text-center">
      <div class="text-4xl mb-3">🍽️</div>
      <p class="text-slate-400">Seleziona un ristorante o creane uno nuovo.</p>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require '_footer.php'; ?>
