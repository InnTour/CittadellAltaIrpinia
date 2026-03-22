<?php
require_once __DIR__ . '/../config/db.php';
jsonHeaders();

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = $_GET['id'] ?? null;
$slug   = $_GET['slug'] ?? null;

// ── Helper: builds a complete restaurant object for the frontend ──────────
function buildRestaurant(PDO $db, array $row): array {
    $out = [];

    // Scalar string fields (pass through)
    foreach ([
        'id', 'slug', 'name', 'type', 'borough_id',
        'address_full', 'description_short', 'description_long', 'tagline',
        'cuisine_type', 'price_range',
        'opening_hours', 'closing_day',
        'website_url', 'booking_url',
        'founder_name', 'founder_quote', 'tier', 'cover_image',
        'main_video_url', 'virtual_tour_url',
    ] as $f) {
        $out[$f] = $row[$f] ?? null;
    }

    // borough_name lookup
    $out['borough_name'] = getBoroughName($db, $row['borough_id'] ?? null);

    // coordinates as {lat, lng} object
    $out['coordinates'] = buildCoordinates($row);

    // Integer fields
    foreach (['seats_indoor', 'seats_outdoor', 'max_group_size'] as $f) {
        $out[$f] = isset($row[$f]) ? (int)$row[$f] : null;
    }

    // Rating & reviews — proper numeric types
    $out['rating']        = (float)($row['rating'] ?? 0);
    $out['reviews_count'] = (int)($row['reviews_count'] ?? 0);

    // Boolean fields
    $out['accepts_groups']       = (bool)($row['accepts_groups'] ?? false);
    $out['b2b_open_for_contact'] = (bool)($row['b2b_open_for_contact'] ?? false);
    $out['is_active']            = (bool)($row['is_active'] ?? true);
    $out['is_featured']          = (bool)($row['is_featured'] ?? false);
    $out['is_verified']          = (bool)($row['is_verified'] ?? false);

    // Array fields — stored as delimited strings in DB
    $out['specialties']      = parseJsonOrText($row['specialties'] ?? null, ',');
    $out['menu_highlights']  = parseJsonOrText($row['menu_highlights'] ?? null, '|');
    $out['certifications']   = parseJsonOrText($row['certifications'] ?? null, "\n");
    $out['b2b_interests']    = parseJsonOrText($row['b2b_interests'] ?? null, ',');

    // Contact fields — mapped from DB column names
    $out['email'] = $row['contact_email'] ?? null;
    $out['phone'] = $row['contact_phone'] ?? null;

    // Social links object
    $out['social_links'] = [
        'instagram' => $row['social_instagram'] ?? null,
        'facebook'  => $row['social_facebook'] ?? null,
        'linkedin'  => $row['social_linkedin'] ?? null,
    ];

    // Images from entity_images table
    $out['images'] = fetchEntityImages($db, 'restaurant', $row['id']);

    return $out;
}

// ── GET ────────────────────────────────────────────────────────────────────
if ($method === 'GET') {
    if ($id || $slug) {
        if ($slug) {
            $stmt = $db->prepare("SELECT * FROM restaurants WHERE slug = ?");
            $stmt->execute([$slug]);
        } else {
            $stmt = $db->prepare("SELECT * FROM restaurants WHERE id = ?");
            $stmt->execute([$id]);
        }
        $row = $stmt->fetch();
        // Fallback: if slug lookup fails, try by id (covers slug/id mismatch in static data)
        if (!$row && $slug) {
            $stmt2 = $db->prepare("SELECT * FROM restaurants WHERE id = ?");
            $stmt2->execute([$slug]);
            $row = $stmt2->fetch();
        }
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

// ── POST / PUT / DELETE — require authentication ───────────────────────────
requireWriteAccess();
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
         main_video_url, virtual_tour_url,
         accepts_groups, max_group_size,
         b2b_open_for_contact, b2b_interests,
         certifications, founder_name, founder_quote, tier, is_verified,
         is_active, is_featured, cover_image)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
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
        main_video_url=?, virtual_tour_url=?,
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
        $b['main_video_url'] ?? null, $b['virtual_tour_url'] ?? null,
        ($b['accepts_groups'] ?? false) ? 1 : 0, $b['max_group_size'] ?? null,
        ($b['b2b_open_for_contact'] ?? false) ? 1 : 0, $b['b2b_interests'] ?? null,
        $b['certifications'] ?? null, $b['founder_name'] ?? null,
        $b['founder_quote'] ?? null, $b['tier'] ?? 'BASE',
        ($b['is_verified'] ?? false) ? 1 : 0,
        $b['is_active'] ?? 1, $b['is_featured'] ?? 0,
        $b['cover_image'] ?? null,
    ];
}
