<?php
require_once __DIR__ . '/../config/db.php';
requireAdminSession();
$db = getDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = trim($_POST['id'] ?? '');
    if (!$id) { $msg = '❌ ID obbligatorio.'; goto render; }

    $exists = $db->prepare("SELECT id FROM accommodations WHERE id=?");
    $exists->execute([$id]);

    $coverPath = handleCoverUpload('cover_image', 'accommodation', $id);
    if ($coverPath) ensureCoverImageColumn($db, 'accommodations');

    $f = [
        'slug'                 => trim($_POST['slug']                 ?? $id),
        'name'                 => trim($_POST['name']                 ?? ''),
        'type'                 => $_POST['type']                      ?? 'AGRITURISMO',
        'provider_id'          => trim($_POST['provider_id']          ?? ''),
        'borough_id'           => trim($_POST['borough_id']           ?? ''),
        'address_full'         => trim($_POST['address_full']         ?? ''),
        'lat'                  => (float)($_POST['lat']               ?? 0),
        'lng'                  => (float)($_POST['lng']               ?? 0),
        'distance_center_km'   => (float)($_POST['distance_center_km'] ?? 0),
        'description_short'    => trim($_POST['description_short']    ?? ''),
        'description_long'     => trim($_POST['description_long']     ?? ''),
        'tagline'              => trim($_POST['tagline']              ?? ''),
        'rooms_count'          => (int)($_POST['rooms_count']         ?? 0),
        'max_guests'           => (int)($_POST['max_guests']          ?? 0),
        'price_per_night_from' => (float)($_POST['price_per_night_from'] ?? 0),
        'stars_or_category'    => trim($_POST['stars_or_category']    ?? ''),
        'check_in_time'        => trim($_POST['check_in_time']        ?? ''),
        'check_out_time'       => trim($_POST['check_out_time']       ?? ''),
        'min_stay_nights'      => (int)($_POST['min_stay_nights']     ?? 1),
        'amenities'            => trim($_POST['amenities']            ?? ''),
        'accessibility'        => trim($_POST['accessibility']        ?? ''),
        'languages_spoken'     => trim($_POST['languages_spoken']     ?? ''),
        'cancellation_policy'  => trim($_POST['cancellation_policy']  ?? ''),
        'booking_email'        => trim($_POST['booking_email']        ?? ''),
        'booking_phone'        => trim($_POST['booking_phone']        ?? ''),
        'booking_url'          => trim($_POST['booking_url']          ?? ''),
        'main_video_url'       => trim($_POST['main_video_url']       ?? ''),
        'virtual_tour_url'     => trim($_POST['virtual_tour_url']     ?? ''),
        'contact_email'        => trim($_POST['contact_email']        ?? ''),
        'contact_phone'        => trim($_POST['contact_phone']        ?? ''),
        'website_url'          => trim($_POST['website_url']          ?? ''),
        'social_instagram'     => trim($_POST['social_instagram']     ?? ''),
        'social_facebook'      => trim($_POST['social_facebook']      ?? ''),
        'social_linkedin'      => trim($_POST['social_linkedin']      ?? ''),
        'certifications'       => trim($_POST['certifications']       ?? ''),
        'founder_name'         => trim($_POST['founder_name']         ?? ''),
        'founder_quote'        => trim($_POST['founder_quote']        ?? ''),
        'tier'                 => $_POST['tier']                      ?? 'BASE',
        'is_verified'          => isset($_POST['is_verified'])        ? 1 : 0,
        'b2b_open_for_contact' => isset($_POST['b2b_open_for_contact']) ? 1 : 0,
        'b2b_interests'        => trim($_POST['b2b_interests']        ?? ''),
        'is_active'            => isset($_POST['is_active'])    ? 1 : 0,
        'is_featured'          => isset($_POST['is_featured'])  ? 1 : 0,
    ];
    if ($coverPath) $f['cover_image'] = $coverPath;

    if ($exists->fetch()) {
        $set = implode(',', array_map(fn($k) => "`$k`=?", array_keys($f)));
        $db->prepare("UPDATE accommodations SET $set WHERE id=?")->execute([...array_values($f), $id]);
    } else {
        $cols = implode(',', array_map(fn($k) => "`$k`", array_keys($f)));
        $phs  = implode(',', array_fill(0, count($f), '?'));
        $db->prepare("INSERT INTO accommodations (id,$cols) VALUES (?,$phs)")->execute([$id, ...array_values($f)]);
    }
    $msg = '✅ Struttura salvata.';
}
render:

if (isset($_GET['delete'])) {
    $db->prepare("DELETE FROM accommodations WHERE id=?")->execute([$_GET['delete']]);
    header('Location: ospitalita.php');
    exit;
}

try {
    $list = $db->query("SELECT id, name, borough_id, type FROM accommodations ORDER BY name ASC")->fetchAll();
} catch (PDOException $e) {
    $pageTitle = 'Ospitalità';
    require '_layout.php';
    echo '<div class="bg-red-900/40 border border-red-600 rounded-xl p-6 text-red-300">
        <p class="font-bold mb-2">❌ Tabella <code>accommodations</code> non trovata nel database.</p>
        <p class="text-sm mb-3">Esegui prima il seed per creare le tabelle e inserire i dati di esempio.</p>
        <a href="seed_lacedonia.php" class="inline-block px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm rounded-lg">🌱 Esegui Seed Lacedonia</a>
    </div>';
    require '_footer.php';
    exit;
}
$sel  = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM accommodations WHERE id=?");
    $stmt->execute([$_GET['edit']]);
    $sel = $stmt->fetch();
}

$pageTitle = 'Ospitalità';
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
      <h3 class="font-semibold text-sm">Ospitalità (<?= count($list) ?>)</h3>
      <a href="ospitalita.php?edit=__new__" class="text-xs bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1 rounded-lg">+ Nuova</a>
    </div>
    <div class="divide-y divide-slate-700 max-h-[65vh] overflow-y-auto">
      <?php foreach ($list as $item): ?>
      <a href="ospitalita.php?edit=<?= urlencode($item['id']) ?>"
         class="flex items-center justify-between px-4 py-3 hover:bg-slate-700 transition-colors <?= (isset($_GET['edit']) && $_GET['edit']===$item['id'] ? 'bg-slate-700' : '') ?>">
        <div>
          <div class="text-sm font-medium text-white"><?= htmlspecialchars($item['name']) ?></div>
          <div class="text-xs text-slate-400"><?= htmlspecialchars($item['borough_id']) ?> · <?= $item['type'] ?></div>
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
      <h3 class="font-semibold text-white mb-2"><?= $sel ? 'Modifica: ' . htmlspecialchars($sel['name']) : 'Nuova struttura' ?></h3>
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
        echo $inp('slug','Slug');
        echo $inp('name','Nome','text',true);
        echo $inp('provider_id','ID Fornitore (azienda)');
        echo $inp('borough_id','ID Borgo');
        echo $inp('address_full','Indirizzo completo','text',true);
        echo $inp('lat','Latitudine','number');
        echo $inp('lng','Longitudine','number');
        echo $inp('distance_center_km','Distanza centro km','number');
        echo $inp('rooms_count','Camere','number');
        echo $inp('max_guests','Max ospiti','number');
        echo $inp('price_per_night_from','Prezzo da €/notte','number');
        echo $inp('stars_or_category','Stelle/Categoria');
        echo $inp('check_in_time','Check-in');
        echo $inp('check_out_time','Check-out');
        echo $inp('min_stay_nights','Soggiorno minimo notti','number');
        echo $inp('languages_spoken','Lingue parlate');
        echo $inp('contact_email','Email contatto','email');
        echo $inp('contact_phone','Telefono contatto');
        echo $inp('website_url','Sito web','url',true);
        echo $inp('social_instagram','Instagram');
        echo $inp('social_facebook','Facebook');
        echo $inp('social_linkedin','LinkedIn');
        echo $inp('founder_name','Fondatore');
        echo $inp('booking_email','Email prenotazioni','email');
        echo $inp('booking_phone','Telefono prenotazioni');
        echo $inp('booking_url','URL prenotazione','url',true);
        echo $inp('main_video_url','URL Video embed','text',true);
        echo $inp('virtual_tour_url','URL Tour Virtuale embed','text',true);
        ?>
        <div>
          <label class="block text-xs text-slate-400 mb-1">Tipo</label>
          <select name="type" class="w-full bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600 focus:outline-none focus:border-emerald-500">
            <?php foreach (['HOTEL','AGRITURISMO','MASSERIA','BED_AND_BREAKFAST','HOSTEL','APPARTAMENTO'] as $t): ?>
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
      <?php foreach ([
        ['tagline','Tagline'],
        ['description_short','Descrizione breve'],
        ['description_long','Descrizione completa'],
        ['amenities','Servizi (uno per riga)'],
        ['accessibility','Accessibilità'],
        ['cancellation_policy','Politica cancellazione'],
        ['founder_quote','Citazione fondatore'],
        ['certifications','Certificazioni (una per riga)'],
        ['b2b_interests','Interessi B2B (uno per riga)'],
      ] as [$n,$l]): ?>
      <div>
        <label class="block text-xs text-slate-400 mb-1"><?= $l ?></label>
        <textarea name="<?= $n ?>" rows="<?= in_array($n,['description_long','amenities'])?4:2 ?>"
          class="w-full bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600 focus:outline-none focus:border-emerald-500"><?= htmlspecialchars($sel[$n]??'') ?></textarea>
      </div>
      <?php endforeach; ?>
      <div class="flex gap-4 flex-wrap text-sm">
        <?php foreach ([['is_verified','Verificata'],['is_active','Attiva'],['is_featured','In evidenza'],['b2b_open_for_contact','Aperta B2B']] as [$n,$l]): ?>
        <label class="flex items-center gap-2 text-slate-300">
          <input type="checkbox" name="<?= $n ?>" <?= !empty($sel[$n])?'checked':'' ?> class="rounded">
          <?= $l ?>
        </label>
        <?php endforeach; ?>
      </div>
      <div class="flex gap-3 pt-2">
        <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg transition-colors">Salva</button>
        <?php if ($sel): ?>
        <a href="ospitalita.php?delete=<?= urlencode($sel['id']) ?>"
           onclick="return confirm('Eliminare questa struttura?')"
           class="px-6 py-2.5 bg-red-700 hover:bg-red-600 text-white text-sm rounded-lg transition-colors">Elimina</a>
        <?php endif; ?>
        <a href="ospitalita.php" class="px-6 py-2.5 bg-slate-600 hover:bg-slate-500 text-white text-sm rounded-lg transition-colors">Annulla</a>
      </div>
    </form>
    <?php else: ?>
    <div class="bg-slate-800 rounded-xl border border-slate-700 p-12 text-center">
      <div class="text-4xl mb-3">🏨</div>
      <p class="text-slate-400">Seleziona una struttura o creane una nuova.</p>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require '_footer.php'; ?>
