<?php
require_once __DIR__ . '/../config/db.php';
requireAdminSession();
$db = getDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = trim($_POST['id'] ?? '');
    if (!$id) { $msg = '❌ ID obbligatorio.'; goto render; }

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

    $toLines = fn($s) => array_filter(array_map('trim', explode("\n", $s ?? '')));
    // languages: spazio-separated
    $langs = array_filter(array_map('trim', explode(',', $_POST['languages_available'] ?? '')));
    $db->prepare("DELETE FROM experience_languages WHERE experience_id=?")->execute([$id]);
    $stmt = $db->prepare("INSERT INTO experience_languages (experience_id, lang) VALUES (?,?)");
    foreach ($langs as $l) $stmt->execute([$id, $l]);

    replaceArray($db, 'experience_includes',      'experience_id', $id, $toLines($_POST['includes']      ?? ''));
    replaceArray($db, 'experience_excludes',      'experience_id', $id, $toLines($_POST['excludes']      ?? ''));
    replaceArray($db, 'experience_bring',         'experience_id', $id, $toLines($_POST['what_to_bring'] ?? ''));
    replaceArray($db, 'experience_seasonal_tags', 'experience_id', $id, $toLines($_POST['seasonal_tags'] ?? ''));

    $msg = '✅ Esperienza salvata.';
}
render:

if (isset($_GET['delete'])) {
    $did = $_GET['delete'];
    foreach (['experience_languages','experience_includes','experience_excludes',
              'experience_bring','experience_seasonal_tags','experience_timeline'] as $t) {
        $db->prepare("DELETE FROM `$t` WHERE experience_id = ?")->execute([$did]);
    }
    $db->prepare("DELETE FROM experiences WHERE id=?")->execute([$did]);
    header('Location: esperienze.php');
    exit;
}

$list = $db->query("SELECT id, title, category, borough_id FROM experiences ORDER BY title ASC")->fetchAll();
$sel = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM experiences WHERE id=?");
    $stmt->execute([$_GET['edit']]);
    $sel = $stmt->fetch();
    if ($sel) {
        $langs = fetchArray($db, 'experience_languages', 'experience_id', $sel['id'], 'lang');
        $sel['languages_available'] = implode(', ', $langs);
        $sel['includes']      = implode("\n", fetchArray($db, 'experience_includes',      'experience_id', $sel['id']));
        $sel['excludes']      = implode("\n", fetchArray($db, 'experience_excludes',      'experience_id', $sel['id']));
        $sel['what_to_bring'] = implode("\n", fetchArray($db, 'experience_bring',         'experience_id', $sel['id']));
        $sel['seasonal_tags'] = implode("\n", fetchArray($db, 'experience_seasonal_tags', 'experience_id', $sel['id']));
    }
}

$pageTitle = 'Esperienze';
require '_layout.php';
?>

<?php if ($msg): ?>
  <div class="mb-4 px-4 py-3 rounded-lg text-sm <?= str_starts_with($msg,'✅') ? 'bg-emerald-900/40 border border-emerald-600 text-emerald-300' : 'bg-red-900/40 border border-red-600 text-red-300' ?>">
    <?= htmlspecialchars($msg) ?>
  </div>
<?php endif; ?>

<div class="grid md:grid-cols-3 gap-6">
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

  <div class="md:col-span-2">
    <?php if ($sel !== null || isset($_GET['edit'])): ?>
    <form method="POST" enctype="multipart/form-data" class="bg-slate-800 rounded-xl border border-slate-700 p-6 space-y-4">
      <h3 class="font-semibold text-white mb-2"><?= $sel ? htmlspecialchars($sel['title']) : 'Nuova esperienza' ?></h3>
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
        echo $inp('title','Titolo');
        echo $inp('provider_id','ID Azienda');
        echo $inp('borough_id','ID Borgo');
        echo $inp('lat','Latitudine','number');
        echo $inp('lng','Longitudine','number');
        echo $inp('duration_minutes','Durata (min)','number');
        echo $inp('max_participants','Max partecipanti','number');
        echo $inp('min_participants','Min partecipanti','number');
        echo $inp('price_per_person','Prezzo/persona €','number');
        echo $inp('rating','Rating (0-5)','number');
        echo $inp('reviews_count','N. recensioni','number');
        echo $inp('languages_available','Lingue (es: Italiano, English)','text',true);
        echo $inp('tagline','Tagline','text',true);
        ?>
        <div>
          <label class="block text-xs text-slate-400 mb-1">Categoria</label>
          <select name="category" class="w-full bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600">
            <?php foreach (['GASTRONOMIA','CULTURA','NATURA','ARTIGIANATO','BENESSERE','AVVENTURA'] as $c): ?>
            <option value="<?= $c ?>" <?= ($sel['category']??'')===$c?'selected':'' ?>><?= $c ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-xs text-slate-400 mb-1">Difficoltà</label>
          <select name="difficulty_level" class="w-full bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600">
            <?php foreach (['FACILE','MEDIO','DIFFICILE'] as $d): ?>
            <option value="<?= $d ?>" <?= ($sel['difficulty_level']??'')===$d?'selected':'' ?>><?= $d ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div>
        <label class="block text-xs text-slate-400 mb-1">Descrizione breve</label>
        <textarea name="description_short" rows="2" class="w-full bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600"><?= htmlspecialchars($sel['description_short']??'') ?></textarea>
      </div>
      <div>
        <label class="block text-xs text-slate-400 mb-1">Descrizione completa</label>
        <textarea name="description_long" rows="3" class="w-full bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600"><?= htmlspecialchars($sel['description_long']??'') ?></textarea>
      </div>
      <?php foreach ([['includes','Include (uno per riga)'],['excludes','Non include (uno per riga)'],['what_to_bring','Cosa portare (uno per riga)'],['seasonal_tags','Tag stagionali (uno per riga)']] as [$n,$l]): ?>
      <div>
        <label class="block text-xs text-slate-400 mb-1"><?= $l ?></label>
        <textarea name="<?= $n ?>" rows="2" class="w-full bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600"><?= htmlspecialchars($sel[$n]??'') ?></textarea>
      </div>
      <?php endforeach; ?>
      <label class="flex items-center gap-2 text-slate-300 text-sm">
        <input type="checkbox" name="is_active" <?= !empty($sel['is_active'])?'checked':'' ?>> Attiva
      </label>
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
    <?php else: ?>
    <div class="bg-slate-800 rounded-xl border border-slate-700 p-12 text-center">
      <div class="text-4xl mb-3">🎭</div>
      <p class="text-slate-400">Seleziona un'esperienza o creane una nuova.</p>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require '_footer.php'; ?>
