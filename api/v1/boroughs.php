<?php
require_once __DIR__ . '/../config/db.php';
jsonHeaders();

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = $_GET['id'] ?? null;
$slug   = $_GET['slug'] ?? null;

// ── Helper: costruisce un borough completo con tutti gli array ──────────────
function buildBorough(PDO $db, array $row): array {
    $bid = $row['id'];
    $row['highlights']            = fetchArray($db, 'borough_highlights',            'borough_id', $bid);
    $row['notable_products']      = fetchArray($db, 'borough_notable_products',      'borough_id', $bid);
    $row['notable_experiences']   = fetchArray($db, 'borough_notable_experiences',   'borough_id', $bid);
    $row['notable_restaurants']   = fetchArray($db, 'borough_notable_restaurants',   'borough_id', $bid);

    // gallery_images: ricostruisce oggetti {src_index, alt_text}
    $stmt = $db->prepare("SELECT src_index, alt_text FROM borough_gallery_images WHERE borough_id = ? ORDER BY sort_order");
    $stmt->execute([$bid]);
    $row['gallery_images'] = $stmt->fetchAll();

    // Parse JSON fields
    foreach (['highlights','notable_products','notable_experiences','notable_restaurants','gallery_images'] as $jf) {
        if (isset($row[$jf]) && is_string($row[$jf])) {
            $row[$jf] = json_decode($row[$jf], true) ?? [];
        }
    }

    // coordinates sub-object
    $row['coordinates'] = [
        'lat'              => (float)($row['lat'] ?? 0),
        'lng'              => (float)($row['lng'] ?? 0),
        'main_video_url'   => $row['main_video_url']  ?? '',
        'virtual_tour_url' => $row['virtual_tour_url'] ?? '',
    ];
    unset($row['lat'], $row['lng'], $row['main_video_url'], $row['virtual_tour_url']);

    // Cast numerici
    foreach (['population','altitude_meters','companies_count','hero_image_index'] as $f) {
        if (isset($row[$f])) $row[$f] = (int)$row[$f];
    }
    $row['area_km2'] = isset($row['area_km2']) ? (float)$row['area_km2'] : null;

    // Images from entity_images table
    $row['images'] = fetchEntityImages($db, 'borough', $bid);

    // Hero image object for frontend
    $heroIdx = $row['hero_image_index'] ?? 0;
    $images = $row['images'];
    if (!empty($images[$heroIdx])) {
        $row['hero_image'] = ['src' => $images[$heroIdx]['src'], 'alt' => $row['hero_image_alt'] ?? $row['name']];
    } elseif (!empty($row['cover_image'])) {
        $row['hero_image'] = ['src' => $row['cover_image'], 'alt' => $row['hero_image_alt'] ?? $row['name']];
    }

    return $row;
}

// ── GET ────────────────────────────────────────────────────────────────────
if ($method === 'GET') {
    if ($id || $slug) {
        if ($slug) {
            $stmt = $db->prepare("SELECT * FROM boroughs WHERE slug = ?");
            $stmt->execute([$slug]);
        } else {
            $stmt = $db->prepare("SELECT * FROM boroughs WHERE id = ?");
            $stmt->execute([$id]);
        }
        $row = $stmt->fetch();
        if (!$row) { http_response_code(404); echo json_encode(['error' => 'Not found']); exit; }
        echo json_encode(buildBorough($db, $row));
    } else {
        $rows = $db->query("SELECT * FROM boroughs ORDER BY name ASC")->fetchAll();
        $result = array_map(fn($r) => buildBorough($db, $r), $rows);
        echo json_encode($result);
    }
    exit;
}

// ── POST / PUT / DELETE — richiedono autenticazione ───────────────────────
requireAuth();
$body = getJsonBody();

if ($method === 'POST') {
    $db->prepare("INSERT INTO boroughs
        (id, slug, name, province, region, population, altitude_meters, area_km2,
         lat, lng, main_video_url, virtual_tour_url, description, companies_count,
         hero_image_index, hero_image_alt, cover_image)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
    ->execute([
        $body['id'], $body['slug'], $body['name'],
        $body['province'] ?? null, $body['region'] ?? null,
        $body['population'] ?? null, $body['altitude_meters'] ?? null,
        $body['area_km2'] ?? null,
        $body['coordinates']['lat'] ?? null, $body['coordinates']['lng'] ?? null,
        $body['coordinates']['main_video_url'] ?? null,
        $body['coordinates']['virtual_tour_url'] ?? null,
        $body['description'] ?? null,
        $body['companies_count'] ?? 0,
        $body['hero_image_index'] ?? 0, $body['hero_image_alt'] ?? null,
        $body['cover_image'] ?? null,
    ]);
    _saveArrays($db, $body);
    http_response_code(201);
    echo json_encode(['ok' => true, 'id' => $body['id']]);
    exit;
}

if ($method === 'PUT' && $id) {
    $db->prepare("UPDATE boroughs SET
        slug=?, name=?, province=?, region=?, population=?, altitude_meters=?,
        area_km2=?, lat=?, lng=?, main_video_url=?, virtual_tour_url=?,
        description=?, companies_count=?, hero_image_index=?, hero_image_alt=?, cover_image=?
        WHERE id=?")
    ->execute([
        $body['slug'] ?? $id, $body['name'],
        $body['province'] ?? null, $body['region'] ?? null,
        $body['population'] ?? null, $body['altitude_meters'] ?? null,
        $body['area_km2'] ?? null,
        $body['coordinates']['lat'] ?? null, $body['coordinates']['lng'] ?? null,
        $body['coordinates']['main_video_url'] ?? null,
        $body['coordinates']['virtual_tour_url'] ?? null,
        $body['description'] ?? null,
        $body['companies_count'] ?? 0,
        $body['hero_image_index'] ?? 0, $body['hero_image_alt'] ?? null,
        $body['cover_image'] ?? null,
        $id,
    ]);
    _saveArrays($db, array_merge($body, ['id' => $id]));
    echo json_encode(['ok' => true]);
    exit;
}

if ($method === 'DELETE' && $id) {
    foreach (['borough_highlights','borough_notable_products','borough_notable_experiences',
              'borough_notable_restaurants','borough_gallery_images'] as $t) {
        $db->prepare("DELETE FROM `$t` WHERE borough_id = ?")->execute([$id]);
    }
    $db->prepare("DELETE FROM boroughs WHERE id = ?")->execute([$id]);
    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);

// ── Salva tutti gli array del borgo ────────────────────────────────────────
function _saveArrays(PDO $db, array $body): void {
    $bid = $body['id'];
    replaceArray($db, 'borough_highlights',          'borough_id', $bid, $body['highlights']          ?? []);
    replaceArray($db, 'borough_notable_products',    'borough_id', $bid, $body['notable_products']    ?? []);
    replaceArray($db, 'borough_notable_experiences', 'borough_id', $bid, $body['notable_experiences'] ?? []);
    replaceArray($db, 'borough_notable_restaurants', 'borough_id', $bid, $body['notable_restaurants'] ?? []);

    // gallery_images
    $db->prepare("DELETE FROM borough_gallery_images WHERE borough_id = ?")->execute([$bid]);
    $stmt = $db->prepare("INSERT INTO borough_gallery_images (borough_id, src_index, alt_text, sort_order) VALUES (?,?,?,?)");
    foreach ($body['gallery_images'] ?? [] as $i => $img) {
        $stmt->execute([$bid, $img['src_index'] ?? $i, $img['alt_text'] ?? '', $i]);
    }
}
