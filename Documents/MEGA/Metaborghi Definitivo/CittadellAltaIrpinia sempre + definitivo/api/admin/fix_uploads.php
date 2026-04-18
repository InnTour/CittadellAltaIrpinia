<?php
// api/admin/fix_uploads.php
// Crea directory uploads + audio, testa write, e crea un file log per vedere
// errori prossimi upload. Da eseguire UNA VOLTA dopo il deploy.
require_once __DIR__ . '/../config/db.php';
requireAdminSession();

$results = [];

function step(string $label, callable $fn, array &$results): void {
    try {
        $out = $fn();
        $results[] = ['ok' => true, 'label' => $label, 'detail' => $out ?: 'OK'];
    } catch (Throwable $e) {
        $results[] = ['ok' => false, 'label' => $label, 'detail' => $e->getMessage()];
    }
}

$root  = realpath(__DIR__ . '/..') . '/uploads';
$audio = $root . '/audio';
$logF  = $root . '/upload_debug.log';

step("1) Crea directory $root", function() use ($root) {
    if (is_dir($root)) return 'Gia esistente';
    if (!mkdir($root, 0775, true)) throw new Exception('mkdir fallita');
    return 'Creata con permessi 0775';
}, $results);

step("2) Crea directory $audio", function() use ($audio) {
    if (is_dir($audio)) return 'Gia esistente';
    if (!mkdir($audio, 0775, true)) throw new Exception('mkdir fallita');
    return 'Creata con permessi 0775';
}, $results);

step("3) Forza permessi 0775 su $root", function() use ($root) {
    if (!is_dir($root)) throw new Exception('Dir non esiste');
    return @chmod($root, 0775) ? 'chmod OK' : 'chmod fallita (potrebbe essere OK se owner diverso)';
}, $results);

step("4) Forza permessi 0775 su $audio", function() use ($audio) {
    if (!is_dir($audio)) throw new Exception('Dir non esiste');
    return @chmod($audio, 0775) ? 'chmod OK' : 'chmod fallita (potrebbe essere OK se owner diverso)';
}, $results);

step("5) Test write in $root", function() use ($root) {
    $testFile = $root . '/_testwrite_' . time() . '.txt';
    if (file_put_contents($testFile, 'ok') === false) throw new Exception('Scrittura fallita');
    @unlink($testFile);
    return 'Scrittura e cancellazione OK';
}, $results);

step("6) Test write in $audio", function() use ($audio) {
    $testFile = $audio . '/_testwrite_' . time() . '.txt';
    if (file_put_contents($testFile, 'ok') === false) throw new Exception('Scrittura fallita');
    @unlink($testFile);
    return 'Scrittura e cancellazione OK';
}, $results);

step("7) Crea log file $logF", function() use ($logF) {
    if (file_put_contents($logF, "# Upload debug log — " . date('Y-m-d H:i:s') . "\n", FILE_APPEND) === false) {
        throw new Exception('Impossibile scrivere log');
    }
    @chmod($logF, 0664);
    return 'Log pronto per tracciare upload';
}, $results);

$pageTitle = 'Fix Uploads';
require '_layout.php';
?>
<div class="flex-1 overflow-auto p-6">
<div class="max-w-3xl mx-auto">

<h2 class="text-2xl font-bold text-white mb-6">&#128295; Fix Uploads &mdash; Setup Directory</h2>

<div class="bg-slate-800 rounded-2xl border border-slate-700 p-5 mb-6">
<?php foreach ($results as $r): ?>
  <div class="flex items-start gap-3 py-2 border-b border-slate-700/50">
    <span class="text-xl leading-none mt-0.5"><?= $r['ok'] ? '<span class="text-emerald-400">&check;</span>' : '<span class="text-red-400">&times;</span>' ?></span>
    <div class="flex-1">
      <div class="text-white text-sm font-semibold"><?= htmlspecialchars($r['label']) ?></div>
      <div class="text-slate-400 text-xs font-mono mt-1"><?= htmlspecialchars($r['detail']) ?></div>
    </div>
  </div>
<?php endforeach; ?>
</div>

<div class="bg-emerald-900/20 border border-emerald-700/40 rounded-2xl p-5 text-emerald-200 text-sm leading-relaxed">
  <strong class="text-emerald-400">Prossimi passi:</strong><br>
  1. Torna in <a href="diag_uploads.php" class="text-emerald-400 underline">Diagnostica</a> &rarr; verifica entrambe le dir ora esistono e sono scrivibili<br>
  2. Vai in Punti di Interesse &rarr; edita <strong>Piazzetta Nicola Vella</strong> &rarr; ricarica cover + audio IT &rarr; Salva<br>
  3. Se upload fallisce ancora, controlla log: <code class="text-xs">/api/uploads/upload_debug.log</code>
</div>

</div>
</div>
<?php require '_footer.php'; ?>
