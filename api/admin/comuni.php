<?php
require_once __DIR__ . '/../config/db.php';
requireAdminSession();
$db = getDB();
$msg = '';

// Auto-create table if not exists
try {
    $db->query("SELECT 1 FROM b2g_municipalities LIMIT 0");
} catch (PDOException $e) {
    $db->exec("CREATE TABLE IF NOT EXISTS `b2g_municipalities` (
      `id`                   VARCHAR(100)   NOT NULL,
      `borough_id`           VARCHAR(100)   DEFAULT NULL,
      `municipality_name`    VARCHAR(300)   NOT NULL,
      `province`             VARCHAR(100)   DEFAULT NULL,
      `region`               VARCHAR(100)   DEFAULT 'Campania',
      `mayor_name`           VARCHAR(200)   DEFAULT NULL,
      `mayor_email`          VARCHAR(200)   DEFAULT NULL,
      `contact_person`       VARCHAR(200)   DEFAULT NULL,
      `contact_email`        VARCHAR(200)   DEFAULT NULL,
      `contact_phone`        VARCHAR(50)    DEFAULT NULL,
      `pec_email`            VARCHAR(200)   DEFAULT NULL,
      `website_url`          TEXT           DEFAULT NULL,
      `population`           INT            DEFAULT NULL,
      `tier`                 ENUM('BASE','STANDARD','PREMIUM') DEFAULT 'BASE',
      `subscription_status`  ENUM('LEAD','CONTATTATO','DEMO','ATTIVO','SOSPESO','SCADUTO') DEFAULT 'LEAD',
      `subscription_start`   DATE           DEFAULT NULL,
      `subscription_end`     DATE           DEFAULT NULL,
      `annual_fee`           DECIMAL(10,2)  DEFAULT NULL,
      `pnrr_funded`          TINYINT(1)     DEFAULT 0,
      `pnrr_measure`         VARCHAR(200)   DEFAULT NULL,
      `notes`                TEXT           DEFAULT NULL,
      `services_enabled`     TEXT           DEFAULT NULL,
      `cover_image`          VARCHAR(500)   DEFAULT NULL,
      `created_at`           TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
      `updated_at`           TIMESTAMP      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = trim($_POST['id'] ?? '');
    if (!$id) { $msg = '❌ ID obbligatorio.'; goto render; }

    $exists = $db->prepare("SELECT id FROM b2g_municipalities WHERE id=?");
    $exists->execute([$id]);

    $coverPath = handleCoverUpload('cover_image', 'municipality', $id);

    $f = [
        'borough_id'          => trim($_POST['borough_id']          ?? ''),
        'municipality_name'   => trim($_POST['municipality_name']   ?? ''),
        'province'            => trim($_POST['province']            ?? ''),
        'region'              => trim($_POST['region']              ?? 'Campania'),
        'mayor_name'          => trim($_POST['mayor_name']          ?? ''),
        'mayor_email'         => trim($_POST['mayor_email']         ?? ''),
        'contact_person'      => trim($_POST['contact_person']      ?? ''),
        'contact_email'       => trim($_POST['contact_email']       ?? ''),
        'contact_phone'       => trim($_POST['contact_phone']       ?? ''),
        'pec_email'           => trim($_POST['pec_email']           ?? ''),
        'website_url'         => trim($_POST['website_url']         ?? ''),
        'population'          => (int)($_POST['population']         ?? 0) ?: null,
        'tier'                => $_POST['tier']                     ?? 'BASE',
        'subscription_status' => $_POST['subscription_status']      ?? 'LEAD',
        'subscription_start'  => trim($_POST['subscription_start']  ?? '') ?: null,
        'subscription_end'    => trim($_POST['subscription_end']    ?? '') ?: null,
        'annual_fee'          => (float)($_POST['annual_fee']       ?? 0) ?: null,
        'pnrr_funded'         => isset($_POST['pnrr_funded'])       ? 1 : 0,
        'pnrr_measure'        => trim($_POST['pnrr_measure']       ?? ''),
        'notes'               => trim($_POST['notes']               ?? ''),
        'services_enabled'    => trim($_POST['services_enabled']    ?? ''),
    ];
    if ($coverPath) $f['cover_image'] = $coverPath;

    if ($exists->fetch()) {
        $set = implode(',', array_map(fn($k) => "`$k`=?", array_keys($f)));
        $db->prepare("UPDATE b2g_municipalities SET $set WHERE id=?")->execute([...array_values($f), $id]);
    } else {
        $cols = implode(',', array_map(fn($k) => "`$k`", array_keys($f)));
        $phs  = implode(',', array_fill(0, count($f), '?'));
        $db->prepare("INSERT INTO b2g_municipalities (id,$cols) VALUES (?,$phs)")->execute([$id, ...array_values($f)]);
    }
    $msg = '✅ Comune salvato.';
}
render:

if (isset($_GET['delete'])) {
    $db->prepare("DELETE FROM b2g_municipalities WHERE id=?")->execute([$_GET['delete']]);
    header('Location: comuni.php');
    exit;
}

$list = $db->query("SELECT id, municipality_name, province, subscription_status, tier FROM b2g_municipalities ORDER BY municipality_name ASC")->fetchAll();
$sel  = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM b2g_municipalities WHERE id=?");
    $stmt->execute([$_GET['edit']]);
    $sel = $stmt->fetch();
}

$pageTitle = 'Comuni B2G';
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
      <h3 class="font-semibold text-sm">Comuni (<?= count($list) ?>)</h3>
      <a href="comuni.php?edit=__new__" class="text-xs bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1 rounded-lg">+ Nuovo</a>
    </div>
    <div class="divide-y divide-slate-700 max-h-[65vh] overflow-y-auto">
      <?php foreach ($list as $item): ?>
      <a href="comuni.php?edit=<?= urlencode($item['id']) ?>"
         class="flex items-center justify-between px-4 py-3 hover:bg-slate-700 transition-colors <?= (isset($_GET['edit']) && $_GET['edit']===$item['id'] ? 'bg-slate-700' : '') ?>">
        <div>
          <div class="text-sm font-medium text-white"><?= htmlspecialchars($item['municipality_name']) ?></div>
          <div class="text-xs text-slate-400"><?= htmlspecialchars($item['province'] ?? '') ?> · <?= $item['subscription_status'] ?> · <?= $item['tier'] ?></div>
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
      <h3 class="font-semibold text-white mb-2"><?= $sel ? 'Modifica: ' . htmlspecialchars($sel['municipality_name']) : 'Nuovo comune' ?></h3>
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
        echo $inp('municipality_name','Nome Comune');
        echo $inp('borough_id','ID Borgo collegato');
        echo $inp('province','Provincia');
        echo $inp('region','Regione');
        echo $inp('population','Popolazione','number');
        echo $inp('mayor_name','Sindaco');
        echo $inp('mayor_email','Email Sindaco','email');
        echo $inp('contact_person','Referente');
        echo $inp('contact_email','Email referente','email');
        echo $inp('contact_phone','Telefono referente');
        echo $inp('pec_email','PEC','email');
        echo $inp('website_url','Sito web','url',true);
        echo $inp('annual_fee','Canone annuo €','number');
        echo $inp('subscription_start','Inizio abbonamento','date');
        echo $inp('subscription_end','Fine abbonamento','date');
        echo $inp('pnrr_measure','Misura PNRR','text',true);
        ?>
        <div>
          <label class="block text-xs text-slate-400 mb-1">Tier</label>
          <select name="tier" class="w-full bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600 focus:outline-none focus:border-emerald-500">
            <?php foreach (['BASE','STANDARD','PREMIUM'] as $t): ?>
            <option value="<?= $t ?>" <?= ($sel['tier']??'')===$t?'selected':'' ?>><?= $t ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-xs text-slate-400 mb-1">Stato abbonamento</label>
          <select name="subscription_status" class="w-full bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600 focus:outline-none focus:border-emerald-500">
            <?php foreach (['LEAD','CONTATTATO','DEMO','ATTIVO','SOSPESO','SCADUTO'] as $t): ?>
            <option value="<?= $t ?>" <?= ($sel['subscription_status']??'')===$t?'selected':'' ?>><?= $t ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <?php foreach ([
        ['notes','Note'],
        ['services_enabled','Servizi abilitati (JSON o uno per riga)'],
      ] as [$n,$l]): ?>
      <div>
        <label class="block text-xs text-slate-400 mb-1"><?= $l ?></label>
        <textarea name="<?= $n ?>" rows="3"
          class="w-full bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600 focus:outline-none focus:border-emerald-500"><?= htmlspecialchars($sel[$n]??'') ?></textarea>
      </div>
      <?php endforeach; ?>
      <div class="flex gap-4 flex-wrap text-sm">
        <label class="flex items-center gap-2 text-slate-300">
          <input type="checkbox" name="pnrr_funded" <?= !empty($sel['pnrr_funded'])?'checked':'' ?> class="rounded">
          Finanziato PNRR
        </label>
      </div>
      <div class="flex gap-3 pt-2">
        <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg transition-colors">Salva</button>
        <?php if ($sel): ?>
        <a href="comuni.php?delete=<?= urlencode($sel['id']) ?>"
           onclick="return confirm('Eliminare questo comune?')"
           class="px-6 py-2.5 bg-red-700 hover:bg-red-600 text-white text-sm rounded-lg transition-colors">Elimina</a>
        <?php endif; ?>
        <a href="comuni.php" class="px-6 py-2.5 bg-slate-600 hover:bg-slate-500 text-white text-sm rounded-lg transition-colors">Annulla</a>
      </div>
    </form>
    <?php else: ?>
    <div class="bg-slate-800 rounded-xl border border-slate-700 p-12 text-center">
      <div class="text-4xl mb-3">🏛️</div>
      <p class="text-slate-400">Seleziona un comune o aggiungine uno nuovo.</p>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require '_footer.php'; ?>
