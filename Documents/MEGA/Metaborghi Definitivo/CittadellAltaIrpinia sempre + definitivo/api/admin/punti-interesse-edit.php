<?php
// api/admin/punti-interesse-edit.php
require_once __DIR__ . '/../config/db.php';
requireAdminSession();
$db  = getDB();
$msg = '';
$poi = null;

$borghi = $db->query("SELECT id, name_it FROM boroughs ORDER BY name_it")->fetchAll();

// ── LOAD per edit ────────────────────────────────────────────
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM points_of_interest WHERE id=?");
    $stmt->execute([$_GET['edit']]);
    $poi = $stmt->fetch() ?: null;
    if (!$poi) {
        header('Location: punti-interesse.php');
        exit;
    }
}

// ── Upload audio locale ───────────────────────────────────────
function uploadAudio(string $inputName, string $poiId, string $lang): ?string {
    if (empty($_FILES[$inputName]['tmp_name']) || $_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $allowed = ['mp3', 'm4a', 'ogg', 'wav'];
    $ext     = strtolower(pathinfo($_FILES[$inputName]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) {
        return null;
    }
    $destDir = __DIR__ . '/../uploads/audio/';
    if (!is_dir($destDir)) {
        if (!mkdir($destDir, 0755, true) && !is_dir($destDir)) {
            error_log("uploadAudio: impossibile creare $destDir");
            return null;
        }
    }
    $safeId   = preg_replace('/[^a-z0-9_-]/', '', strtolower($poiId));
    $filename = 'poi_' . $safeId . '_' . $lang . '_' . time() . '.' . $ext;
    $dest     = $destDir . $filename;
    return move_uploaded_file($_FILES[$inputName]['tmp_name'], $dest)
        ? '/api/uploads/audio/' . $filename
        : null;
}

// ── POST — Salvataggio ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = trim($_POST['id'] ?? '');
    if (!$id) {
        $msg = '❌ ID (slug) obbligatorio.';
        goto render;
    }

    $boroughId = trim($_POST['borough_id'] ?? '');
    if (!$boroughId) {
        $msg = '❌ Borough obbligatorio.';
        goto render;
    }

    $nameIt = trim($_POST['name_it'] ?? '');
    if (!$nameIt) {
        $msg = '❌ Nome IT obbligatorio.';
        goto render;
    }

    // Cover image upload
    $coverPath = handleCoverUpload('cover_image', 'poi', $id);

    // Audio upload (file ha priorità su URL CDN)
    $audioIt  = uploadAudio('audio_it_file',  $id, 'it')
             ?? (trim($_POST['audio_it_url']  ?? '') ?: null);
    $audioEn  = uploadAudio('audio_en_file',  $id, 'en')
             ?? (trim($_POST['audio_en_url']  ?? '') ?: null);
    $audioIrp = uploadAudio('audio_irp_file', $id, 'irp')
             ?? (trim($_POST['audio_irp_url'] ?? '') ?: null);

    $f = [
        'borough_id'     => $boroughId,
        'category'       => trim($_POST['category']       ?? '') ?: null,
        'sort_order'     => (int)($_POST['sort_order']    ?? 0),
        'name_it'        => $nameIt,
        'name_en'        => trim($_POST['name_en']        ?? '') ?: null,
        'name_irp'       => trim($_POST['name_irp']       ?? '') ?: null,
        'desc_it'        => trim($_POST['desc_it']        ?? '') ?: null,
        'desc_en'        => trim($_POST['desc_en']        ?? '') ?: null,
        'desc_irp'       => trim($_POST['desc_irp']       ?? '') ?: null,
        'tags'           => trim($_POST['tags']            ?? '') ?: null,
        'audio_it'       => $audioIt,
        'audio_en'       => $audioEn,
        'audio_irp'      => $audioIrp,
        'transcript_it'  => trim($_POST['transcript_it']  ?? '') ?: null,
        'transcript_en'  => trim($_POST['transcript_en']  ?? '') ?: null,
        'transcript_irp' => trim($_POST['transcript_irp'] ?? '') ?: null,
        'video_it'       => trim($_POST['video_it']       ?? '') ?: null,
        'video_en'       => trim($_POST['video_en']       ?? '') ?: null,
    ];
    if ($coverPath) {
        $f['cover_image'] = $coverPath;
    }

    // Carica il record esistente per preservare valori media non toccati
    $existsStmt = $db->prepare("SELECT * FROM points_of_interest WHERE id=?");
    $existsStmt->execute([$id]);
    $existing = $existsStmt->fetch() ?: null;

    // Mantieni cover/audio esistenti se non è stato caricato nulla di nuovo
    $preserveFields = ['cover_image', 'audio_it', 'audio_en', 'audio_irp'];
    foreach ($preserveFields as $field) {
        if (($f[$field] ?? null) === null && $existing && !empty($existing[$field])) {
            $f[$field] = $existing[$field];
        }
    }

    if ($existing) {
        $set = implode(',', array_map(fn($k) => "`$k`=?", array_keys($f)));
        $db->prepare("UPDATE points_of_interest SET $set WHERE id=?")
           ->execute([...array_values($f), $id]);
    } else {
        $cols = implode(',', array_map(fn($k) => "`$k`", array_keys($f)));
        $phs  = implode(',', array_fill(0, count($f), '?'));
        $db->prepare("INSERT INTO points_of_interest (id,$cols) VALUES (?,$phs)")
           ->execute([$id, ...array_values($f)]);
    }

    // Galleria immagini
    processGalleryFromPost($db, 'poi', $id, 'new_images');

    // Auto-export HTML statico (se il generatore esiste già)
    if (file_exists(__DIR__ . '/../export/generate_poi.php')) {
        require_once __DIR__ . '/../export/generate_poi.php';
        exportPoiHtml($db, $id);
        $msg = '✅ POI salvato e pagina generata.';
    } else {
        $msg = '✅ POI salvato. (Export HTML non ancora disponibile — genera_poi.php mancante)';
    }

    // Ricarica dati aggiornati per il form
    $stmt = $db->prepare("SELECT * FROM points_of_interest WHERE id=?");
    $stmt->execute([$id]);
    $poi = $stmt->fetch() ?: null;
}
render:

// Carica immagini galleria per il form
$images = [];
if ($poi) {
    $imgStmt = $db->prepare(
        "SELECT src, alt FROM entity_images WHERE entity_type='poi' AND entity_id=? ORDER BY sort_order"
    );
    $imgStmt->execute([$poi['id']]);
    $images = $imgStmt->fetchAll();
}

$pageTitle = $poi ? 'Modifica POI — ' . htmlspecialchars($poi['name_it']) : 'Nuovo POI';
require '_layout.php';
?>

<div class="flex-1 overflow-auto p-6">
<div class="max-w-3xl mx-auto">

  <!-- Header breadcrumb -->
  <div class="flex items-center gap-3 mb-6">
    <a href="punti-interesse.php" class="text-slate-400 hover:text-white text-sm transition-colors">← Punti di Interesse</a>
    <span class="text-slate-600">/</span>
    <h2 class="text-xl font-bold text-white">
      <?= $poi ? htmlspecialchars($poi['name_it']) : 'Nuovo POI' ?>
    </h2>
    <?php if ($poi): ?>
    <span class="text-xs bg-emerald-900/40 text-emerald-400 border border-emerald-700 px-2 py-1 rounded-lg font-mono">
      <?= htmlspecialchars($poi['id']) ?>
    </span>
    <?php endif; ?>
  </div>

  <?php if ($msg): ?>
  <?= adminMsg($msg) ?>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" class="flex flex-col gap-5">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

    <!-- ── IDENTITÀ ─────────────────────────────────────── -->
    <div class="bg-slate-800 rounded-2xl border border-slate-700 overflow-hidden">
      <div class="px-5 py-3 border-b border-slate-700 flex items-center gap-2">
        <span>&#127991;</span>
        <span class="font-semibold text-white text-sm">Identit&agrave;</span>
      </div>
      <div class="p-5 grid grid-cols-2 gap-4">

        <!-- ID / Slug -->
        <div class="flex flex-col gap-1">
          <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">
            ID / Slug <span class="text-red-400">*</span>
          </label>
          <input name="id" type="text" required
                 value="<?= htmlspecialchars($poi['id'] ?? '') ?>"
                 <?= $poi ? 'readonly' : '' ?>
                 placeholder="porta-di-sopra"
                 class="<?= $poi ? 'bg-slate-900/60 cursor-not-allowed' : 'bg-slate-900' ?> border border-slate-600 text-white rounded-lg px-3 py-2 text-sm font-mono w-full focus:outline-none focus:border-emerald-500"/>
          <span class="text-xs text-slate-500">kebab-case &mdash; non modificabile dopo creazione</span>
        </div>

        <!-- Borough -->
        <div class="flex flex-col gap-1">
          <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">
            Borough <span class="text-red-400">*</span>
          </label>
          <select name="borough_id"
                  class="bg-slate-900 border border-slate-600 text-white rounded-lg px-3 py-2 text-sm w-full focus:outline-none focus:border-emerald-500">
            <?php foreach ($borghi as $b): ?>
            <option value="<?= htmlspecialchars($b['id']) ?>"
                    <?= ($poi['borough_id'] ?? '') === $b['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($b['name_it']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Categoria -->
        <div class="flex flex-col gap-1">
          <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Categoria</label>
          <input name="category" type="text"
                 value="<?= htmlspecialchars($poi['category'] ?? '') ?>"
                 placeholder="Architettura"
                 class="bg-slate-900 border border-slate-600 text-white rounded-lg px-3 py-2 text-sm w-full focus:outline-none focus:border-emerald-500"/>
        </div>

        <!-- Ordine -->
        <div class="flex flex-col gap-1">
          <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Ordine</label>
          <input name="sort_order" type="number"
                 value="<?= (int)($poi['sort_order'] ?? 0) ?>"
                 class="bg-slate-900 border border-slate-600 text-white rounded-lg px-3 py-2 text-sm w-full focus:outline-none focus:border-emerald-500"/>
        </div>

        <!-- Tags CSV -->
        <div class="flex flex-col gap-1 col-span-2">
          <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Tag (CSV)</label>
          <input name="tags" type="text"
                 value="<?= htmlspecialchars($poi['tags'] ?? '') ?>"
                 placeholder="Medioevo,Normanni,Centro storico"
                 class="bg-slate-900 border border-slate-600 text-white rounded-lg px-3 py-2 text-sm w-full focus:outline-none focus:border-emerald-500"/>
        </div>

      </div>
    </div>

    <!-- ── TESTO MULTILINGUE ─────────────────────────────── -->
    <div class="bg-slate-800 rounded-2xl border border-slate-700 overflow-hidden">
      <div class="px-5 py-3 border-b border-slate-700 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <span>&#128214;</span>
          <span class="font-semibold text-white text-sm">Testi</span>
        </div>
        <!-- Switch lingua -->
        <div class="flex gap-1" id="lang-tabs">
          <button type="button" onclick="switchLang('it')" id="lt-it"
                  class="lang-tab text-xs font-semibold px-3 py-1 rounded-full bg-emerald-600 text-white transition-colors">
            IT
          </button>
          <button type="button" onclick="switchLang('en')" id="lt-en"
                  class="lang-tab text-xs font-semibold px-3 py-1 rounded-full bg-slate-700 text-slate-300 transition-colors">
            EN
          </button>
          <button type="button" onclick="switchLang('irp')" id="lt-irp"
                  class="lang-tab text-xs font-semibold px-3 py-1 rounded-full bg-slate-700 text-slate-300 transition-colors">
            IRP
          </button>
        </div>
      </div>
      <div class="p-5 flex flex-col gap-4">

        <?php
        $langLabels = ['it' => 'Italiano', 'en' => 'English', 'irp' => 'Cerugnes (dialetto)'];
        foreach ($langLabels as $lang => $langLabel):
        ?>
        <div class="lang-panel <?= $lang !== 'it' ? 'hidden' : '' ?>" id="panel-<?= $lang ?>">
          <div class="flex flex-col gap-3">
            <div class="flex flex-col gap-1">
              <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">
                Nome &mdash; <?= htmlspecialchars($langLabel) ?>
                <?= $lang === 'it' ? '<span class="text-red-400">*</span>' : '' ?>
              </label>
              <input name="name_<?= $lang ?>" type="text"
                     value="<?= htmlspecialchars($poi['name_' . $lang] ?? '') ?>"
                     placeholder="Nome POI in <?= strtoupper($lang) ?>"
                     <?= $lang === 'it' ? 'required' : '' ?>
                     class="bg-slate-900 border border-slate-600 text-white rounded-lg px-3 py-2 text-sm w-full focus:outline-none focus:border-emerald-500"/>
            </div>
            <div class="flex flex-col gap-1">
              <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">
                Descrizione &mdash; <?= htmlspecialchars($langLabel) ?>
              </label>
              <textarea name="desc_<?= $lang ?>" rows="5"
                        placeholder="Descrizione in <?= strtoupper($lang) ?>..."
                        class="bg-slate-900 border border-slate-600 text-white rounded-lg px-3 py-2 text-sm w-full resize-y focus:outline-none focus:border-emerald-500"><?= htmlspecialchars($poi['desc_' . $lang] ?? '') ?></textarea>
            </div>
          </div>
        </div>
        <?php endforeach; ?>

      </div>
    </div>

    <!-- ── IMMAGINI ──────────────────────────────────────── -->
    <div class="bg-slate-800 rounded-2xl border border-slate-700 overflow-hidden">
      <div class="px-5 py-3 border-b border-slate-700 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <span>&#128444;</span>
          <span class="font-semibold text-white text-sm">Immagini</span>
        </div>
        <span class="text-xs text-slate-500">Prima immagine galleria = hero &middot; Max 10</span>
      </div>
      <div class="p-5 flex flex-col gap-5">

        <!-- Cover singola -->
        <div class="flex flex-col gap-2">
          <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">
            Cover / Hero
          </label>
          <?php if (!empty($poi['cover_image'])): ?>
          <div class="flex items-center gap-3">
            <div class="w-20 h-14 rounded-lg bg-slate-700 overflow-hidden shrink-0">
              <img src="<?= htmlspecialchars($poi['cover_image']) ?>"
                   class="w-full h-full object-cover" alt="cover"/>
            </div>
            <span class="text-xs text-slate-400 font-mono break-all">
              <?= htmlspecialchars($poi['cover_image']) ?>
            </span>
          </div>
          <?php endif; ?>
          <input name="cover_image" type="file" accept="image/jpeg,image/png,image/webp"
                 class="text-sm text-slate-300 file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0
                        file:bg-emerald-700 file:text-white file:text-xs file:font-semibold
                        hover:file:bg-emerald-600 cursor-pointer"/>
          <p class="text-xs text-slate-500">Ottimale 800&times;600px (4:3) &middot; max 5 MB &middot; JPG, PNG, WebP</p>
        </div>

        <!-- Galleria multipla -->
        <div class="flex flex-col gap-2">
          <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">
            Galleria aggiuntiva
            <?php if ($images): ?>
            <span class="text-slate-500 font-normal normal-case">(<?= count($images) ?> immagini)</span>
            <?php endif; ?>
          </label>

          <?php if ($images): ?>
          <div class="grid grid-cols-5 gap-2">
            <?php foreach ($images as $i => $img): ?>
            <div class="relative group">
              <div class="w-full h-16 rounded-lg bg-slate-700 overflow-hidden">
                <img src="<?= htmlspecialchars($img['src'] ?? '') ?>"
                     class="w-full h-full object-cover"
                     alt="<?= htmlspecialchars($img['alt'] ?? '') ?>"/>
              </div>
              <input type="hidden" name="existing_images_src[]" value="<?= htmlspecialchars($img['src'] ?? '') ?>">
              <input type="text"  name="existing_images_alt[]" value="<?= htmlspecialchars($img['alt'] ?? '') ?>"
                     placeholder="Alt text"
                     class="w-full mt-1 bg-slate-600 text-white rounded px-2 py-1 text-xs border border-slate-500 focus:outline-none">
              <label class="absolute top-1 right-1 bg-red-600 text-white rounded-full w-5 h-5 flex items-center
                            justify-center text-xs cursor-pointer opacity-0 group-hover:opacity-100 transition-opacity"
                     title="Rimuovi">
                <input type="checkbox" name="remove_images[]" value="<?= $i ?>" class="hidden"> &times;
              </label>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <input name="new_images[]" type="file" accept="image/jpeg,image/png,image/webp" multiple
                 class="text-sm text-slate-300 file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0
                        file:bg-slate-700 file:text-white file:text-xs file:font-semibold
                        hover:file:bg-slate-600 cursor-pointer"/>
          <p class="text-xs text-slate-500">Ottimale 1200&times;900px (4:3) &middot; max 8 MB/file &middot; JPG, PNG, WebP</p>
        </div>

      </div>
    </div>

    <!-- ── AUDIO ─────────────────────────────────────────── -->
    <div class="bg-slate-800 rounded-2xl border border-slate-700 overflow-hidden">
      <div class="px-5 py-3 border-b border-slate-700 flex items-center gap-2">
        <span>&#127911;</span>
        <span class="font-semibold text-white text-sm">Audio narrazione</span>
      </div>
      <div class="p-5 flex flex-col gap-6">

        <?php
        $audioLangs = [
            'it'  => 'Italiano',
            'en'  => 'English',
            'irp' => 'Cerugn&eacute;s (dialetto)',
        ];
        foreach ($audioLangs as $lang => $langLabel):
            $existingAudio = $poi['audio_' . $lang] ?? '';
        ?>
        <div class="flex flex-col gap-2">
          <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">
            <?= $langLabel ?>
          </label>

          <?php if ($existingAudio): ?>
          <!-- Player audio esistente -->
          <div class="flex items-center gap-3 bg-slate-900 rounded-lg px-3 py-2">
            <span class="text-xs text-emerald-400 font-mono break-all flex-1">
              <?= htmlspecialchars($existingAudio) ?>
            </span>
            <audio controls preload="none"
                   class="h-8 shrink-0"
                   style="max-width:180px">
              <source src="<?= htmlspecialchars($existingAudio) ?>"/>
            </audio>
          </div>
          <?php endif; ?>

          <!-- Upload file o URL CDN -->
          <div class="flex gap-2 flex-wrap">
            <input name="audio_<?= $lang ?>_file" type="file"
                   accept="audio/mpeg,audio/mp4,audio/ogg,audio/wav"
                   class="flex-1 min-w-0 text-sm text-slate-300 file:mr-2 file:py-1 file:px-3 file:rounded-lg
                          file:border-0 file:bg-slate-700 file:text-white file:text-xs file:font-semibold
                          hover:file:bg-slate-600 cursor-pointer"/>
            <input name="audio_<?= $lang ?>_url" type="text"
                   value="<?= htmlspecialchars($existingAudio) ?>"
                   placeholder="oppure URL CDN (https://…)"
                   class="flex-1 min-w-0 bg-slate-900 border border-slate-600 text-white rounded-lg px-3 py-2
                          text-xs font-mono focus:outline-none focus:border-emerald-500"/>
          </div>
          <p class="text-xs text-slate-500">
            Upload: mp3, m4a, ogg, wav &middot; Il file caricato ha priorit&agrave; sull&rsquo;URL CDN
          </p>

          <!-- Trascrizione -->
          <div class="flex flex-col gap-1">
            <label class="text-xs text-slate-500">Trascrizione <?= $langLabel ?></label>
            <textarea name="transcript_<?= $lang ?>" rows="2"
                      placeholder="Testo della narrazione audio..."
                      class="bg-slate-900 border border-slate-600 text-white rounded-lg px-3 py-2 text-xs
                             resize-y w-full focus:outline-none focus:border-emerald-500"><?= htmlspecialchars($poi['transcript_' . $lang] ?? '') ?></textarea>
          </div>
        </div>
        <?php endforeach; ?>

      </div>
    </div>

    <!-- ── VIDEO ─────────────────────────────────────────── -->
    <div class="bg-slate-800 rounded-2xl border border-slate-700 overflow-hidden">
      <div class="px-5 py-3 border-b border-slate-700 flex items-center gap-2">
        <span>&#127916;</span>
        <span class="font-semibold text-white text-sm">Video</span>
      </div>
      <div class="p-5 flex flex-col gap-4">

        <?php
        $videoLangs = ['it' => 'Italiano', 'en' => 'English'];
        foreach ($videoLangs as $lang => $langLabel):
        ?>
        <div class="flex flex-col gap-1">
          <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">
            URL Video &mdash; <?= $langLabel ?>
          </label>
          <input name="video_<?= $lang ?>" type="text"
                 value="<?= htmlspecialchars($poi['video_' . $lang] ?? '') ?>"
                 placeholder="https://metaborghi.org/media/video/..."
                 class="bg-slate-900 border border-slate-600 text-white rounded-lg px-3 py-2 text-sm
                        font-mono w-full focus:outline-none focus:border-emerald-500"/>
          <span class="text-xs text-slate-500">
            Lascia vuoto &rarr; il tab Video non appare nella scheda pubblica
          </span>
        </div>
        <?php endforeach; ?>

      </div>
    </div>

    <!-- ── AZIONI ─────────────────────────────────────────── -->
    <div class="flex gap-3 justify-end pb-4">
      <a href="punti-interesse.php"
         class="px-4 py-2 text-sm text-slate-400 bg-slate-800 border border-slate-600 rounded-xl
                hover:bg-slate-700 transition-colors">
        Annulla
      </a>
      <?php if ($poi): ?>
      <a href="../../borghi/<?= urlencode($poi['borough_id']) ?>/<?= urlencode($poi['id']) ?>/?preview=1"
         target="_blank"
         class="px-4 py-2 text-sm text-cyan-400 bg-slate-800 border border-cyan-700 rounded-xl
                hover:bg-slate-700 transition-colors">
        Anteprima iframe
      </a>
      <?php endif; ?>
      <button type="submit"
              class="px-5 py-2 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-500
                     rounded-xl transition-colors">
        Salva &amp; Pubblica
      </button>
    </div>

  </form>
</div>
</div>

<script>
function switchLang(lang) {
  document.querySelectorAll('.lang-panel').forEach(function(p) {
    p.classList.add('hidden');
  });
  document.querySelectorAll('.lang-tab').forEach(function(t) {
    t.classList.remove('bg-emerald-600', 'text-white');
    t.classList.add('bg-slate-700', 'text-slate-300');
  });
  var panel = document.getElementById('panel-' + lang);
  if (panel) panel.classList.remove('hidden');
  var btn = document.getElementById('lt-' + lang);
  if (btn) {
    btn.classList.add('bg-emerald-600', 'text-white');
    btn.classList.remove('bg-slate-700', 'text-slate-300');
  }
}
</script>

<?php require '_footer.php'; ?>
