<?php
// api/admin/diag_uploads.php
// Diagnostica uploads: esiste dir? scrivibile? quali file ci sono?
require_once __DIR__ . '/../config/db.php';
requireAdminSession();

$uploadsRoot  = __DIR__ . '/../uploads';
$uploadsAudio = __DIR__ . '/../uploads/audio';

function inspect(string $path): array {
    return [
        'path'       => $path,
        'exists'     => is_dir($path),
        'writable'   => is_dir($path) && is_writable($path),
        'perms'      => is_dir($path) ? substr(sprintf('%o', fileperms($path)), -4) : null,
        'file_count' => is_dir($path) ? count(array_diff(scandir($path) ?: [], ['.', '..'])) : 0,
    ];
}

function listFiles(string $path, int $limit = 40): array {
    if (!is_dir($path)) return [];
    $out = [];
    foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $f) {
        $full = $path . '/' . $f;
        if (is_file($full)) {
            $out[] = [
                'name' => $f,
                'size' => filesize($full),
                'mtime' => date('Y-m-d H:i:s', filemtime($full)),
            ];
        }
        if (count($out) >= $limit) break;
    }
    return $out;
}

$info = [
    'root'          => inspect($uploadsRoot),
    'audio'         => inspect($uploadsAudio),
    'php_config'    => [
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size'       => ini_get('post_max_size'),
        'max_file_uploads'    => ini_get('max_file_uploads'),
        'file_uploads'        => ini_get('file_uploads'),
        'upload_tmp_dir'      => ini_get('upload_tmp_dir') ?: sys_get_temp_dir(),
        'memory_limit'        => ini_get('memory_limit'),
    ],
    'files_root'    => listFiles($uploadsRoot),
    'files_audio'   => listFiles($uploadsAudio),
];

$debugLog = $uploadsRoot . '/upload_debug.log';
$debugLogContent = is_file($debugLog) ? file_get_contents($debugLog) : '';
$debugLogTail = $debugLogContent ? implode("\n", array_slice(explode("\n", trim($debugLogContent)), -40)) : '';

$pageTitle = 'Diagnostica Uploads';
require '_layout.php';
?>
<div class="flex-1 overflow-auto p-6">
<div class="max-w-4xl mx-auto space-y-6">

<h2 class="text-2xl font-bold text-white mb-4">&#128269; Diagnostica Uploads</h2>

<div class="bg-slate-800 rounded-2xl border border-slate-700 p-5">
  <h3 class="text-white font-semibold mb-3">Stato directory</h3>
  <table class="w-full text-sm">
    <thead class="text-slate-400 border-b border-slate-700">
      <tr><th class="text-left py-2">Dir</th><th class="text-left">Esiste</th><th class="text-left">Scrivibile</th><th class="text-left">Permessi</th><th class="text-left">File</th></tr>
    </thead>
    <tbody>
      <tr class="border-b border-slate-700/50">
        <td class="py-2 font-mono text-xs text-slate-300"><?= htmlspecialchars($info['root']['path']) ?></td>
        <td><?= $info['root']['exists'] ? '<span class="text-emerald-400">&#10003; Si</span>' : '<span class="text-red-400">&#10007; No</span>' ?></td>
        <td><?= $info['root']['writable'] ? '<span class="text-emerald-400">&#10003; Si</span>' : '<span class="text-red-400">&#10007; No</span>' ?></td>
        <td class="font-mono text-xs text-amber-400"><?= htmlspecialchars($info['root']['perms'] ?? 'n/a') ?></td>
        <td class="text-slate-300"><?= $info['root']['file_count'] ?></td>
      </tr>
      <tr>
        <td class="py-2 font-mono text-xs text-slate-300"><?= htmlspecialchars($info['audio']['path']) ?></td>
        <td><?= $info['audio']['exists'] ? '<span class="text-emerald-400">&#10003; Si</span>' : '<span class="text-red-400">&#10007; No</span>' ?></td>
        <td><?= $info['audio']['writable'] ? '<span class="text-emerald-400">&#10003; Si</span>' : '<span class="text-red-400">&#10007; No</span>' ?></td>
        <td class="font-mono text-xs text-amber-400"><?= htmlspecialchars($info['audio']['perms'] ?? 'n/a') ?></td>
        <td class="text-slate-300"><?= $info['audio']['file_count'] ?></td>
      </tr>
    </tbody>
  </table>
</div>

<div class="bg-slate-800 rounded-2xl border border-slate-700 p-5">
  <h3 class="text-white font-semibold mb-3">Limiti PHP</h3>
  <table class="w-full text-sm">
    <tbody>
      <?php foreach ($info['php_config'] as $k => $v): ?>
      <tr class="border-b border-slate-700/50">
        <td class="py-2 text-slate-400 font-mono text-xs"><?= htmlspecialchars($k) ?></td>
        <td class="text-white font-mono text-xs"><?= htmlspecialchars((string)$v) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="bg-slate-800 rounded-2xl border border-slate-700 p-5">
  <h3 class="text-white font-semibold mb-3">File in <code class="text-emerald-400">/api/uploads/</code> (max 40)</h3>
  <?php if (empty($info['files_root'])): ?>
    <p class="text-red-400 text-sm">Nessun file trovato.</p>
  <?php else: ?>
    <table class="w-full text-sm">
      <thead class="text-slate-400 border-b border-slate-700"><tr><th class="text-left py-2">Nome</th><th class="text-right">Size</th><th class="text-left pl-4">Modificato</th></tr></thead>
      <tbody>
        <?php foreach ($info['files_root'] as $f): ?>
        <tr class="border-b border-slate-700/50">
          <td class="py-2 font-mono text-xs text-slate-300"><?= htmlspecialchars($f['name']) ?></td>
          <td class="text-right text-slate-400 text-xs"><?= number_format($f['size']) ?> B</td>
          <td class="pl-4 text-slate-500 text-xs"><?= htmlspecialchars($f['mtime']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<div class="bg-slate-800 rounded-2xl border border-slate-700 p-5">
  <h3 class="text-white font-semibold mb-3">File in <code class="text-emerald-400">/api/uploads/audio/</code> (max 40)</h3>
  <?php if (empty($info['files_audio'])): ?>
    <p class="text-red-400 text-sm">Nessun file trovato.</p>
  <?php else: ?>
    <table class="w-full text-sm">
      <thead class="text-slate-400 border-b border-slate-700"><tr><th class="text-left py-2">Nome</th><th class="text-right">Size</th><th class="text-left pl-4">Modificato</th></tr></thead>
      <tbody>
        <?php foreach ($info['files_audio'] as $f): ?>
        <tr class="border-b border-slate-700/50">
          <td class="py-2 font-mono text-xs text-slate-300"><?= htmlspecialchars($f['name']) ?></td>
          <td class="text-right text-slate-400 text-xs"><?= number_format($f['size']) ?> B</td>
          <td class="pl-4 text-slate-500 text-xs"><?= htmlspecialchars($f['mtime']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<div class="bg-slate-800 rounded-2xl border border-slate-700 p-5">
  <h3 class="text-white font-semibold mb-3">Log debug upload (ultimi 40 eventi)</h3>
  <?php if (!$debugLogTail): ?>
    <p class="text-slate-500 text-sm">Nessun log ancora. Prova a salvare un POI con upload e ricarica.</p>
  <?php else: ?>
    <pre class="text-xs text-slate-300 bg-slate-900/60 rounded-lg p-3 overflow-x-auto whitespace-pre-wrap"><?= htmlspecialchars($debugLogTail) ?></pre>
  <?php endif; ?>
</div>

<div class="bg-amber-900/20 border border-amber-700/40 rounded-2xl p-5 text-amber-200 text-sm leading-relaxed">
  <strong class="text-amber-400">Cosa cercare:</strong><br>
  &bull; Dir <code>uploads/</code> e <code>uploads/audio/</code> devono esistere ed essere scrivibili (permessi 0755 o 0775)<br>
  &bull; <code>upload_max_filesize</code> &ge; 20M, <code>post_max_size</code> &ge; 25M per audio mp3<br>
  &bull; Se il DB POI ha URL <code>/api/uploads/audio/xxx.mp3</code> ma il file non c&rsquo;&egrave; in tabella sopra &rarr; upload fallito silenziosamente
</div>

</div>
</div>
<?php require '_footer.php'; ?>
