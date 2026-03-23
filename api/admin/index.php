<?php
require_once __DIR__ . '/../config/db.php';
requireAdminSession();
$db = getDB();

$counts = [
    'borghi'      => $db->query("SELECT COUNT(*) FROM boroughs")->fetchColumn(),
    'aziende'     => $db->query("SELECT COUNT(*) FROM companies")->fetchColumn(),
    'esperienze'  => $db->query("SELECT COUNT(*) FROM experiences")->fetchColumn(),
    'artigianato' => $db->query("SELECT COUNT(*) FROM craft_products")->fetchColumn(),
];
// Nuove tabelle (potrebbero non esistere ancora — gestisci con try/catch)
foreach (['prodotti' => 'food_products', 'ospitalita' => 'accommodations', 'ristorazione' => 'restaurants', 'comuni' => 'b2g_municipalities'] as $k => $t) {
    try { $counts[$k] = $db->query("SELECT COUNT(*) FROM `$t`")->fetchColumn(); }
    catch (PDOException $e) { $counts[$k] = '–'; }
}

$publishResult = null;
if (isset($_GET['publish'])) {
    try {
        require_once __DIR__ . '/../export/_generate_functions.php';
        $generated = [
            'boroughs'       => generateBoroughs($db),
            'companies'      => generateCompanies($db),
            'experiences'    => generateExperiences($db),
            'crafts'         => generateCrafts($db),
            'food_products'  => generateFoodProducts($db),
            'accommodations' => generateAccommodations($db),
            'restaurants'    => generateRestaurants($db),
        ];
        $publishResult = ['ok' => true, 'generated' => $generated];
    } catch (Throwable $e) {
        $publishResult = ['error' => $e->getMessage()];
    }
}

$pageTitle = 'Dashboard';
require '_layout.php';
?>

<?php if ($publishResult): ?>
  <div class="mb-6 p-4 rounded-xl <?= isset($publishResult['ok']) ? 'bg-emerald-900/40 border border-emerald-600 text-emerald-300' : 'bg-red-900/40 border border-red-600 text-red-300' ?>">
    <?php if (isset($publishResult['ok'])): ?>
      ✅ Dati pubblicati con successo!
      <ul class="mt-2 text-xs space-y-0.5">
        <?php foreach ($publishResult['generated'] ?? [] as $k => $v): ?>
          <li><span class="font-mono"><?= htmlspecialchars($k) ?></span>: <?= htmlspecialchars($v) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      ❌ Errore: <?= htmlspecialchars($publishResult['error'] ?? 'Errore sconosciuto') ?>
    <?php endif; ?>
  </div>
<?php endif; ?>

<!-- Stats -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
  <?php foreach ([
    ['borghi', '🏔️', 'Borghi'],
    ['aziende', '🏢', 'Aziende'],
    ['esperienze', '🎭', 'Esperienze'],
    ['artigianato', '🏺', 'Artigianato'],
    ['prodotti', '🧀', 'Prodotti Food'],
    ['ospitalita', '🏨', 'Ospitalità'],
    ['ristorazione', '🍽️', 'Ristorazione'],
    ['comuni', '🏛️', 'Comuni B2G'],
  ] as [$key, $icon, $label]): ?>
  <div class="bg-slate-800 rounded-xl p-5 border border-slate-700">
    <div class="text-2xl mb-2"><?= $icon ?></div>
    <div class="text-3xl font-bold text-emerald-400"><?= $counts[$key] ?></div>
    <div class="text-slate-400 text-sm mt-1"><?= $label ?></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Publish button -->
<div class="bg-slate-800 rounded-xl p-6 border border-slate-700 mb-6">
  <h3 class="font-semibold text-white mb-2">Pubblica i dati</h3>
  <p class="text-slate-400 text-sm mb-4">
    Rigenera i file JS della SPA a partire dal database MySQL.
    Dopo la pubblicazione le modifiche saranno visibili sul sito.
  </p>
  <a href="?publish=1"
    class="inline-flex items-center gap-2 px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg transition-colors">
    🚀 Pubblica ora
  </a>
</div>

<!-- Quick links -->
<div class="grid md:grid-cols-2 gap-4">
  <?php foreach ([
    ['/api/admin/borghi.php',         '🏔️', 'Gestisci Borghi',       'Modifica descrizioni, ristoranti, immagini'],
    ['/api/admin/aziende.php',        '🏢', 'Gestisci Aziende',      'Aggiorna schede produttori e artigiani'],
    ['/api/admin/esperienze.php',     '🎭', 'Gestisci Esperienze',   'Modifica esperienze turistiche'],
    ['/api/admin/artigianato.php',    '🏺', 'Gestisci Artigianato',  'Aggiorna prodotti artigianali'],
    ['/api/admin/prodotti.php',       '🧀', 'Gestisci Prodotti Food','Formaggi, salumi, eccellenze gastronomiche'],
    ['/api/admin/ospitalita.php',     '🏨', 'Gestisci Ospitalità',   'Masserie, agriturismi, B&B'],
    ['/api/admin/ristorazione.php',   '🍽️', 'Gestisci Ristorazione', 'Ristoranti, trattorie, osterie'],
    ['/api/admin/comuni.php',        '🏛️', 'Gestisci Comuni B2G',   'Programma abbonamenti Pubbliche Amministrazioni'],
    ['/api/admin/statistiche.php',   '📊', 'Statistiche & Analytics','Visualizzazioni, KPI e reportistica'],
    ['/api/admin/seed_lacedonia.php', '🌱', 'Seed Lacedonia',        'Popola il DB con i dati di esempio Lacedonia'],
  ] as [$url, $icon, $title, $desc]): ?>
  <a href="<?= $url ?>" class="bg-slate-800 hover:bg-slate-700 rounded-xl p-5 border border-slate-700 transition-colors group">
    <div class="text-xl mb-2"><?= $icon ?></div>
    <div class="font-semibold text-white group-hover:text-emerald-400 transition-colors"><?= $title ?></div>
    <div class="text-slate-400 text-xs mt-1"><?= $desc ?></div>
  </a>
  <?php endforeach; ?>
</div>

<?php require '_footer.php'; ?>
