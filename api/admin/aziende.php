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
        'slug'               => trim($_POST['slug']               ?? $id),
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
        'social_instagram'   => trim($_POST['social_instagram']   ?? '#'),
        'social_facebook'    => trim($_POST['social_facebook']    ?? '#'),
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

    $msg = '✅ Azienda salvata.';
}
render:

if (isset($_GET['delete'])) {
    $did = $_GET['delete'];
    foreach (['company_certifications','company_b2b_interests','company_awards'] as $t) {
        $db->prepare("DELETE FROM `$t` WHERE company_id = ?")->execute([$did]);
    }
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
    }
}

$pageTitle = 'Aziende';
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
        echo $inp('legal_name','Ragione Sociale');
        echo $inp('vat_number','P.IVA');
        echo $inp('borough_id','ID Borgo');
        echo $inp('founding_year','Anno fondazione','number');
        echo $inp('employees_count','Dipendenti','number');
        echo $inp('contact_email','Email','email');
        echo $inp('contact_phone','Telefono');
        echo $inp('lat','Latitudine','number');
        echo $inp('lng','Longitudine','number');
        echo $inp('address_full','Indirizzo completo','text',true);
        echo $inp('website_url','Sito web','url',true);
        echo $inp('social_instagram','Instagram');
        echo $inp('social_facebook','Facebook');
        echo $inp('social_linkedin','LinkedIn');
        echo $inp('founder_name','Fondatore');
        echo $inp('hero_image_index','Indice immagine','number');
        echo $inp('main_video_url','URL Video embed','text',true);
        echo $inp('virtual_tour_url','URL Tour Virtuale embed','text',true);
        ?>
        <div>
          <label class="block text-xs text-slate-400 mb-1">Tipo</label>
          <select name="type" class="w-full bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600 focus:outline-none focus:border-emerald-500">
            <?php foreach (['PRODUTTORE_FOOD','MISTO','AGRITURISMO'] as $t): ?>
            <option value="<?= $t ?>" <?= ($sel['type']??'')===$t?'selected':'' ?>><?= $t ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-xs text-slate-400 mb-1">Tier</label>
          <select name="tier" class="w-full bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600 focus:outline-none focus:border-emerald-500">
            <?php foreach (['BASE','PREMIUM','PLATINUM'] as $t): ?>
            <option value="<?= $t ?>" <?= ($sel['tier']??'')===$t?'selected':'' ?>><?= $t ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div>
        <label class="block text-xs text-slate-400 mb-1">Tagline</label>
        <input type="text" name="tagline" value="<?= htmlspecialchars($sel['tagline']??'') ?>"
          class="w-full bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600 focus:outline-none focus:border-emerald-500">
      </div>
      <div>
        <label class="block text-xs text-slate-400 mb-1">Descrizione breve</label>
        <textarea name="description_short" rows="2" class="w-full bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600 focus:outline-none focus:border-emerald-500"><?= htmlspecialchars($sel['description_short']??'') ?></textarea>
      </div>
      <div>
        <label class="block text-xs text-slate-400 mb-1">Descrizione completa</label>
        <textarea name="description_long" rows="4" class="w-full bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600 focus:outline-none focus:border-emerald-500"><?= htmlspecialchars($sel['description_long']??'') ?></textarea>
      </div>
      <div>
        <label class="block text-xs text-slate-400 mb-1">Citazione fondatore</label>
        <textarea name="founder_quote" rows="2" class="w-full bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600 focus:outline-none focus:border-emerald-500"><?= htmlspecialchars($sel['founder_quote']??'') ?></textarea>
      </div>
      <?php foreach ([['certifications','Certificazioni (una per riga)'],['b2b_interests','Interessi B2B (uno per riga)']] as [$n,$l]): ?>
      <div>
        <label class="block text-xs text-slate-400 mb-1"><?= $l ?></label>
        <textarea name="<?= $n ?>" rows="3" class="w-full bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600 focus:outline-none focus:border-emerald-500"><?= htmlspecialchars($sel[$n]??'') ?></textarea>
      </div>
      <?php endforeach; ?>
      <div class="flex gap-3 flex-wrap text-sm">
        <?php foreach ([['is_verified','Verificata'],['is_active','Attiva'],['b2b_open_for_contact','Aperta B2B']] as [$n,$l]): ?>
        <label class="flex items-center gap-2 text-slate-300">
          <input type="checkbox" name="<?= $n ?>" <?= !empty($sel[$n])?'checked':'' ?> class="rounded">
          <?= $l ?>
        </label>
        <?php endforeach; ?>
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
    <?php else: ?>
    <div class="bg-slate-800 rounded-xl border border-slate-700 p-12 text-center">
      <div class="text-4xl mb-3">🏢</div>
      <p class="text-slate-400">Seleziona un'azienda o creane una nuova.</p>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require '_footer.php'; ?>
