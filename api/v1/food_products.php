<?php
require_once __DIR__ . '/../config/db.php';
jsonHeaders();

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = $_GET['id'] ?? null;

function buildFood(PDO $db, array $row): array {
    foreach (['weight_grams','shelf_life_days','stock_qty','min_order_qty'] as $f) {
        if (isset($row[$f])) $row[$f] = (int)$row[$f];
    }
    $row['price']       = isset($row['price'])       ? (float)$row['price']  : null;
    $row['is_shippable'] = (bool)$row['is_shippable'];
    $row['is_active']    = (bool)$row['is_active'];
    $row['is_featured']  = (bool)$row['is_featured'];

    // Parse JSON fields
    foreach (['allergens'] as $jf) {
        if (isset($row[$jf]) && is_string($row[$jf])) {
            $row[$jf] = json_decode($row[$jf], true) ?? [];
        }
    }

    // Add borough_name
    if (!isset($row['borough_name']) && isset($row['borough_id'])) {
        $bs = $db->prepare("SELECT name FROM boroughs WHERE id = ?");
        $bs->execute([$row['borough_id']]);
        $br = $bs->fetch();
        $row['borough_name'] = $br ? $br['name'] : $row['borough_id'];
    }

    return $row;
}

if ($method === 'GET') {
    if ($id) {
        $stmt = $db->prepare("SELECT * FROM food_products WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) { http_response_code(404); echo json_encode(['error' => 'Not found']); exit; }
        echo json_encode(buildFood($db, $row));
    } else {
        $borough  = $_GET['borough']  ?? null;
        $category = $_GET['category'] ?? null;
        if ($borough && $category) {
            $stmt = $db->prepare("SELECT * FROM food_products WHERE borough_id = ? AND category = ? AND is_active = 1 ORDER BY name");
            $stmt->execute([$borough, $category]);
        } elseif ($borough) {
            $stmt = $db->prepare("SELECT * FROM food_products WHERE borough_id = ? AND is_active = 1 ORDER BY name");
            $stmt->execute([$borough]);
        } elseif ($category) {
            $stmt = $db->prepare("SELECT * FROM food_products WHERE category = ? AND is_active = 1 ORDER BY name");
            $stmt->execute([$category]);
        } else {
            $stmt = $db->query("SELECT * FROM food_products ORDER BY name ASC");
        }
        echo json_encode(array_map(fn($r) => buildFood($db, $r), $stmt->fetchAll()));
    }
    exit;
}

requireAuth();
$body = getJsonBody();

if ($method === 'POST') {
    $db->prepare("INSERT INTO food_products
        (id, slug, name, producer_id, borough_id, category,
         description_short, description_long, tagline, pairing_suggestions,
         price, unit, weight_grams, shelf_life_days, storage_instructions,
         origin_protected, allergens, ingredients,
         stock_qty, min_order_qty, is_shippable, shipping_notes,
         is_active, is_featured, cover_image)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
    ->execute(_foodValues($body));
    http_response_code(201);
    echo json_encode(['ok' => true, 'id' => $body['id']]);
    exit;
}

if ($method === 'PUT' && $id) {
    $db->prepare("UPDATE food_products SET
        slug=?, name=?, producer_id=?, borough_id=?, category=?,
        description_short=?, description_long=?, tagline=?, pairing_suggestions=?,
        price=?, unit=?, weight_grams=?, shelf_life_days=?, storage_instructions=?,
        origin_protected=?, allergens=?, ingredients=?,
        stock_qty=?, min_order_qty=?, is_shippable=?, shipping_notes=?,
        is_active=?, is_featured=?, cover_image=? WHERE id=?")
    ->execute(array_merge(array_slice(_foodValues($body), 1), [$id]));
    echo json_encode(['ok' => true]);
    exit;
}

if ($method === 'DELETE' && $id) {
    $db->prepare("DELETE FROM food_products WHERE id = ?")->execute([$id]);
    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);

function _foodValues(array $b): array {
    return [
        $b['id'], $b['slug'], $b['name'],
        $b['producer_id'] ?? null, $b['borough_id'] ?? null,
        $b['category'] ?? null,
        $b['description_short'] ?? null, $b['description_long'] ?? null,
        $b['tagline'] ?? null, $b['pairing_suggestions'] ?? null,
        $b['price'] ?? null, $b['unit'] ?? null,
        $b['weight_grams'] ?? null, $b['shelf_life_days'] ?? null,
        $b['storage_instructions'] ?? null,
        $b['origin_protected'] ?? null, $b['allergens'] ?? null,
        $b['ingredients'] ?? null,
        $b['stock_qty'] ?? 0, $b['min_order_qty'] ?? 1,
        $b['is_shippable'] ? 1 : 0, $b['shipping_notes'] ?? null,
        $b['is_active'] ?? 1, $b['is_featured'] ?? 0,
        $b['cover_image'] ?? null,
    ];
}
