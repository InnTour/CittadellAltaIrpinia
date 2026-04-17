<?php
// api/admin/seed_poi_lacedonia.php
require_once __DIR__ . '/../config/db.php';
requireAdminSession();
$db = getDB();
$results = []; $errors = [];

function seedPoi(PDO $db, array $data, array &$results, array &$errors): void {
    try {
        $id = $data['id'];
        unset($data['id']); // rimuovi id dall'array per evitare duplicato
        $cols = implode(',', array_map(fn($k) => "`$k`", array_keys($data)));
        $phs  = implode(',', array_fill(0, count($data), '?'));
        $db->prepare("INSERT IGNORE INTO points_of_interest (id,$cols) VALUES (?,$phs)")
           ->execute([$id, ...array_values($data)]);
        $results[] = "✅ " . ($data['name_it'] ?? $id);
    } catch (PDOException $e) {
        $errors[] = "❌ " . ($data['name_it'] ?? 'POI') . ": " . $e->getMessage();
    }
}

$pois = [
  [
    'id'         => 'porta-di-sopra',
    'borough_id' => 'lacedonia',
    'category'   => 'Architettura',
    'sort_order' => 1,
    'name_it'    => 'Porta di Sopra',
    'name_en'    => 'Upper Gate',
    'name_irp'   => 'Porta re Soper',
    'desc_it'    => 'Porta di Sopra è una delle antiche porte d\'accesso al centro storico di Lacedonia. Risalente al periodo medievale, rappresenta uno degli esempi meglio conservati dell\'architettura difensiva irpina del XIII secolo. La struttura in pietra calcarea locale mostra ancora chiaramente l\'arco a tutto sesto tipico del periodo normanno-svevo.',
    'desc_en'    => 'Porta di Sopra (Upper Gate) is one of the ancient gateways to the historic center of Lacedonia. Dating back to the medieval period, it is one of the best-preserved examples of Irpinian defensive architecture from the 13th century.',
    'tags'       => 'Architettura,Medioevo,Normanni,Centro storico,Transumanza',
  ],
  [
    'id'         => 'castello-normanno',
    'borough_id' => 'lacedonia',
    'category'   => 'Architettura',
    'sort_order' => 2,
    'name_it'    => 'Castello Normanno',
    'name_en'    => 'Norman Castle',
    'desc_it'    => 'Il Castello Normanno di Lacedonia domina il borgo dall\'alto, testimone silenzioso di secoli di storia. Costruito in età normanna e ampliato in epoca sveva, il castello ha resistito ai terremoti del 1930 e del 1980, diventando simbolo della resilienza irpina.',
    'desc_en'    => 'The Norman Castle of Lacedonia dominates the village from above, a silent witness to centuries of history. Built in the Norman age and expanded in the Swabian era, the castle has survived the earthquakes of 1930 and 1980, becoming a symbol of Irpinian resilience.',
    'tags'       => 'Architettura,Castello,Normanni,Medioevo,Resilienza',
  ],
  [
    'id'         => 'piazza-primo-maggio',
    'borough_id' => 'lacedonia',
    'category'   => 'Spazi pubblici',
    'sort_order' => 10,
    'name_it'    => 'Piazza Primo Maggio',
    'name_en'    => 'Piazza Primo Maggio',
    'desc_it'    => 'Il cuore pulsante di Lacedonia. Piazza Primo Maggio è il punto d\'incontro della comunità, luogo di feste, mercati e aggregazione sociale. Circondata da edifici storici, la piazza offre una vista panoramica sulla Valle dell\'Osento.',
    'desc_en'    => 'The beating heart of Lacedonia. Piazza Primo Maggio is the community meeting point, a place for festivals, markets, and social gathering. Surrounded by historic buildings, the square offers a panoramic view of the Osento Valley.',
    'tags'       => 'Piazza,Centro storico,Vita sociale,Panorama',
  ],
];

foreach ($pois as $poi) {
    seedPoi($db, $poi, $results, $errors);
}
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-slate-900 text-white p-8">
<h1 class="text-2xl font-bold mb-4">Seed POI Lacedonia</h1>
<?php foreach ($results as $r): ?><p class="text-emerald-400"><?= htmlspecialchars($r) ?></p><?php endforeach; ?>
<?php foreach ($errors as $e): ?><p class="text-red-400"><?= htmlspecialchars($e) ?></p><?php endforeach; ?>
<div class="mt-4 flex gap-4">
  <a href="punti-interesse.php?borough=lacedonia" class="text-emerald-400 underline">→ Gestisci POI (admin)</a>
  <a href="/borghi/lacedonia/" class="text-cyan-400 underline" target="_blank">→ Vedi Lacedonia su MetaBorghi ↗</a>
</div>
</body></html>
