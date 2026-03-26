<?php
require_once __DIR__ . '/../config/db.php';
requireAdminSession();
$db = getDB();

// Ripristino completo del borgo Andretta
$db->prepare("INSERT INTO boroughs
    (id, slug, name, province, region, population, altitude_meters, area_km2,
     lat, lng, main_video_url, virtual_tour_url, description, companies_count,
     hero_image_index, hero_image_alt)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ON DUPLICATE KEY UPDATE
    slug=VALUES(slug), name=VALUES(name), province=VALUES(province),
    region=VALUES(region), population=VALUES(population),
    altitude_meters=VALUES(altitude_meters), area_km2=VALUES(area_km2),
    lat=VALUES(lat), lng=VALUES(lng),
    description=VALUES(description), companies_count=VALUES(companies_count),
    hero_image_index=VALUES(hero_image_index), hero_image_alt=VALUES(hero_image_alt)")
->execute([
    'andretta', 'andretta', 'Andretta', 'Avellino', 'Campania',
    1650, 850, 43.5, 40.938, 15.325,
    '', '',
    "Andretta è un borgo dell'Alta Irpinia noto per la sua tradizione tessile artigianale e per il suggestivo centro storico medievale che domina la valle dell'Ofanto.",
    1, 0, 'Vista panoramica di Andretta',
]);

replaceArray($db, 'borough_highlights', 'borough_id', 'andretta', [
    'Museo della Civiltà Contadina',
    'Tessitura artigianale',
    'Panorama Valle Ofanto',
]);
replaceArray($db, 'borough_notable_products', 'borough_id', 'andretta', [
    'Tessuti artigianali',
    'Olio extravergine',
]);
replaceArray($db, 'borough_notable_experiences', 'borough_id', 'andretta', [
    'Workshop tessitura',
    'Passeggiata storica',
]);
replaceArray($db, 'borough_notable_restaurants', 'borough_id', 'andretta', [
    "Agriturismo Le Valli dell'Ofanto",
    'Trattoria della Nonna Rossella',
    'Osteria del Castello di Andretta',
    'Bar Ristorante Il Telaio',
]);

$pageTitle = 'Ripristino Andretta';
require '_layout.php';
?>
<div class="bg-emerald-900/40 border border-emerald-600 text-emerald-300 px-6 py-5 rounded-xl mb-6">
  <h2 class="text-lg font-bold mb-2">✅ Andretta ripristinata con successo</h2>
  <ul class="text-sm space-y-1">
    <li>ID: <code class="bg-slate-700 px-1 rounded">andretta</code></li>
    <li>Nome: Andretta</li>
    <li>Provincia: Avellino · Regione: Campania</li>
    <li>Popolazione: 1.650 · Altitudine: 850 m · Area: 43,5 km²</li>
    <li>Highlights: Museo della Civiltà Contadina, Tessitura artigianale, Panorama Valle Ofanto</li>
    <li>Prodotti tipici: Tessuti artigianali, Olio extravergine</li>
    <li>Esperienze: Workshop tessitura, Passeggiata storica</li>
    <li>Ristoranti: 4 inseriti</li>
  </ul>
</div>
<div class="flex gap-4">
  <a href="borghi.php?edit=andretta" class="px-6 py-2.5 bg-ambra-600 hover:bg-ambra-700 text-white text-sm font-semibold rounded-lg transition-colors" style="background:#d97706">
    Apri scheda Andretta →
  </a>
  <a href="borghi.php" class="px-6 py-2.5 bg-slate-600 hover:bg-slate-500 text-white text-sm rounded-lg transition-colors">
    Torna alla lista borghi
  </a>
</div>
<?php require '_footer.php'; ?>
