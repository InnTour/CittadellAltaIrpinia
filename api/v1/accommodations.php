<?php
require_once __DIR__ . '/../config/db.php';
jsonHeaders();

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = $_GET['id'] ?? null;
$slug   = $_GET['slug'] ?? null;

function buildAccommodation(PDO $db, array $row): array {
    $out = [];

    // Identity
    $out['id']          = $row['id'];
    $out['slug']        = $row['slug'] ?? null;
    $out['name']        = $row['name'] ?? '';
    $out['type']        = $row['type'] ?? 'AGRITURISMO';
    $out['provider_id'] = $row['provider_id'] ?? null;
    $out['borough_id']  = $row['borough_id'] ?? null;
    $out['borough_name'] = getBoroughName($db, $row['borough_id'] ?? null);

    // Location
    $out['address_full']       = $row['address_full'] ?? null;
    $out['coordinates']        = buildCoordinates($row);
    $out['distance_center_km'] = isset($row['distance_center_km']) ? (float)$row['distance_center_km'] : null;

    // Descriptions
    $out['description_short'] = $row['description_short'] ?? null;
    $out['description_long']  = $row['description_long'] ?? null;
    $out['tagline']           = $row['tagline'] ?? null;

    // Capacity & pricing
    $out['rooms']     = isset($row['rooms_count']) ? (int)$row['rooms_count'] : null;
    $out['max_guests'] = isset($row['max_guests']) ? (int)$row['max_guests'] : null;
    $out['price_min']  = isset($row['price_per_night_from']) ? (float)$row['price_per_night_from'] : null;
    $out['price_max']  = isset($row['price_per_night_from']) ? (float)$row['price_per_night_from'] : null;
    $out['stars']      = $row['stars_or_category'] ?? null;

    // Check-in / check-out
    $out['check_in']  = $row['check_in_time'] ?? null;
    $out['check_out'] = $row['check_out_time'] ?? null;
    $out['min_stay']  = isset($row['min_stay_nights']) ? (int)$row['min_stay_nights'] : null;

    // Arrays (stored as JSON or newline/comma-separated text)
    $out['amenities']         = parseJsonOrText($row['amenities'] ?? null, "\n");
    $out['accessibility']     = parseJsonOrText($row['accessibility'] ?? null, "\n");
    $out['languages_spoken']  = parseJsonOrText($row['languages_spoken'] ?? null, ',');
    $out['certifications']    = parseJsonOrText($row['certifications'] ?? null, "\n");
    $out['b2b_interests']     = parseJsonOrText($row['b2b_interests'] ?? null, ',');

    // Policies
    $out['cancellation_policy'] = $row['cancellation_policy'] ?? null;

    // Booking
    $out['booking_email'] = $row['booking_email'] ?? null;
    $out['booking_phone'] = $row['booking_phone'] ?? null;
    $out['booking_url']   = $row['booking_url'] ?? null;

    // Media
    $out['cover_image']     = $row['cover_image'] ?? null;
    $out['main_video_url']  = $row['main_video_url'] ?? null;
    $out['virtual_tour_url'] = $row['virtual_tour_url'] ?? null;
    $out['images']          = fetchEntityImages($db, 'accommodation', $row['id']);

    // Contact — mapped to frontend-friendly keys
    $out['email']   = $row['contact_email'] ?? null;
    $out['phone']   = $row['contact_phone'] ?? null;
    $out['website'] = $row['website_url'] ?? null;

    // Social links object
    $out['social_links'] = [
        'instagram' => $row['social_instagram'] ?? null,
        'facebook'  => $row['social_facebook'] ?? null,
        'linkedin'  => $row['social_linkedin'] ?? null,
    ];

    // Founder
    $out['founder_name']  = $row['founder_name'] ?? null;
    $out['founder_quote'] = $row['founder_quote'] ?? null;

    // Tier & flags
    $out['tier']                = $row['tier'] ?? 'BASE';
    $out['is_active']           = (bool)($row['is_active'] ?? true);
    $out['is_featured']         = (bool)($row['is_featured'] ?? false);
    $out['is_verified']         = (bool)($row['is_verified'] ?? false);
    $out['b2b_open_for_contact'] = (bool)($row['b2b_open_for_contact'] ?? false);

    // Rating
    $out['rating']        = (float)($row['rating'] ?? 0);
    $out['reviews_count'] = (int)($row['reviews_count'] ?? 0);

    // Keep raw DB column names for admin compatibility
    $out['rooms_count']          = $out['rooms'];
    $out['price_per_night_from'] = $out['price_min'];
    $out['stars_or_category']    = $out['stars'];
    $out['check_in_time']        = $out['check_in'];
    $out['check_out_time']       = $out['check_out'];
    $out['min_stay_nights']      = $out['min_stay'];
    $out['contact_email']        = $out['email'];
    $out['contact_phone']        = $out['phone'];
    $out['website_url']          = $out['website'];

    return $out;
}

// ── GET ────────────────────────────────────────────────────────────────────
if ($method === 'GET') {
    if ($id || $slug) {
        if ($slug) {
            $stmt = $db->prepare("SELECT * FROM accommodations WHERE slug = ?");
            $stmt->execute([$slug]);
        } else {
            $stmt = $db->prepare("SELECT * FROM accommodations WHERE id = ?");
            $stmt->execute([$id]);
        }
        $row = $stmt->fetch();
        if (!$row) { http_response_code(404); echo json_encode(['error' => 'Not found']); exit; }
        echo json_encode(buildAccommodation($db, $row));
    } else {
        $borough = $_GET['borough'] ?? null;
        if ($borough) {
            $stmt = $db->prepare("SELECT * FROM accommodations WHERE borough_id = ? AND is_active = 1 ORDER BY name");
            $stmt->execute([$borough]);
        } else {
            $stmt = $db->query("SELECT * FROM accommodations ORDER BY name ASC");
        }
        echo json_encode(array_map(fn($r) => buildAccommodation($db, $r), $stmt->fetchAll()));
    }
    exit;
}

// ── POST / PUT / DELETE — richiedono autenticazione ───────────────────────
requireAuth();
$body = getJsonBody();

if ($method === 'POST') {
    $db->prepare("INSERT INTO accommodations
        (id, slug, name, type, provider_id, borough_id,
         address_full, lat, lng, distance_center_km,
         description_short, description_long, tagline,
         rooms_count, max_guests, price_per_night_from, stars_or_category,
         check_in_time, check_out_time, min_stay_nights,
         amenities, accessibility, languages_spoken, cancellation_policy,
         booking_email, booking_phone, booking_url,
         main_video_url, virtual_tour_url,
         contact_email, contact_phone, website_url,
         social_instagram, social_facebook, social_linkedin,
         certifications, founder_name, founder_quote, tier, is_verified,
         b2b_open_for_contact, b2b_interests,
         is_active, is_featured, cover_image)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
    ->execute(_accValues($body));
    http_response_code(201);
    echo json_encode(['ok' => true, 'id' => $body['id']]);
    exit;
}

if ($method === 'PUT' && $id) {
    $db->prepare("UPDATE accommodations SET
        slug=?, name=?, type=?, provider_id=?, borough_id=?,
        address_full=?, lat=?, lng=?, distance_center_km=?,
        description_short=?, description_long=?, tagline=?,
        rooms_count=?, max_guests=?, price_per_night_from=?, stars_or_category=?,
        check_in_time=?, check_out_time=?, min_stay_nights=?,
        amenities=?, accessibility=?, languages_spoken=?, cancellation_policy=?,
        booking_email=?, booking_phone=?, booking_url=?,
        main_video_url=?, virtual_tour_url=?,
        contact_email=?, contact_phone=?, website_url=?,
        social_instagram=?, social_facebook=?, social_linkedin=?,
        certifications=?, founder_name=?, founder_quote=?, tier=?, is_verified=?,
        b2b_open_for_contact=?, b2b_interests=?,
        is_active=?, is_featured=?, cover_image=? WHERE id=?")
    ->execute(array_merge(array_slice(_accValues($body), 1), [$id]));
    echo json_encode(['ok' => true]);
    exit;
}

if ($method === 'DELETE' && $id) {
    $db->prepare("DELETE FROM accommodations WHERE id = ?")->execute([$id]);
    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);

function _accValues(array $b): array {
    return [
        $b['id'], $b['slug'], $b['name'],
        $b['type'] ?? 'AGRITURISMO',
        $b['provider_id'] ?? null, $b['borough_id'] ?? null,
        $b['address_full'] ?? null,
        $b['coordinates']['lat'] ?? null, $b['coordinates']['lng'] ?? null,
        $b['distance_center_km'] ?? null,
        $b['description_short'] ?? null, $b['description_long'] ?? null,
        $b['tagline'] ?? null,
        $b['rooms_count'] ?? null, $b['max_guests'] ?? null,
        $b['price_per_night_from'] ?? null, $b['stars_or_category'] ?? null,
        $b['check_in_time'] ?? null, $b['check_out_time'] ?? null,
        $b['min_stay_nights'] ?? 1,
        $b['amenities'] ?? null, $b['accessibility'] ?? null,
        $b['languages_spoken'] ?? null, $b['cancellation_policy'] ?? null,
        $b['booking_email'] ?? null, $b['booking_phone'] ?? null,
        $b['booking_url'] ?? null,
        $b['main_video_url'] ?? null, $b['virtual_tour_url'] ?? null,
        $b['contact_email'] ?? null, $b['contact_phone'] ?? null,
        $b['website_url'] ?? null,
        $b['social_instagram'] ?? null, $b['social_facebook'] ?? null,
        $b['social_linkedin'] ?? null,
        $b['certifications'] ?? null, $b['founder_name'] ?? null,
        $b['founder_quote'] ?? null, $b['tier'] ?? 'BASE',
        ($b['is_verified'] ?? false) ? 1 : 0,
        ($b['b2b_open_for_contact'] ?? false) ? 1 : 0, $b['b2b_interests'] ?? null,
        $b['is_active'] ?? 1, $b['is_featured'] ?? 0,
        $b['cover_image'] ?? null,
    ];
}
