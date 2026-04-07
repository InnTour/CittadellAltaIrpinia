<?php
require_once __DIR__ . '/../config/db.php';
requireAdminSession();
$db = getDB();

// ── Crea tabella se non esiste ──────────────────────────────
$db->exec("CREATE TABLE IF NOT EXISTS `survey_submissions` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `entity_type`     VARCHAR(50)   NOT NULL,
  `submitter_name`  VARCHAR(200)  DEFAULT NULL,
  `submitter_email` VARCHAR(200)  DEFAULT NULL,
  `submitter_phone` VARCHAR(50)   DEFAULT NULL,
  `entity_name`     VARCHAR(300)  DEFAULT NULL,
  `data`            JSON          NOT NULL,
  `status`          ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `submitted_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reviewed_at`     TIMESTAMP     NULL,
  `reviewed_by`     VARCHAR(200)  DEFAULT NULL,
  `notes`           TEXT          DEFAULT NULL,
  INDEX `idx_status` (`status`),
  INDEX `idx_entity` (`entity_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$msg  = '';
$view = null;

// ── Azione: approvazione (import nel DB) ───────────────────
if (isset($_GET['approve'])) {
    $sid = (int)$_GET['approve'];
    $stmt = $db->prepare("SELECT * FROM survey_submissions WHERE id = ?");
    $stmt->execute([$sid]);
    $sub = $stmt->fetch();

    if ($sub && $sub['status'] === 'pending') {
        $data = json_decode($sub['data'], true) ?? [];
        $type = $sub['entity_type'];

        $tableMap = [
            'azienda'      => ['companies',       ['id','slug','name','legal_name','vat_number','type','tagline','description_short','description_long','founding_year','employees_count','borough_id','address_full','lat','lng','contact_email','contact_phone','website_url','social_instagram','social_facebook','social_linkedin','founder_name','founder_quote']],
            'esperienza'   => ['experiences',     ['id','slug','title','tagline','description_short','description_long','category','provider_id','borough_id','lat','lng','duration_minutes','max_participants','min_participants','price_per_person','difficulty_level','cancellation_policy','accessibility_info']],
            'artigianato'  => ['craft_products',  ['id','slug','name','description_short','description_long','price','artisan_id','borough_id','technique_description','dimensions','weight_grams','lead_time_days','is_custom_order_available','is_unique_piece']],
            'prodotto_food'=> ['food_products',   ['id','slug','name','producer_id','borough_id','category','description_short','description_long','tagline','price','unit','weight_grams','shelf_life_days','storage_instructions','origin_protected','allergens','ingredients','pairing_suggestions','is_shippable']],
            'ristorazione' => ['restaurants',     ['id','slug','name','type','borough_id','address_full','lat','lng','description_short','description_long','tagline','cuisine_type','price_range','seats_indoor','seats_outdoor','opening_hours','closing_day','specialties','menu_highlights','contact_email','contact_phone','website_url','social_instagram','booking_url','founder_name']],
            'ospitalita'   => ['accommodations',  ['id','slug','name','type','provider_id','borough_id','address_full','lat','lng','description_short','description_long','tagline','rooms_count','max_guests','price_per_night_from','stars_or_category','check_in_time','check_out_time','min_stay_nights','amenities','booking_email','booking_phone','booking_url','contact_email','contact_phone','website_url','social_instagram']],
        ];

        if (!array_key_exists($type, $tableMap)) {
            $msg = '❌ Tipo entità non supportato per l\'importazione automatica.';
        } else {
            [$table, $cols] = $tableMap[$type];

            // Normalizza nome_campo del sondaggio (survey usa 'name' per title nelle esperienze)
            if ($type === 'esperienza' && empty($data['title']) && !empty($data['name'])) {
                $data['title'] = $data['name'];
            }

            // Auto-slug/id
            if (empty($data['id']) || empty($data['slug'])) {
                $base = $data['title'] ?? $data['name'] ?? 'entita-' . $sid;
                $slug = strtolower(preg_replace('/[^a-z0-9-]/', '-', preg_replace('/\s+/', '-', $base)));
                $slug = preg_replace('/-+/', '-', trim($slug, '-'));
                $slug = substr($slug, 0, 80);
                $data['id']   = $data['id']   ?: $slug;
                $data['slug'] = $data['slug'] ?: $slug;
            }

            $insertCols = [];
            $insertVals = [];
            foreach ($cols as $col) {
                if (array_key_exists($col, $data) && $data[$col] !== '') {
                    $insertCols[] = "`$col`";
                    $insertVals[] = $data[$col];
                }
            }

            if (empty($insertCols)) {
                $msg = '❌ Nessun dato valido da importare.';
            } else {
                $colStr    = implode(',', $insertCols);
                $phStr     = implode(',', array_fill(0, count($insertVals), '?'));
                $updateStr = implode(',', array_map(fn($c) => "$c=VALUES($c)", $insertCols));
                try {
                    $db->prepare("INSERT INTO `$table` ($colStr) VALUES ($phStr) ON DUPLICATE KEY UPDATE $updateStr")
                       ->execute($insertVals);
                    $db->prepare("UPDATE survey_submissions SET status='approved', reviewed_at=NOW(), reviewed_by=? WHERE id=?")
                       ->execute([($_SESSION['admin_user_name'] ?? 'admin'), $sid]);
                    $msg = '✅ Scheda importata con successo nella tabella <strong>' . htmlspecialchars($table) . '</strong>.';
                } catch (\Exception $e) {
                    $msg = '❌ Errore import: ' . htmlspecialchars($e->getMessage());
                }
            }
        }
    }
}

// ── Azione: rifiuto ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_id'])) {
    $sid   = (int)$_POST['reject_id'];
    $notes = trim($_POST['rejection_notes'] ?? '');
    $db->prepare("UPDATE survey_submissions SET status='rejected', reviewed_at=NOW(), reviewed_by=?, notes=? WHERE id=?")
       ->execute([($_SESSION['admin_user_name'] ?? 'admin'), $notes, $sid]);
    $msg = 'Scheda rifiutata.';
}

// ── Visualizza dettaglio ───────────────────────────────────
if (isset($_GET['view'])) {
    $stmt = $db->prepare("SELECT * FROM survey_submissions WHERE id = ?");
    $stmt->execute([(int)$_GET['view']]);
    $view = $stmt->fetch() ?: null;
}

// ── Lista filtrabile ───────────────────────────────────────
$filterStatus = $_GET['status'] ?? 'pending';
$filterType   = $_GET['type']   ?? '';

$where  = [];
$params = [];
if ($filterStatus && $filterStatus !== 'all') { $where[] = 'status = ?'; $params[] = $filterStatus; }
if ($filterType)                              { $where[] = 'entity_type = ?'; $params[] = $filterType; }
$whereStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$list = $db->prepare("SELECT id, entity_type, submitter_name, submitter_email, entity_name, status, submitted_at FROM survey_submissions $whereStr ORDER BY submitted_at DESC LIMIT 200");
$list->execute($params);
$submissions = $list->fetchAll();

// Contatori per badge
$counts = $db->query("SELECT status, COUNT(*) as n FROM survey_submissions GROUP BY status")->fetchAll();
$cntMap = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
foreach ($counts as $c) $cntMap[$c['status']] = (int)$c['n'];

$pageTitle = 'Sondaggi';
require '_layout.php';

$statusBadge = function(string $s): string {
    return match($s) {
        'pending'  => '<span class="px-2 py-0.5 rounded text-xs bg-yellow-900/60 text-yellow-300 border border-yellow-700">In attesa</span>',
        'approved' => '<span class="px-2 py-0.5 rounded text-xs bg-emerald-900/60 text-emerald-300 border border-emerald-700">Approvata</span>',
        'rejected' => '<span class="px-2 py-0.5 rounded text-xs bg-red-900/60 text-red-300 border border-red-700">Rifiutata</span>',
        default    => '<span class="px-2 py-0.5 rounded text-xs bg-slate-700 text-slate-400">' . htmlspecialchars($s) . '</span>',
    };
};

$typeIcon = function(string $t): string {
    return match($t) {
        'azienda'       => '🏢', 'esperienza'    => '🎭',
        'artigianato'   => '🏺', 'prodotto_food' => '🧀',
        'ristorazione'  => '🍽️', 'ospitalita'    => '🏨',
        default         => '📋',
    };
};
?>

<?php if ($msg): ?>
<div class="mb-4 px-4 py-3 rounded-lg text-sm <?= str_contains($msg,'✅') ? 'bg-emerald-900/40 border border-emerald-600 text-emerald-300' : 'bg-red-900/40 border border-red-600 text-red-300' ?>">
  <?= $msg ?>
</div>
<?php endif; ?>

<!-- Stat cards -->
<div class="grid grid-cols-3 gap-3 mb-6">
  <?php foreach ([['pending','⏳','In attesa','yellow'],['approved','✅','Approvate','emerald'],['rejected','❌','Rifiutate','red']] as [$s,$ic,$lbl,$col]): ?>
  <a href="sondaggi.php?status=<?= $s ?>"
     class="bg-slate-800 rounded-xl border border-slate-700 p-4 text-center hover:bg-slate-700 transition-colors <?= $filterStatus===$s ? 'ring-2 ring-'.$col.'-500' : '' ?>">
    <div class="text-xl"><?= $ic ?></div>
    <div class="text-2xl font-bold text-white mt-1"><?= $cntMap[$s] ?></div>
    <div class="text-xs text-slate-400 mt-0.5"><?= $lbl ?></div>
  </a>
  <?php endforeach; ?>
</div>

<!-- Link al sondaggio pubblico -->
<div class="mb-5 flex items-center gap-3 px-4 py-3 bg-slate-800 border border-slate-700 rounded-xl text-sm">
  <span class="text-slate-400">Link sondaggio da condividere:</span>
  <code class="text-emerald-400 bg-slate-900 px-2 py-0.5 rounded text-xs select-all">/api/survey.php</code>
  <a href="/api/survey.php" target="_blank" class="text-emerald-400 hover:text-emerald-300 text-xs">Apri ↗</a>
  <span class="text-slate-600 text-xs ml-auto">Aggiungi ?type=azienda (o esperienza, ristorazione, ecc.) per pre-selezionare il tipo.</span>
</div>

<div class="grid md:grid-cols-3 gap-6">

  <!-- Lista submissions -->
  <div class="md:col-span-1 bg-slate-800 rounded-xl border border-slate-700 overflow-hidden">
    <div class="px-4 py-3 border-b border-slate-700 flex items-center justify-between">
      <div>
        <span class="font-semibold text-sm text-white">Invii (<?= count($submissions) ?>)</span>
        <span class="text-xs text-slate-500 ml-2"><?= $filterStatus === 'all' ? 'tutti' : htmlspecialchars($filterStatus) ?></span>
      </div>
      <div class="flex gap-1">
        <?php foreach (['pending'=>'In attesa','approved'=>'OK','rejected'=>'NO','all'=>'Tutti'] as $s => $lbl): ?>
        <a href="sondaggi.php?status=<?= $s ?><?= $filterType ? '&type='.$filterType : '' ?>"
           class="text-xs px-2 py-1 rounded <?= $filterStatus===$s ? 'bg-emerald-700 text-white' : 'bg-slate-700 text-slate-300 hover:bg-slate-600' ?> transition-colors">
          <?= $lbl ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="divide-y divide-slate-700 max-h-[70vh] overflow-y-auto">
      <?php foreach ($submissions as $sub): ?>
      <a href="sondaggi.php?view=<?= $sub['id'] ?>&status=<?= $filterStatus ?>"
         class="flex items-start gap-3 px-4 py-3 hover:bg-slate-700 transition-colors <?= ($view && $view['id']==$sub['id']) ? 'bg-slate-700' : '' ?>">
        <span class="text-xl mt-0.5"><?= $typeIcon($sub['entity_type']) ?></span>
        <div class="min-w-0 flex-1">
          <div class="text-sm font-medium text-white truncate"><?= htmlspecialchars($sub['entity_name'] ?: '—') ?></div>
          <div class="text-xs text-slate-400 truncate"><?= htmlspecialchars($sub['submitter_name'] ?? '') ?> · <?= htmlspecialchars($sub['submitter_email'] ?? '') ?></div>
          <div class="flex items-center gap-2 mt-1">
            <?= $statusBadge($sub['status']) ?>
            <span class="text-xs text-slate-600"><?= date('d/m/Y', strtotime($sub['submitted_at'])) ?></span>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
      <?php if (empty($submissions)): ?>
      <div class="px-4 py-10 text-center text-slate-500 text-sm">Nessun invio trovato.</div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Dettaglio submission -->
  <div class="md:col-span-2">
    <?php if ($view): ?>
      <?php $viewData = json_decode($view['data'], true) ?? []; ?>
      <div class="bg-slate-800 rounded-xl border border-slate-700 p-6 space-y-5">

        <!-- Header -->
        <div class="flex items-start justify-between gap-4">
          <div>
            <div class="flex items-center gap-2 mb-1">
              <span class="text-2xl"><?= $typeIcon($view['entity_type']) ?></span>
              <h3 class="text-lg font-bold text-white"><?= htmlspecialchars($view['entity_name'] ?: 'Senza nome') ?></h3>
              <?= $statusBadge($view['status']) ?>
            </div>
            <div class="text-sm text-slate-400">
              Inviato da <strong class="text-slate-300"><?= htmlspecialchars($view['submitter_name'] ?? '') ?></strong>
              · <a href="mailto:<?= htmlspecialchars($view['submitter_email'] ?? '') ?>" class="text-emerald-400 hover:underline"><?= htmlspecialchars($view['submitter_email'] ?? '') ?></a>
              <?php if ($view['submitter_phone']): ?>· <?= htmlspecialchars($view['submitter_phone']) ?><?php endif; ?>
            </div>
            <div class="text-xs text-slate-500 mt-1">
              Ricevuto il <?= date('d/m/Y \a\l\l\e H:i', strtotime($view['submitted_at'])) ?>
              <?php if ($view['reviewed_at']): ?>
                · Revisionato da <em><?= htmlspecialchars($view['reviewed_by'] ?? '') ?></em> il <?= date('d/m/Y', strtotime($view['reviewed_at'])) ?>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Dati compilati -->
        <div class="border border-slate-700 rounded-lg overflow-hidden">
          <div class="px-4 py-2 bg-slate-700/50 text-xs font-semibold text-slate-300 uppercase tracking-wide">Dati della scheda</div>
          <div class="divide-y divide-slate-700/50 max-h-80 overflow-y-auto">
            <?php foreach ($viewData as $k => $v): ?>
              <?php if (in_array($k, ['id','slug','privacy_ok','note_aggiuntive'], true)) continue; ?>
              <?php if ($v === '' || $v === null) continue; ?>
              <div class="flex gap-3 px-4 py-2.5 text-sm">
                <span class="text-slate-500 w-40 shrink-0 font-mono text-xs mt-0.5"><?= htmlspecialchars($k) ?></span>
                <span class="text-slate-200 break-words"><?= nl2br(htmlspecialchars($v)) ?></span>
              </div>
            <?php endforeach; ?>
            <?php if (!empty($viewData['note_aggiuntive'])): ?>
              <div class="px-4 py-2.5 text-sm">
                <div class="text-xs text-slate-500 mb-1 font-semibold">NOTE AGGIUNTIVE</div>
                <div class="text-slate-200"><?= nl2br(htmlspecialchars($viewData['note_aggiuntive'])) ?></div>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Azioni (solo se pending) -->
        <?php if ($view['status'] === 'pending'): ?>
        <div class="flex gap-3 pt-2 flex-wrap">
          <a href="sondaggi.php?approve=<?= $view['id'] ?>&status=<?= $filterStatus ?>"
             onclick="return confirm('Importare questa scheda nel database?')"
             class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg transition-colors">
            ✅ Approva e importa
          </a>
          <button onclick="document.getElementById('rejectForm').style.display='block'; this.style.display='none'"
             class="px-5 py-2.5 bg-red-700 hover:bg-red-600 text-white text-sm rounded-lg transition-colors">
            ❌ Rifiuta
          </button>
        </div>

        <!-- Form rifiuto -->
        <form id="rejectForm" method="POST" style="display:none" class="border border-red-800/50 bg-red-900/10 rounded-lg p-4 space-y-3">
          <input type="hidden" name="reject_id" value="<?= $view['id'] ?>">
          <p class="text-xs text-red-400 font-semibold">Motivo del rifiuto (opzionale — verrà salvato nei log)</p>
          <textarea name="rejection_notes" rows="2" placeholder="Es: dati incompleti, non riguarda un borgo dell'Alta Irpinia..."
            class="w-full bg-slate-900 border border-red-800/50 text-white text-sm rounded-lg px-3 py-2 focus:outline-none focus:border-red-500"></textarea>
          <div class="flex gap-2">
            <button type="submit" class="px-5 py-2 bg-red-700 hover:bg-red-600 text-white text-sm rounded-lg transition-colors">Conferma rifiuto</button>
            <button type="button" onclick="this.closest('#rejectForm').style.display='none'" class="px-5 py-2 bg-slate-700 text-white text-sm rounded-lg">Annulla</button>
          </div>
        </form>

        <?php elseif ($view['status'] === 'approved'): ?>
        <div class="px-4 py-3 bg-emerald-900/20 border border-emerald-800/50 rounded-lg text-sm text-emerald-300">
          ✅ Scheda importata nel database il <?= date('d/m/Y', strtotime($view['reviewed_at'])) ?> da <em><?= htmlspecialchars($view['reviewed_by'] ?? '') ?></em>.
        </div>
        <?php else: ?>
        <div class="px-4 py-3 bg-red-900/20 border border-red-800/50 rounded-lg text-sm text-red-300">
          ❌ Rifiutata il <?= date('d/m/Y', strtotime($view['reviewed_at'])) ?>.
          <?php if ($view['notes']): ?><br><span class="text-slate-400">Nota: <?= htmlspecialchars($view['notes']) ?></span><?php endif; ?>
        </div>
        <?php endif; ?>

      </div>
    <?php else: ?>
    <div class="bg-slate-800 rounded-xl border border-slate-700 p-12 text-center">
      <div class="text-4xl mb-4">📋</div>
      <p class="text-slate-400 text-sm">Seleziona un invio dalla lista per visualizzarlo.</p>
      <p class="text-slate-500 text-xs mt-3">
        Puoi approvare una scheda per importarla automaticamente nel database,<br>
        oppure rifiutarla con una nota per il log.
      </p>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require '_footer.php'; ?>
