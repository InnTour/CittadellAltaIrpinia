<?php
require_once __DIR__ . '/../config/db.php';
jsonHeaders();

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = $_GET['id'] ?? null;

function buildRestaurant(PDO $db, array $row): array {
    foreach (['seats_indoor','seats_outdoor','max_group_size'] as $f) {
        if (isset($row[$f])) $row[$f] = (int)$row[$f];
    }
    $row['coordinates']        = ['lat' => (float)($row['lat'] ?? 0), 'lng' => (float)($row['lng'] ?? 0)];
    unset($row['lat'], $row['lng']);
    $row['accepts_groups']      = (bool)($row['accepts_groups'] ?? false);
    $row['b2b_open_for_contact']= (bool)($row['b2b_open_for_contact'] ?? false);
    $row['is_active']           = (bool)($row['is_active'] ?? true);
    $row['is_featured']         = (bool)($row['is_featured'] ?? false);
    $row['is_verified']         = (bool)($row['is_verified'] ?? false);
    // Parse JSON fields
    foreach (['specialties','menu_highlights','certifications','b2b_interests'] as $jf) {
        if (isset($row[$jf]) && is_string($row[$jf])) {
            $row[$jf] = json_decode($row[$jf], true) ?? [];
        } elseif (!isset($row[$jf])) {
            $row[$jf] = [];
        }
    }
    // Add borough_name via join if not already present
    if (!isset($row['borough_name']) && isset($row['borough_id'])) {
        $bs = $db->prepare("SELECT name FROM boroughs WHERE id = ?");
        $bs->execute([$row['borough_id']]);
        $br = $bs->fetch();
        $row['borough_name'] = $br ? $br['name'] : $row['borough_id'];
    }
    // Default rating/reviews for frontend compatibility
    $row['rating'] = (float)($row['rating'] ?? 0);
    $row['reviews_count'] = (int)($row['reviews_count'] ?? 0);
    return $row;
}

if ($method === 'GET') {
    if ($id) {
        $stmt = $db->prepare("SELECT * FROM restaurants WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) { http_response_code(404); echo json_encode(['error' => 'Not found']); exit; }
        echo json_encode(buildRestaurant($db, $row));
    } else {
        $borough = $_GET['borough'] ?? null;
        if ($borough) {
            $stmt = $db->prepare("SELECT * FROM restaurants WHERE borough_id = ? AND is_active = 1 ORDER BY name");
            $stmt->execute([$borough]);
        } else {
            $stmt = $db->query("SELECT * FROM restaurants ORDER BY name ASC");
        }
        echo json_encode(array_map(fn($r) => buildRestaurant($db, $r), $stmt->fetchAll()));
    }
    exit;
}

requireAuth();
$body = getJsonBody();

if ($method === 'POST') {
    $db->prepare("INSERT INTO restaurants
        (id, slug, name, type, borough_id,
         address_full, lat, lng,
         description_short, description_long, tagline,
         cuisine_type, price_range, seats_indoor, seats_outdoor,
         opening_hours, closing_day, specialties, menu_highlights,
         contact_email, contact_phone, website_url,
         social_instagram, social_facebook, social_linkedin, booking_url,
         accepts_groups, max_group_size,
         b2b_open_for_contact, b2b_interests,
         certifications, founder_name, founder_quote, tier, is_verified,
         is_active, is_featured, cover_image)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
    ->execute(_restValues($body));
    http_response_code(201);
    echo json_encode(['ok' => true, 'id' => $body['id']]);
    exit;
}

if ($method === 'PUT' && $id) {
    $db->prepare("UPDATE restaurants SET
        slug=?, name=?, type=?, borough_id=?,
        address_full=?, lat=?, lng=?,
        description_short=?, description_long=?, tagline=?,
        cuisine_type=?, price_range=?, seats_indoor=?, seats_outdoor=?,
        opening_hours=?, closing_day=?, specialties=?, menu_highlights=?,
        contact_email=?, contact_phone=?, website_url=?,
        social_instagram=?, social_facebook=?, social_linkedin=?, booking_url=?,
        accepts_groups=?, max_group_size=?,
        b2b_open_for_contact=?, b2b_interests=?,
        certifications=?, founder_name=?, founder_quote=?, tier=?, is_verified=?,
        is_active=?, is_featured=?, cover_image=? WHERE id=?")
    ->execute(array_merge(array_slice(_restValues($body), 1), [$id]));
    echo json_encode(['ok' => true]);
    exit;
}

if ($method === 'DELETE' && $id) {
    $db->prepare("DELETE FROM restaurants WHERE id = ?")->execute([$id]);
    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);

function _restValues(array $b): array {
    return [
        $b['id'], $b['slug'], $b['name'],
        $b['type'] ?? 'RISTORANTE', $b['borough_id'] ?? null,
        $b['address_full'] ?? null,
        $b['coordinates']['lat'] ?? null, $b['coordinates']['lng'] ?? null,
        $b['description_short'] ?? null, $b['description_long'] ?? null,
        $b['tagline'] ?? null,
        $b['cuisine_type'] ?? null, $b['price_range'] ?? 'MEDIO',
        $b['seats_indoor'] ?? null, $b['seats_outdoor'] ?? null,
        $b['opening_hours'] ?? null, $b['closing_day'] ?? null,
        $b['specialties'] ?? null, $b['menu_highlights'] ?? null,
        $b['contact_email'] ?? null, $b['contact_phone'] ?? null,
        $b['website_url'] ?? null,
        $b['social_instagram'] ?? null, $b['social_facebook'] ?? null,
        $b['social_linkedin'] ?? null,
        $b['booking_url'] ?? null,
        ($b['accepts_groups'] ?? false) ? 1 : 0, $b['max_group_size'] ?? null,
        ($b['b2b_open_for_contact'] ?? false) ? 1 : 0, $b['b2b_interests'] ?? null,
        $b['certifications'] ?? null, $b['founder_name'] ?? null,
        $b['founder_quote'] ?? null, $b['tier'] ?? 'BASE',
        ($b['is_verified'] ?? false) ? 1 : 0,
        $b['is_active'] ?? 1, $b['is_featured'] ?? 0,
        $b['cover_image'] ?? null,
    ];
}
