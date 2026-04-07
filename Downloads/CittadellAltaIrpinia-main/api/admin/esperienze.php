<?php
require_once __DIR__ . '/../config/db.php';
requireAdminSession();
requireOperatorAccess('experience');
$_su       = getAdminSessionUser();
$_isAdmin  = ($_su['role'] === 'admin');
$_entityId = $_isAdmin ? null : ($_su['company_id'] ?? null); // company_id = provider_id
$db = getDB();
$msg = '';

// Assicura che le colonne aggiunte dopo la migrazione iniziale esistano
ensureTableColumns($db, 'experiences', [
    'main_video_url'   => "TEXT DEFAULT NULL",
    'virtual_tour_url' => "TEXT DEFAULT NULL",
    'cover_video_url'  => "TEXT DEFAULT NULL",
]);

// ============================================================
// POST — Salvataggio esperienza
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = trim($_POST['id'] ?? '');
    if (!$id) { $msg = '❌ ID obbligatorio.'; goto render; }

    $required_fields = ['title' => 'Titolo', 'provider_id' => 'ID Azienda', 'borough_id' => 'ID Borgo'];
    foreach ($required_fields as $field => $fieldLabel) {
        if (empty(trim($_POST[$field] ?? ''))) {
            $msg = "❌ Il campo \"$fieldLabel\" è obbligatorio.";
            goto render;
        }
    }
    if (empty($_POST['category'] ?? '')) {
        $msg = '❌ Il campo "Categoria" è obbligatorio.';
        goto render;
    }
    if (!isset($_POST['price_per_person']) || trim($_POST['price_per_person']) === '') {
        $msg = '❌ Il campo "Prezzo/persona" è obbligatorio.';
        goto render;
    }
    if (empty(trim($_POST['duration_minutes'] ?? '')) || (int)($_POST['duration_minutes']) <= 0) {
        $msg = '❌ Il campo "Durata (min)" è obbligatorio e deve essere maggiore di 0.';
        goto render;
    }

    $exists = $db->prepare("SELECT id FROM experiences WHERE id=?");
    $exists->execute([$id]);

    $coverPath = handleCoverUpload('cover_image', 'experience', $id);
    if ($coverPath) ensureCoverImageColumn($db, 'experiences');

    $f = [
        'slug'                => trim($_POST['slug']                ?? $id),
        'title'               => trim($_POST['title']               ?? ''),
        'tagline'             => trim($_POST['tagline']             ?? ''),
        'description_short'   => trim($_POST['description_short']   ?? ''),
        'description_long'    => trim($_POST['description_long']    ?? ''),
        'category'            => $_POST['category']                 ?? 'CULTURA',
        'provider_id'         => trim($_POST['provider_id']         ?? ''),
        'borough_id'          => trim($_POST['borough_id']          ?? ''),
        'lat'                 => (float)($_POST['lat']              ?? 0),
        'lng'                 => (float)($_POST['lng']              ?? 0),
        'duration_minutes'    => (int)($_POST['duration_minutes']   ?? 0),
        'max_participants'    => (int)($_POST['max_participants']   ?? 0),
        'min_participants'    => (int)($_POST['min_participants']   ?? 1),
        'price_per_person'    => (float)($_POST['price_per_person'] ?? 0),
        'cancellation_policy' => trim($_POST['cancellation_policy'] ?? ''),
        'difficulty_level'    => $_POST['difficulty_level']         ?? 'FACILE',
        'accessibility_info'  => trim($_POST['accessibility_info']  ?? ''),
        'rating'              => (float)($_POST['rating']           ?? 0),
        'reviews_count'       => (int)($_POST['reviews_count']      ?? 0),
        'is_active'           => isset($_POST['is_active']) ? 1 : 0,
        'main_video_url'      => trim($_POST['main_video_url']      ?? '') ?: null,
        'virtual_tour_url'    => trim($_POST['virtual_tour_url']    ?? '') ?: null,
        'cover_video_url'     => trim($_POST['cover_video_url']     ?? '') ?: null,
    ];
    if ($coverPath) $f['cover_image'] = $coverPath;

    if ($exists->fetch()) {
        $set = implode(',', array_map(fn($k) => "`$k`=?", array_keys($f)));
        $db->prepare("UPDATE experiences SET $set WHERE id=?")->execute([...array_values($f), $id]);
    } else {
        $cols = implode(',', array_map(fn($k) => "`$k`", array_keys($f)));
        $phs  = implode(',', array_fill(0, count($f), '?'));
        $db->prepare("INSERT INTO experiences (id,$cols) VALUES (?,$phs)")->execute([$id, ...array_values($f)]);
    }

    // Array fields
    $langs = array_filter(array_map('trim', explode(',', $_POST['languages_available'] ?? '')));
    $db->prepare("DELETE FROM experience_languages WHERE experience_id=?")->execute([$id]);
    $stmtLang = $db->prepare("INSERT INTO experience_languages (experience_id, lang) VALUES (?,?)");
    foreach ($langs as $l) $stmtLang->execute([$id, $l]);

    replaceArray($db, 'experience_includes',      'experience_id', $id, parseTextToArray($_POST['includes']      ?? ''));
    replaceArray($db, 'experience_excludes',       'experience_id', $id, parseTextToArray($_POST['excludes']      ?? ''));
    replaceArray($db, 'experience_bring',          'experience_id', $id, parseTextToArray($_POST['what_to_bring'] ?? ''));
    replaceArray($db, 'experience_seasonal_tags',  'experience_id', $id, parseTextToArray($_POST['seasonal_tags'] ?? ''));

    // Timeline steps
    $db->prepare("DELETE FROM experience_timeline WHERE experience_id=?")->execute([$id]);
    $times  = $_POST['timeline_time']  ?? [];
    $titles = $_POST['timeline_title'] ?? [];
    $descs  = $_POST['timeline_desc']  ?? [];
    $stmtTl = $db->prepare("INSERT INTO experience_timeline (experience_id, time_slot, title, description, sort_order) VALUES (?,?,?,?,?)");
    for ($i = 0; $i < count($titles); $i++) {
        if (trim($titles[$i] ?? '')) {
            $stmtTl->execute([$id, $times[$i] ?? '', $titles[$i], $descs[$i] ?? '', $i]);
        }
    }

    // Gallery images
    processGalleryFromPost($db, 'experience', $id, 'new_images');

    $msg = '✅ Esperienza salvata.';
}
render:

// ============================================================
// DELETE
// ============================================================
if (isset($_GET['delete'])) {
    $did = $_GET['delete'];
    foreach (['experience_languages','experience_includes','experience_excludes',
              'experience_bring','experience_seasonal_tags','experience_timeline'] as $t) {
        $db->prepare("DELETE FROM `$t` WHERE experience_id = ?")->execute([$did]);
    }
    // Remove gallery images
    $db->prepare("DELETE FROM entity_images WHERE entity_type='experience' AND entity_id=?")->execute([$did]);
    $db->prepare("DELETE FROM experiences WHERE id=?")->execute([$did]);
    header('Location: esperienze.php');
    exit;
}

// ============================================================
// LIST + EDIT LOAD
// ============================================================
if ($_entityId) {
    $stmtList = $db->prepare("SELECT id, title, category, borough_id FROM experiences WHERE provider_id = ? ORDER BY title ASC");
    $stmtList->execute([$_entityId]);
    $list = $stmtList->fetchAll();
} else {
    $list = $db->query("SELECT id, title, category, borough_id FROM experiences ORDER BY title ASC")->fetchAll();
}
$sel = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM experiences WHERE id=?");
    $stmt->execute([$_GET['edit']]);
    $sel = $stmt->fetch() ?: null;
    if ($sel) {
        // Array fields
        $langs = fetchArray($db, 'experience_languages', 'experience_id', $sel['id'], 'lang');
        $sel['languages_available'] = implode(', ', $langs);
        $sel['includes']      = implode("\n", fetchArray($db, 'experience_includes',      'experience_id', $sel['id']));
        $sel['excludes']      = implode("\n", fetchArray($db, 'experience_excludes',      'experience_id', $sel['id']));
        $sel['what_to_bring'] = implode("\n", fetchArray($db, 'experience_bring',         'experience_id', $sel['id']));
        $sel['seasonal_tags'] = implode("\n", fetchArray($db, 'experience_seasonal_tags', 'experience_id', $sel['id']));

        // Gallery images
        $sel['_images'] = fetchEntityImages($db, 'experience', $sel['id']);

        // Timeline steps
        $tlStmt = $db->prepare("SELECT time_slot, title, description FROM experience_timeline WHERE experience_id = ? ORDER BY sort_order ASC");
        $tlStmt->execute([$sel['id']]);
        $sel['_timeline'] = $tlStmt->fetchAll();
    }
}

$pageTitle = 'Esperienze';
require '_layout.php';
?>

<?= adminMsg($msg) ?>

<div class="grid md:grid-cols-3 gap-6">
  <!-- LEFT: List -->
  <div class="md:col-span-1 bg-slate-800 rounded-xl border border-slate-700 overflow-hidden">
    <div class="px-4 py-3 border-b border-slate-700 flex items-center justify-between">
      <h3 class="font-semibold text-sm">Esperienze (<?= count($list) ?>)</h3>
      <a href="esperienze.php?edit=__new__" class="text-xs bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1 rounded-lg">+ Nuova</a>
    </div>
    <div class="divide-y divide-slate-700 max-h-[65vh] overflow-y-auto">
      <?php foreach ($list as $item): ?>
      <a href="esperienze.php?edit=<?= urlencode($item['id']) ?>"
         class="flex items-center justify-between px-4 py-3 hover:bg-slate-700 transition-colors <?= (isset($_GET['edit']) && $_GET['edit']===$item['id'] ? 'bg-slate-700' : '') ?>">
        <div>
          <div class="text-sm font-medium text-white"><?= htmlspecialchars($item['title']) ?></div>
          <div class="text-xs text-slate-400"><?= $item['category'] ?> · <?= htmlspecialchars($item['borough_id']) ?></div>
        </div>
        <span class="text-xs text-slate-500">›</span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- RIGHT: Form -->
  <div class="md:col-span-2">
    <?php if ($sel !== null || isset($_GET['edit'])): ?>
    <form method="POST" enctype="multipart/form-data" class="bg-slate-800 rounded-xl border border-slate-700 p-6 space-y-4">
      <h3 class="font-semibold text-white mb-2"><?= $sel ? htmlspecialchars($sel['title']) : 'Nuova esperienza' ?></h3>

      <!-- Cover Image -->
      <?= adminCoverImage($sel) ?>

      <!-- Gallery -->
      <?= adminImageGallery('new_images', $sel['_images'] ?? [], 'Galleria immagini esperienza') ?>

      <!-- Main fields grid -->
      <div class="grid grid-cols-2 gap-4">
        <?= adminInput('id', 'ID', $sel, 'text', false, '', true) ?>
        <?= adminInput('slug', 'Slug', $sel) ?>
        <?= adminInput('title', 'Titolo', $sel, 'text', true, '', true) ?>
        <?= adminInput('tagline', 'Tagline', $sel, 'text', true) ?>
        <?= adminInput('provider_id', 'ID Azienda', $sel, 'text', false, '', true) ?>
        <?= adminInput('borough_id', 'ID Borgo', $sel, 'text', false, '', true) ?>
        <?= adminInput('lat', 'Latitudine', $sel, 'number') ?>
        <?= adminInput('lng', 'Longitudine', $sel, 'number') ?>
        <?= adminInput('duration_minutes', 'Durata (min)', $sel, 'number', false, '', true) ?>
        <?= adminInput('max_participants', 'Max partecipanti', $sel, 'number') ?>
        <?= adminInput('min_participants', 'Min partecipanti', $sel, 'number') ?>
        <?= adminInput('price_per_person', 'Prezzo/persona', $sel, 'number', false, '0.01', true) ?>
        <?= adminInput('rating', 'Rating (0-5)', $sel, 'number', false, '0.1') ?>
        <?= adminInput('reviews_count', 'N. recensioni', $sel, 'number') ?>
        <?= adminSelect('category', 'Categoria', $sel, ['GASTRONOMIA','CULTURA','NATURA','ARTIGIANATO','BENESSERE','AVVENTURA']) ?>
        <?= adminSelect('difficulty_level', 'Difficoltà', $sel, ['FACILE','MEDIO','DIFFICILE']) ?>
        <?= adminInput('languages_available', 'Lingue (es: Italiano, English)', $sel, 'text', true) ?>
      </div>

      <!-- Textareas -->
      <?= adminTextarea('description_short', 'Descrizione breve', $sel, 2) ?>
      <?= adminTextarea('description_long', 'Descrizione completa', $sel, 4) ?>
      <?= adminTextarea('cancellation_policy', 'Politica di cancellazione', $sel, 2) ?>
      <?= adminTextarea('accessibility_info', 'Info accessibilità', $sel, 2) ?>
      <?= adminTextarea('includes', 'Include (uno per riga)', $sel, 3, 'Inserisci un elemento per riga') ?>
      <?= adminTextarea('excludes', 'Non include (uno per riga)', $sel, 3, 'Inserisci un elemento per riga') ?>
      <?= adminTextarea('what_to_bring', 'Cosa portare (uno per riga)', $sel, 3, 'Inserisci un elemento per riga') ?>
      <?= adminTextarea('seasonal_tags', 'Tag stagionali (uno per riga)', $sel, 2) ?>

      <!-- Video & Virtual Tour -->
      <div class="grid grid-cols-2 gap-4">
        <?= adminInput('cover_video_url', 'Video copertina (YouTube/locale)', $sel, 'text', true) ?>
        <?= adminInput('main_video_url', 'URL Video (YouTube/Vimeo)', $sel, 'url', true) ?>
        <?= adminInput('virtual_tour_url', 'URL Virtual Tour (iframe)', $sel, 'url', true) ?>
      </div>

      <!-- Timeline Steps -->
      <div class="col-span-2">
        <label class="block text-xs text-slate-400 mb-2">Timeline / Programma della giornata</label>
        <div id="timeline-container" class="space-y-3">
          <?php
          $timeline = $sel['_timeline'] ?? [];
          if (!empty($timeline)):
            foreach ($timeline as $i => $step):
          ?>
          <div class="timeline-row flex gap-2 items-start bg-slate-700/50 rounded-lg p-3">
            <div class="w-28 shrink-0">
              <label class="block text-xs text-slate-500 mb-1">Orario</label>
              <input type="text" name="timeline_time[]" value="<?= htmlspecialchars($step['time_slot'] ?? '') ?>" placeholder="09:00"
                class="w-full bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600 focus:outline-none focus:border-emerald-500">
            </div>
            <div class="flex-1">
              <label class="block text-xs text-slate-500 mb-1">Titolo</label>
              <input type="text" name="timeline_title[]" value="<?= htmlspecialchars($step['title'] ?? '') ?>" placeholder="Titolo step"
                class="w-full bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600 focus:outline-none focus:border-emerald-500">
            </div>
            <div class="flex-1">
              <label class="block text-xs text-slate-500 mb-1">Descrizione</label>
              <textarea name="timeline_desc[]" rows="1" placeholder="Descrizione..."
                class="w-full bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600 focus:outline-none focus:border-emerald-500"><?= htmlspecialchars($step['description'] ?? '') ?></textarea>
            </div>
            <button type="button" onclick="this.closest('.timeline-row').remove()" class="mt-5 shrink-0 text-red-400 hover:text-red-300 text-lg px-2" title="Rimuovi">&times;</button>
          </div>
          <?php
            endforeach;
          endif;
          ?>
        </div>
        <button type="button" id="add-timeline-step" class="mt-3 text-xs bg-slate-600 hover:bg-slate-500 text-white px-4 py-2 rounded-lg transition-colors">+ Aggiungi step</button>
      </div>

      <!-- Active checkbox -->
      <?= adminCheckbox('is_active', 'Attiva', $sel) ?>

      <!-- Actions -->
      <div class="flex gap-3 pt-2">
        <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg">Salva</button>
        <?php if ($sel): ?>
        <a href="esperienze.php?delete=<?= urlencode($sel['id']) ?>"
           onclick="return confirm('Eliminare questa esperienza?')"
           class="px-6 py-2.5 bg-red-700 hover:bg-red-600 text-white text-sm rounded-lg transition-colors">Elimina</a>
        <?php endif; ?>
        <a href="esperienze.php" class="px-6 py-2.5 bg-slate-600 hover:bg-slate-500 text-white text-sm rounded-lg">Annulla</a>
      </div>
    </form>

    <script>
    document.getElementById('add-timeline-step').addEventListener('click', function() {
      const container = document.getElementById('timeline-container');
      const row = document.createElement('div');
      row.className = 'timeline-row flex gap-2 items-start bg-slate-700/50 rounded-lg p-3';
      row.innerHTML = `
        <div class="w-28 shrink-0">
          <label class="block text-xs text-slate-500 mb-1">Orario</label>
          <input type="text" name="timeline_time[]" placeholder="09:00"
            class="w-full bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600 focus:outline-none focus:border-emerald-500">
        </div>
        <div class="flex-1">
          <label class="block text-xs text-slate-500 mb-1">Titolo</label>
          <input type="text" name="timeline_title[]" placeholder="Titolo step"
            class="w-full bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600 focus:outline-none focus:border-emerald-500">
        </div>
        <div class="flex-1">
          <label class="block text-xs text-slate-500 mb-1">Descrizione</label>
          <textarea name="timeline_desc[]" rows="1" placeholder="Descrizione..."
            class="w-full bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600 focus:outline-none focus:border-emerald-500"></textarea>
        </div>
        <button type="button" onclick="this.closest('.timeline-row').remove()" class="mt-5 shrink-0 text-red-400 hover:text-red-300 text-lg px-2" title="Rimuovi">&times;</button>
      `;
      container.appendChild(row);
    });
    </script>

    <?php else: ?>
    <div class="bg-slate-800 rounded-xl border border-slate-700 p-12 text-center">
      <div class="text-4xl mb-3">🎭</div>
      <p class="text-slate-400">Seleziona un'esperienza o creane una nuova.</p>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require '_footer.php'; ?>
