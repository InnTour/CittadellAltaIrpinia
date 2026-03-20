<?php
require_once __DIR__ . '/../config/db.php';
requireAdminSession();
$db = getDB();
$msg = '';

// ── AUTO-MIGRATE: crea le tabelle se non esistono ──────────
$db->exec("CREATE TABLE IF NOT EXISTS `admin_users` (
  `id`            VARCHAR(40)   NOT NULL,
  `name`          VARCHAR(200)  NOT NULL,
  `email`         VARCHAR(200)  NOT NULL,
  `password_hash` VARCHAR(255)  NOT NULL DEFAULT '',
  `role`          ENUM('visitatore','registrato','operatore','admin') NOT NULL DEFAULT 'registrato',
  `borough_id`    VARCHAR(100)  DEFAULT NULL COMMENT 'Borgo assegnato (solo per operatore)',
  `company_id`    VARCHAR(100)  DEFAULT NULL COMMENT 'Azienda assegnata (solo per operatore)',
  `phone`         VARCHAR(50)   DEFAULT NULL,
  `bio`           TEXT          DEFAULT NULL,
  `is_active`     TINYINT(1)    NOT NULL DEFAULT 1,
  `last_login_at` TIMESTAMP     NULL DEFAULT NULL,
  `created_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// ── ELIMINA ────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $did = $_GET['delete'];
    // Non si può eliminare l'unico admin
    $adminCount = $db->query("SELECT COUNT(*) FROM admin_users WHERE role='admin'")->fetchColumn();
    $targetRole = $db->prepare("SELECT role FROM admin_users WHERE id=?");
    $targetRole->execute([$did]);
    $tr = $targetRole->fetchColumn();
    if ($tr === 'admin' && $adminCount <= 1) {
        $msg = '❌ Impossibile eliminare l\'unico amministratore.';
    } else {
        $db->prepare("DELETE FROM admin_users WHERE id=?")->execute([$did]);
        header('Location: utenti.php');
        exit;
    }
}

// ── SALVA (POST) ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id       = trim($_POST['id'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $name     = trim($_POST['name'] ?? '');
    $role     = $_POST['role'] ?? 'registrato';
    $phone    = trim($_POST['phone'] ?? '');
    $bio      = trim($_POST['bio'] ?? '');
    $borough  = trim($_POST['borough_id'] ?? '');
    $company  = trim($_POST['company_id'] ?? '');
    $active   = isset($_POST['is_active']) ? 1 : 0;
    $password = trim($_POST['password'] ?? '');

    if (!$id || !$email || !$name) {
        $msg = '❌ ID, email e nome sono obbligatori.';
        goto render;
    }

    $exists = $db->prepare("SELECT id FROM admin_users WHERE id=?");
    $exists->execute([$id]);

    if ($exists->fetch()) {
        // UPDATE
        $f = [
            'name'       => $name,
            'email'      => $email,
            'role'       => $role,
            'phone'      => $phone ?: null,
            'bio'        => $bio ?: null,
            'borough_id' => ($role === 'operatore' && $borough) ? $borough : null,
            'company_id' => ($role === 'operatore' && $company) ? $company : null,
            'is_active'  => $active,
        ];
        if ($password !== '') {
            $f['password_hash'] = password_hash($password, PASSWORD_BCRYPT);
        }
        $set = implode(',', array_map(fn($k) => "`$k`=?", array_keys($f)));
        $db->prepare("UPDATE admin_users SET $set WHERE id=?")->execute([...array_values($f), $id]);
        $msg = '✅ Utente aggiornato.';
    } else {
        // INSERT
        if ($password === '') {
            $msg = '❌ La password è obbligatoria per i nuovi utenti.';
            goto render;
        }
        $emailCheck = $db->prepare("SELECT id FROM admin_users WHERE email=?");
        $emailCheck->execute([$email]);
        if ($emailCheck->fetch()) {
            $msg = '❌ Email già registrata.';
            goto render;
        }
        $f = [
            'name'          => $name,
            'email'         => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'role'          => $role,
            'phone'         => $phone ?: null,
            'bio'           => $bio ?: null,
            'borough_id'    => ($role === 'operatore' && $borough) ? $borough : null,
            'company_id'    => ($role === 'operatore' && $company) ? $company : null,
            'is_active'     => $active,
        ];
        $cols = implode(',', array_map(fn($k) => "`$k`", array_keys($f)));
        $phs  = implode(',', array_fill(0, count($f), '?'));
        $db->prepare("INSERT INTO admin_users (id,$cols) VALUES (?,$phs)")->execute([$id, ...array_values($f)]);
        $msg = '✅ Utente creato.';
    }
}
render:

// ── LISTA + FORM ───────────────────────────────────────────
$list = $db->query("
    SELECT id, name, email, role, is_active, last_login_at
    FROM admin_users
    ORDER BY role ASC, name ASC
")->fetchAll();

// Statistiche
$stats = ['visitatore'=>0,'registrato'=>0,'operatore'=>0,'admin'=>0];
foreach ($list as $u) {
    if (isset($stats[$u['role']])) $stats[$u['role']]++;
}

$sel = null;
if (isset($_GET['edit'])) {
    if ($_GET['edit'] === '__new__') {
        $sel = ['id'=>'','name'=>'','email'=>'','role'=>'registrato','phone'=>'','bio'=>'',
                'borough_id'=>'','company_id'=>'','is_active'=>1];
    } else {
        $stmt = $db->prepare("SELECT * FROM admin_users WHERE id=?");
        $stmt->execute([$_GET['edit']]);
        $sel = $stmt->fetch() ?: null;
    }
}

// Elenco borghi e aziende per i select
$borghi   = $db->query("SELECT id, name FROM boroughs ORDER BY name ASC")->fetchAll();
$aziende  = $db->query("SELECT id, name FROM companies ORDER BY name ASC")->fetchAll();

$pageTitle = 'Utenti';
require '_layout.php';

$roleBadge = function(string $role): string {
    return match($role) {
        'admin'      => '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-900/60 text-emerald-300 border border-emerald-700">Admin</span>',
        'operatore'  => '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-900/60 text-amber-300 border border-amber-700">Operatore</span>',
        'registrato' => '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-900/60 text-blue-300 border border-blue-700">Registrato</span>',
        default      => '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-700 text-slate-400">Visitatore</span>',
    };
};
?>

<?php if ($msg): ?>
<div class="mb-4 px-4 py-3 rounded-lg text-sm <?= str_starts_with($msg,'✅') ? 'bg-emerald-900/40 border border-emerald-600 text-emerald-300' : 'bg-red-900/40 border border-red-600 text-red-300' ?>">
  <?= htmlspecialchars($msg) ?>
</div>
<?php endif; ?>

<!-- Stats -->
<div class="grid grid-cols-4 gap-3 mb-6">
  <?php foreach ([
    ['visitatore', 'Visitatori',  '👤', 'slate'],
    ['registrato', 'Registrati',  '🙋', 'blue'],
    ['operatore',  'Operatori',   '🏔️', 'amber'],
    ['admin',      'Amministratori','🔑','emerald'],
  ] as [$role,$label,$icon,$color]): ?>
  <div class="bg-slate-800 rounded-xl border border-slate-700 px-4 py-3 text-center">
    <div class="text-xl mb-1"><?= $icon ?></div>
    <div class="text-2xl font-bold text-white"><?= $stats[$role] ?></div>
    <div class="text-xs text-slate-400"><?= $label ?></div>
  </div>
  <?php endforeach; ?>
</div>

<div class="grid md:grid-cols-3 gap-6">
  <!-- Lista utenti -->
  <div class="md:col-span-1 bg-slate-800 rounded-xl border border-slate-700 overflow-hidden">
    <div class="px-4 py-3 border-b border-slate-700 flex items-center justify-between">
      <h3 class="font-semibold text-sm">Utenti (<?= count($list) ?>)</h3>
      <a href="utenti.php?edit=__new__" class="text-xs bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1 rounded-lg transition-colors">+ Nuovo</a>
    </div>
    <div class="divide-y divide-slate-700 max-h-[60vh] overflow-y-auto">
      <?php foreach ($list as $u): ?>
      <a href="utenti.php?edit=<?= urlencode($u['id']) ?>"
         class="flex items-center justify-between px-4 py-3 hover:bg-slate-700 transition-colors <?= (isset($_GET['edit']) && $_GET['edit']===$u['id']) ? 'bg-slate-700' : '' ?>">
        <div class="min-w-0 flex-1">
          <div class="flex items-center gap-2">
            <span class="text-sm font-medium text-white truncate"><?= htmlspecialchars($u['name']) ?></span>
            <?php if (!$u['is_active']): ?>
              <span class="text-xs text-slate-500">(inattivo)</span>
            <?php endif; ?>
          </div>
          <div class="flex items-center gap-2 mt-0.5">
            <?= $roleBadge($u['role']) ?>
            <span class="text-xs text-slate-500 truncate"><?= htmlspecialchars($u['email']) ?></span>
          </div>
        </div>
        <span class="text-slate-500 ml-2">›</span>
      </a>
      <?php endforeach; ?>
      <?php if (empty($list)): ?>
      <div class="px-4 py-8 text-center text-slate-500 text-sm">
        Nessun utente. Creane uno con il pulsante + Nuovo.
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Form modifica / creazione -->
  <div class="md:col-span-2">
    <?php if ($sel !== null): ?>
    <form method="POST" class="bg-slate-800 rounded-xl border border-slate-700 p-6 space-y-4">
      <h3 class="font-semibold text-white">
        <?= (isset($_GET['edit']) && $_GET['edit']==='__new__') ? 'Nuovo utente' : 'Modifica: ' . htmlspecialchars($sel['name'] ?? '') ?>
      </h3>

      <!-- ID + Nome + Email -->
      <div class="grid grid-cols-2 gap-4">
        <?php
        $inp = fn($n,$l,$t='text',$rd=false) =>
          '<div>
            <label class="block text-xs text-slate-400 mb-1">'.$l.'</label>
            <input type="'.$t.'" name="'.$n.'" value="'.htmlspecialchars($sel[$n]??'').'"
              '.($rd?'readonly class="w-full bg-slate-900 text-slate-400 rounded-lg px-3 py-2 text-sm border border-slate-700 cursor-not-allowed"'
                      :'class="w-full bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600 focus:outline-none focus:border-emerald-500"').'>
          </div>';
        echo $inp('id',  'ID (slug, es: mario-rossi)', 'text', !empty($sel['id']) && $_GET['edit']!=='__new__');
        echo $inp('name','Nome completo');
        echo $inp('email','Email','email');
        echo $inp('phone','Telefono (opzionale)');
        ?>

        <!-- Ruolo -->
        <div>
          <label class="block text-xs text-slate-400 mb-1">Ruolo</label>
          <select name="role" id="roleSelect"
            class="w-full bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600 focus:outline-none focus:border-emerald-500"
            onchange="document.getElementById('operatoreFields').style.display = this.value==='operatore'?'block':'none'">
            <?php foreach (['visitatore'=>'👤 Visitatore','registrato'=>'🙋 Registrato','operatore'=>'🏔️ Operatore borgo','admin'=>'🔑 Amministratore'] as $v=>$l): ?>
            <option value="<?= $v ?>" <?= ($sel['role']??'')===$v?'selected':'' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
          <p class="text-xs text-slate-500 mt-1">
            Visitatore: solo lettura · Registrato: wishlist+prenotazioni · Operatore: gestisce borgo/azienda assegnata · Admin: accesso completo
          </p>
        </div>

        <!-- Stato -->
        <div class="flex items-center">
          <label class="flex items-center gap-2 text-slate-300 text-sm cursor-pointer mt-4">
            <input type="checkbox" name="is_active" <?= !empty($sel['is_active'])?'checked':'' ?> class="rounded w-4 h-4">
            Utente attivo
          </label>
        </div>
      </div>

      <!-- Campi operatore (borgo/azienda assegnati) -->
      <div id="operatoreFields" style="display:<?= ($sel['role']??'')==='operatore'?'block':'none' ?>">
        <div class="border border-amber-800/50 bg-amber-900/10 rounded-lg p-4 space-y-3">
          <p class="text-xs text-amber-400 font-medium">⚙️ Assegnazioni operatore</p>
          <div class="grid grid-cols-2 gap-4">
            <!-- Borgo assegnato -->
            <div>
              <label class="block text-xs text-slate-400 mb-1">Borgo assegnato</label>
              <select name="borough_id" class="w-full bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600 focus:outline-none focus:border-emerald-500">
                <option value="">— Nessuno —</option>
                <?php foreach ($borghi as $b): ?>
                <option value="<?= htmlspecialchars($b['id']) ?>" <?= ($sel['borough_id']??'')===$b['id']?'selected':'' ?>>
                  <?= htmlspecialchars($b['name']) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <!-- Azienda assegnata -->
            <div>
              <label class="block text-xs text-slate-400 mb-1">Azienda assegnata</label>
              <select name="company_id" class="w-full bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600 focus:outline-none focus:border-emerald-500">
                <option value="">— Nessuna —</option>
                <?php foreach ($aziende as $a): ?>
                <option value="<?= htmlspecialchars($a['id']) ?>" <?= ($sel['company_id']??'')===$a['id']?'selected':'' ?>>
                  <?= htmlspecialchars($a['name']) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <p class="text-xs text-slate-500">L'operatore potrà modificare i contenuti del borgo/azienda assegnati.</p>
        </div>
      </div>

      <!-- Bio -->
      <div>
        <label class="block text-xs text-slate-400 mb-1">Bio / Note (opzionale)</label>
        <textarea name="bio" rows="2"
          class="w-full bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600 focus:outline-none focus:border-emerald-500"><?= htmlspecialchars($sel['bio']??'') ?></textarea>
      </div>

      <!-- Password -->
      <div class="border border-slate-600 rounded-lg p-4">
        <label class="block text-xs text-slate-400 mb-1">
          <?= (!empty($sel['id']) && $_GET['edit']!=='__new__') ? 'Nuova password (lascia vuoto per non cambiare)' : 'Password *' ?>
        </label>
        <input type="password" name="password" autocomplete="new-password" minlength="8"
          placeholder="<?= (!empty($sel['id']) && $_GET['edit']!=='__new__') ? 'Lascia vuoto per mantenere la password attuale' : 'Minimo 8 caratteri' ?>"
          class="w-full bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600 focus:outline-none focus:border-emerald-500">
        <p class="text-xs text-slate-500 mt-1">Minimo 8 caratteri. Verrà hashata con bcrypt.</p>
      </div>

      <?php if (!empty($sel['last_login_at'])): ?>
      <p class="text-xs text-slate-500">
        Ultimo accesso: <?= htmlspecialchars($sel['last_login_at']) ?>
      </p>
      <?php endif; ?>

      <div class="flex gap-3 pt-2">
        <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg transition-colors">
          Salva utente
        </button>
        <?php if (!empty($sel['id']) && $_GET['edit']!=='__new__'): ?>
        <a href="utenti.php?delete=<?= urlencode($sel['id']) ?>"
           onclick="return confirm('Eliminare definitivamente questo utente?')"
           class="px-6 py-2.5 bg-red-700 hover:bg-red-600 text-white text-sm rounded-lg transition-colors">Elimina</a>
        <?php endif; ?>
        <a href="utenti.php" class="px-6 py-2.5 bg-slate-600 hover:bg-slate-500 text-white text-sm rounded-lg transition-colors">Annulla</a>
      </div>
    </form>
    <?php else: ?>
    <div class="bg-slate-800 rounded-xl border border-slate-700 p-12 text-center space-y-4">
      <div class="text-5xl">👥</div>
      <p class="text-slate-400">Seleziona un utente dalla lista o creane uno nuovo.</p>
      <div class="max-w-sm mx-auto text-left mt-6 space-y-2 text-xs text-slate-500">
        <p class="font-semibold text-slate-400 mb-1">Livelli di accesso:</p>
        <p><?= $roleBadge('visitatore') ?> &nbsp;Solo contenuti pubblici, nessun login</p>
        <p><?= $roleBadge('registrato') ?> &nbsp;Wishlist, prenotazioni, recensioni</p>
        <p><?= $roleBadge('operatore') ?> &nbsp;Gestione borgo/azienda assegnati</p>
        <p><?= $roleBadge('admin') ?> &nbsp;Accesso completo al pannello</p>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require '_footer.php'; ?>
