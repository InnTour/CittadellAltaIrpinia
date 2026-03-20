<?php
require_once __DIR__ . '/../config/db.php';
jsonHeaders();

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = $_GET['id'] ?? null;
$slug   = $_GET['slug'] ?? null;

function buildCraft(PDO $db, array $row): array {
    $cid = $row['id'];
    $row['material_type'] = fetchArray($db, 'craft_material_types', 'craft_id', $cid);

    $stmt = $db->prepare("SELECT name, values_json, price_modifier FROM craft_customization_options WHERE craft_id = ?");
    $stmt->execute([$cid]);
    $opts = $stmt->fetchAll();
    $row['customization_options'] = array_map(function($o) {
        $o['values']         = json_decode($o['values_json'] ?? '[]', true) ?? [];
        $o['price_modifier'] = (float)$o['price_modifier'];
        unset($o['values_json']);
        return $o;
    }, $opts);

    $stmt = $db->prepare("SELECT title, description FROM craft_process_steps WHERE craft_id = ? ORDER BY sort_order ASC");
    $stmt->execute([$cid]);
    $row['process_steps'] = $stmt->fetchAll();

    // Add borough_name
    if (!isset($row['borough_name']) && isset($row['borough_id'])) {
        $bs = $db->prepare("SELECT name FROM boroughs WHERE id = ?");
        $bs->execute([$row['borough_id']]);
        $br = $bs->fetch();
        $row['borough_name'] = $br ? $br['name'] : $row['borough_id'];
    }

    foreach (['lead_time_days','weight_grams','production_series_qty','reviews_count','stock_qty'] as $f) {
        if (isset($row[$f])) $row[$f] = (int)$row[$f];
    }
    $row['price']  = isset($row['price'])  ? (float)$row['price']  : null;
    $row['rating'] = isset($row['rating']) ? (float)$row['rating'] : 0.0;
    $row['is_custom_order_available'] = (bool)$row['is_custom_order_available'];
    $row['is_unique_piece']           = (bool)$row['is_unique_piece'];
    $row['is_active']                 = (bool)$row['is_active'];
    return $row;
}

if ($method === 'GET') {
    if ($id || $slug) {
        if ($slug) {
            $stmt = $db->prepare("SELECT * FROM craft_products WHERE slug = ?");
            $stmt->execute([$slug]);
        } else {
            $stmt = $db->prepare("SELECT * FROM craft_products WHERE id = ?");
            $stmt->execute([$id]);
        }
        $row = $stmt->fetch();
        if (!$row) { http_response_code(404); echo json_encode(['error' => 'Not found']); exit; }
        echo json_encode(buildCraft($db, $row));
    } else {
        $borough = $_GET['borough'] ?? null;
        if ($borough) {
            $stmt = $db->prepare("SELECT * FROM craft_products WHERE borough_id = ? AND is_active = 1 ORDER BY name");
            $stmt->execute([$borough]);
            $rows = $stmt->fetchAll();
        } else {
            $rows = $db->query("SELECT * FROM craft_products ORDER BY name ASC")->fetchAll();
        }
        echo json_encode(array_map(fn($r) => buildCraft($db, $r), $rows));
    }
    exit;
}

requireAuth();
$body = getJsonBody();

if ($method === 'POST') {
    $db->prepare("INSERT INTO craft_products
        (id, slug, name, description_short, description_long, price,
         is_custom_order_available, lead_time_days, technique_description,
         dimensions, weight_grams, artisan_id, borough_id, is_unique_piece,
         production_series_qty, rating, reviews_count, stock_qty, is_active, cover_image)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
    ->execute(_craftValues($body));
    _saveCraftArrays($db, $body);
    http_response_code(201);
    echo json_encode(['ok' => true, 'id' => $body['id']]);
    exit;
}

if ($method === 'PUT' && $id) {
    $db->prepare("UPDATE craft_products SET
        slug=?, name=?, description_short=?, description_long=?, price=?,
        is_custom_order_available=?, lead_time_days=?, technique_description=?,
        dimensions=?, weight_grams=?, artisan_id=?, borough_id=?, is_unique_piece=?,
        production_series_qty=?, rating=?, reviews_count=?, stock_qty=?, is_active=?, cover_image=?
        WHERE id=?")
    ->execute(array_merge(array_slice(_craftValues($body), 1), [$id]));
    _saveCraftArrays($db, array_merge($body, ['id' => $id]));
    echo json_encode(['ok' => true]);
    exit;
}

if ($method === 'DELETE' && $id) {
    foreach (['craft_material_types','craft_customization_options','craft_process_steps'] as $t) {
        $db->prepare("DELETE FROM `$t` WHERE craft_id = ?")->execute([$id]);
    }
    $db->prepare("DELETE FROM craft_products WHERE id = ?")->execute([$id]);
    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);

function _craftValues(array $b): array {
    return [
        $b['id'], $b['slug'], $b['name'],
        $b['description_short'] ?? null, $b['description_long'] ?? null,
        $b['price'] ?? null,
        $b['is_custom_order_available'] ? 1 : 0,
        $b['lead_time_days'] ?? null, $b['technique_description'] ?? null,
        $b['dimensions'] ?? null, $b['weight_grams'] ?? null,
        $b['artisan_id'] ?? null, $b['borough_id'] ?? null,
        $b['is_unique_piece'] ? 1 : 0, $b['production_series_qty'] ?? null,
        $b['rating'] ?? 0, $b['reviews_count'] ?? 0,
        $b['stock_qty'] ?? 0, $b['is_active'] ?? 1,
        $b['cover_image'] ?? null,
    ];
}

function _saveCraftArrays(PDO $db, array $body): void {
    $cid = $body['id'];
    replaceArray($db, 'craft_material_types', 'craft_id', $cid, $body['material_type'] ?? []);

    $db->prepare("DELETE FROM craft_customization_options WHERE craft_id = ?")->execute([$cid]);
    $stmt = $db->prepare("INSERT INTO craft_customization_options (craft_id, name, values_json, price_modifier) VALUES (?,?,?,?)");
    foreach ($body['customization_options'] ?? [] as $opt) {
        $stmt->execute([$cid, $opt['name'] ?? null, json_encode($opt['values'] ?? []), $opt['price_modifier'] ?? 0]);
    }

    $db->prepare("DELETE FROM craft_process_steps WHERE craft_id = ?")->execute([$cid]);
    $stmt = $db->prepare("INSERT INTO craft_process_steps (craft_id, title, description, sort_order) VALUES (?,?,?,?)");
    foreach ($body['process_steps'] ?? [] as $i => $step) {
        $stmt->execute([$cid, $step['title'] ?? null, $step['description'] ?? null, $i]);
    }
}
