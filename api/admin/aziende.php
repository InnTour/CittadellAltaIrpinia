<?php
require_once __DIR__ . '/../config/db.php';
requireAdminSession();
$db = getDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = trim($_POST['id'] ?? '');
    if (!$id) { $msg = '❌ ID obbligatorio.'; goto render; }

    $exists = $db->prepare("SELECT id FROM companies WHERE id=?");
    $exists->execute([$id]);

    $coverPath = handleCoverUpload('cover_image', 'company', $id);
    if ($coverPath) ensureCoverImageColumn($db, 'companies');

    $f = [
        'slug'               => trim($_POST['slug']               ?? '') ?: $id,
        'name'               => trim($_POST['name']               ?? ''),
        'legal_name'         => trim($_POST['legal_name']         ?? ''),
        'vat_number'         => trim($_POST['vat_number']         ?? ''),
        'type'               => $_POST['type']                    ?? 'MISTO',
        'tagline'            => trim($_POST['tagline']            ?? ''),
        'description_short'  => trim($_POST['description_short']  ?? ''),
        'description_long'   => trim($_POST['description_long']   ?? ''),
        'founding_year'      => (int)($_POST['founding_year']     ?? 0),
        'employees_count'    => (int)($_POST['employees_count']   ?? 0),
        'borough_id'         => trim($_POST['borough_id']         ?? ''),
        'address_full'       => trim($_POST['address_full']       ?? ''),
        'lat'                => (float)($_POST['lat']             ?? 0),
        'lng'                => (float)($_POST['lng']             ?? 0),
        'contact_email'      => trim($_POST['contact_email']      ?? ''),
        'contact_phone'      => trim($_POST['contact_phone']      ?? ''),
        'website_url'        => trim($_POST['website_url']        ?? ''),
        'social_instagram'   => trim($_POST['social_instagram']   ?? ''),
        'social_facebook'    => trim($_POST['social_facebook']    ?? ''),
        'social_linkedin'    => trim($_POST['social_linkedin']    ?? ''),
        'tier'               => $_POST['tier']                    ?? 'BASE',
        'is_verified'        => isset($_POST['is_verified'])  ? 1 : 0,
        'is_active'          => isset($_POST['is_active'])    ? 1 : 0,
        'b2b_open_for_contact' => isset($_POST['b2b_open_for_contact']) ? 1 : 0,
        'founder_name'       => trim($_POST['founder_name']       ?? ''),
        'founder_quote'      => trim($_POST['founder_quote']      ?? ''),
        'main_video_url'     => trim($_POST['main_video_url']     ?? ''),
        'virtual_tour_url'   => trim($_POST['virtual_tour_url']   ?? ''),
        'hero_image_index'   => (int)($_POST['hero_image_index']  ?? 0),
        'hero_image_alt'     => trim($_POST['hero_image_alt']     ?? ''),
    ];
    if ($coverPath) $f['cover_image'] = $coverPath;

    if ($exists->fetch()) {
        $set = implode(',', array_map(fn($k) => "`$k`=?", array_keys($f)));
        $db->prepare("UPDATE companies SET $set WHERE id=?")->execute([...array_values($f), $id]);
    } else {
        $cols = implode(',', array_map(fn($k) => "`$k`", array_keys($f)));
        $phs  = implode(',', array_fill(0, count($f), '?'));
        $db->prepare("INSERT INTO companies (id,$cols) VALUES (?,$phs)")->execute([$id, ...array_values($f)]);
    }

    $toLines = fn($s) => array_filter(array_map('trim', explode("\n", $s ?? '')));
    replaceArray($db, 'company_certifications', 'company_id', $id, $toLines($_POST['certifications']  ?? ''));
    replaceArray($db, 'company_b2b_interests',  'company_id', $id, $toLines($_POST['b2b_interests']   ?? ''));

    // Awards
    $db->prepare("DELETE FROM company_awards WHERE company_id = ?")->execute([$id]);
    $awardYears  = $_POST['award_year']  ?? [];
    $awardTitles = $_POST['award_title'] ?? [];
    $awardEnts   = $_POST['award_entity'] ?? [];
    $stmtAw = $db->prepare("INSERT INTO company_awards (company_id, year, title, entity) VALUES (?,?,?,?)");
    for ($i = 0; $i < count($awardTitles); $i++) {
        if (trim($awardTitles[$i] ?? '')) {
            $stmtAw->execute([$id, (int)($awardYears[$i] ?? 0), $awardTitles[$i], $awardEnts[$i] ?? '']);
        }
    }

    // Gallery images
    processGalleryFromPost($db, 'company', $id, 'new_images');

    $msg = '✅ Azienda salvata.';
}
render:

if (isset($_GET['delete'])) {
    $did = $_GET['delete'];
    foreach (['company_certifications','company_b2b_interests','company_awards'] as $t) {
        $db->prepare("DELETE FROM `$t` WHERE company_id = ?")->execute([$did]);
    }
    $db->prepare("DELETE FROM entity_images WHERE entity_type = 'company' AND entity_id = ?")->execute([$did]);
    $db->prepare("DELETE FROM companies WHERE id=?")->execute([$did]);
    header('Location: aziende.php');
    exit;
}

$list = $db->query("SELECT id, name, borough_id, tier FROM companies ORDER BY name ASC")->fetchAll();
$sel = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM companies WHERE id=?");
    $stmt->execute([$_GET['edit']]);
    $sel = $stmt->fetch();
    if ($sel) {
        $sel['certifications'] = implode("\n", fetchArray($db, 'company_certifications', 'company_id', $sel['id']));
        $sel['b2b_interests']  = implode("\n", fetchArray($db, 'company_b2b_interests',  'company_id', $sel['id']));
        $sel['_images'] = fetchEntityImages($db, 'company', $sel['id']);
        // Awards
        $stmtAw = $db->prepare("SELECT year, title, entity FROM company_awards WHERE company_id = ? ORDER BY year DESC");
        $stmtAw->execute([$sel['id']]);
        $sel['_awards'] = $stmtAw->fetchAll();
    }
}

$pageTitle = 'Aziende';
require '_layout.php';
?>

<?= adminMsg($msg) ?>

<div class="grid md:grid-cols-3 gap-6">
  <!-- Lista -->
  <div class="md:col-span-1 bg-slate-800 rounded-xl border border-slate-700 overflow-hidden">
    <div class="px-4 py-3 border-b border-slate-700 flex items-center justify-between">
      <h3 class="font-semibold text-sm">Aziende (<?= count($list) ?>)</h3>
      <a href="aziende.php?edit=__new__" class="text-xs bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1 rounded-lg">+ Nuova</a>
    </div>
    <div class="divide-y divide-slate-700 max-h-[65vh] overflow-y-auto">
      <?php foreach ($list as $item): ?>
      <a href="aziende.php?edit=<?= urlencode($item['id']) ?>"
         class="flex items-center justify-between px-4 py-3 hover:bg-slate-700 transition-colors <?= (isset($_GET['edit']) && $_GET['edit']===$item['id'] ? 'bg-slate-700' : '') ?>">
        <div>
          <div class="text-sm font-medium text-white"><?= htmlspecialchars($item['name']) ?></div>
          <div class="text-xs text-slate-400"><?= htmlspecialchars($item['borough_id']) ?> · <?= $item['tier'] ?></div>
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
      <h3 class="font-semibold text-white mb-2"><?= $sel ? 'Modifica: ' . htmlspecialchars($sel['name']) : 'Nuova azienda' ?></h3>

      <?= adminCoverImage($sel) ?>

      <div class="grid grid-cols-2 gap-4">
        <?= adminInput('id', 'ID', $sel) ?>
        <?= adminInput('slug', 'Slug', $sel) ?>
        <?= adminInput('name', 'Nome', $sel, 'text', true) ?>
        <?= adminInput('legal_name', 'Ragione Sociale', $sel) ?>
        <?= adminInput('vat_number', 'P.IVA', $sel) ?>
        <?= adminInput('borough_id', 'ID Borgo', $sel) ?>
        <?= adminInput('founding_year', 'Anno fondazione', $sel, 'number') ?>
        <?= adminInput('employees_count', 'Dipendenti', $sel, 'number') ?>
        <?= adminInput('contact_email', 'Email', $sel, 'email') ?>
        <?= adminInput('contact_phone', 'Telefono', $sel) ?>
        <?= adminInput('lat', 'Latitudine', $sel, 'number', false, 'any') ?>
        <?= adminInput('lng', 'Longitudine', $sel, 'number', false, 'any') ?>
        <?= adminInput('address_full', 'Indirizzo completo', $sel, 'text', true) ?>
        <?= adminInput('website_url', 'Sito web', $sel, 'url', true) ?>
        <?= adminInput('social_instagram', 'Instagram', $sel) ?>
        <?= adminInput('social_facebook', 'Facebook', $sel) ?>
        <?= adminInput('social_linkedin', 'LinkedIn', $sel) ?>
        <?= adminInput('founder_name', 'Fondatore', $sel) ?>
        <?= adminInput('hero_image_index', 'Indice immagine hero', $sel, 'number') ?>
        <?= adminInput('hero_image_alt', 'Alt immagine hero', $sel, 'text', true) ?>
        <?= adminInput('main_video_url', 'URL Video embed', $sel, 'text', true) ?>
        <?= adminInput('virtual_tour_url', 'URL Tour Virtuale', $sel, 'text', true) ?>
        <?= adminSelect('type', 'Tipo', $sel, ['PRODUTTORE_FOOD','ARTIGIANO','MISTO','AGRITURISMO','RISTORANTE','GUIDA_TURISTICA','COOPERATIVA']) ?>
        <?= adminSelect('tier', 'Tier', $sel, ['BASE','PREMIUM','PLATINUM']) ?>
        <?= adminImageGallery('new_images', $sel['_images'] ?? [], 'Galleria immagini azienda') ?>
      </div>

      <?= adminInput('tagline', 'Tagline', $sel, 'text', true) ?>
      <?= adminTextarea('description_short', 'Descrizione breve', $sel, 2) ?>
      <?= adminTextarea('description_long', 'Descrizione completa', $sel, 4) ?>
      <?= adminTextarea('founder_quote', 'Citazione fondatore', $sel, 2) ?>
      <?= adminTextarea('certifications', 'Certificazioni (una per riga)', $sel, 3) ?>
      <?= adminTextarea('b2b_interests', 'Interessi B2B (uno per riga)', $sel, 3) ?>

      <!-- Awards -->
      <div>
        <label class="block text-xs text-slate-400 mb-2">Premi e riconoscimenti</label>
        <div id="awards-container" class="space-y-2">
          <?php foreach ($sel['_awards'] ?? [] as $aw): ?>
          <div class="flex gap-2 items-center award-row">
            <input type="number" name="award_year[]" value="<?= (int)$aw['year'] ?>" placeholder="Anno" class="w-20 bg-slate-700 text-white rounded-lg px-2 py-2 text-sm border border-slate-600">
            <input type="text" name="award_title[]" value="<?= htmlspecialchars($aw['title']) ?>" placeholder="Titolo premio" class="flex-1 bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600">
            <input type="text" name="award_entity[]" value="<?= htmlspecialchars($aw['entity']) ?>" placeholder="Ente" class="w-32 bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600">
            <button type="button" onclick="this.parentElement.remove()" class="text-red-400 hover:text-red-300 text-sm px-2">&times;</button>
          </div>
          <?php endforeach; ?>
        </div>
        <button type="button" onclick="addAwardRow()" class="mt-2 text-xs text-emerald-400 hover:text-emerald-300">+ Aggiungi premio</button>
      </div>

      <div class="flex gap-4 flex-wrap text-sm">
        <?= adminCheckbox('is_verified', 'Verificata', $sel) ?>
        <?= adminCheckbox('is_active', 'Attiva', $sel) ?>
        <?= adminCheckbox('b2b_open_for_contact', 'Aperta B2B', $sel) ?>
      </div>

      <div class="flex gap-3 pt-2">
        <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg transition-colors">Salva</button>
        <?php if ($sel): ?>
        <a href="aziende.php?delete=<?= urlencode($sel['id']) ?>"
           onclick="return confirm('Eliminare questa azienda?')"
           class="px-6 py-2.5 bg-red-700 hover:bg-red-600 text-white text-sm rounded-lg transition-colors">Elimina</a>
        <?php endif; ?>
        <a href="aziende.php" class="px-6 py-2.5 bg-slate-600 hover:bg-slate-500 text-white text-sm rounded-lg transition-colors">Annulla</a>
      </div>
    </form>

    <script>
    function addAwardRow() {
      const c = document.getElementById('awards-container');
      const div = document.createElement('div');
      div.className = 'flex gap-2 items-center award-row';
      div.innerHTML = '<input type="number" name="award_year[]" placeholder="Anno" class="w-20 bg-slate-700 text-white rounded-lg px-2 py-2 text-sm border border-slate-600">'
        + '<input type="text" name="award_title[]" placeholder="Titolo premio" class="flex-1 bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600">'
        + '<input type="text" name="award_entity[]" placeholder="Ente" class="w-32 bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600">'
        + '<button type="button" onclick="this.parentElement.remove()" class="text-red-400 hover:text-red-300 text-sm px-2">&times;</button>';
      c.appendChild(div);
    }
    </script>
    <?php else: ?>
    <div class="bg-slate-800 rounded-xl border border-slate-700 p-12 text-center">
      <div class="text-4xl mb-3">🏢</div>
      <p class="text-slate-400">Seleziona un'azienda o creane una nuova.</p>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require '_footer.php'; ?>
