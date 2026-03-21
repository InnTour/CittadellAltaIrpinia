<?php
require_once __DIR__ . '/../config/db.php';
jsonHeaders();

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = $_GET['id'] ?? null;
$slug   = $_GET['slug'] ?? null;

function buildCompany(PDO $db, array $row): array {
    $cid = $row['id'];
    $row['certifications']     = fetchArray($db, 'company_certifications', 'company_id', $cid);
    $row['b2b_interests']      = fetchArray($db, 'company_b2b_interests',  'company_id', $cid);

    $stmt = $db->prepare("SELECT year, title, entity FROM company_awards WHERE company_id = ? ORDER BY year DESC");
    $stmt->execute([$cid]);
    $row['awards'] = $stmt->fetchAll();

    $row['social_links'] = [
        'instagram' => $row['social_instagram'] ?? '#',
        'facebook'  => $row['social_facebook']  ?? '#',
        'linkedin'  => $row['social_linkedin']  ?? null,
    ];
    $row['coordinates'] = ['lat' => (float)($row['lat'] ?? 0), 'lng' => (float)($row['lng'] ?? 0)];

    unset($row['social_instagram'], $row['social_facebook'], $row['social_linkedin'],
          $row['lat'], $row['lng']);

    // Parse JSON fields
    foreach (['certifications','b2b_interests','awards','social_links'] as $jf) {
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

    foreach (['founding_year','employees_count','hero_image_index'] as $f) {
        if (isset($row[$f])) $row[$f] = (int)$row[$f];
    }
    foreach (['is_verified','is_active','b2b_open_for_contact'] as $f) {
        if (isset($row[$f])) $row[$f] = (bool)$row[$f];
    }

    // Images from entity_images table
    $row['images'] = fetchEntityImages($db, 'company', $cid);

    // Hero image and founder image objects for frontend
    $images = $row['images'];
    $heroIdx = $row['hero_image_index'] ?? 0;
    if (!empty($images[$heroIdx])) {
        $row['hero_image'] = ['src' => $images[$heroIdx]['src'], 'alt' => $row['hero_image_alt'] ?? $row['name']];
    } elseif (!empty($row['cover_image'])) {
        $row['hero_image'] = ['src' => $row['cover_image'], 'alt' => $row['hero_image_alt'] ?? $row['name']];
    }

    // Founder image (first image or cover as fallback)
    if (!empty($images[1])) {
        $row['founder_image'] = ['src' => $images[1]['src'], 'alt' => $row['founder_name'] ?? $row['name']];
    } elseif (!empty($row['cover_image'])) {
        $row['founder_image'] = ['src' => $row['cover_image'], 'alt' => $row['founder_name'] ?? $row['name']];
    }

    return $row;
}

// ── Fallback: normalize a restaurant row to company-like format ──────────
function restaurantAsCompany(PDO $db, array $row): array {
    $out = [
        'id'                  => $row['id'],
        'slug'                => $row['slug'],
        'name'                => $row['name'],
        'type'                => strtolower($row['type'] ?? 'ristorante'),
        '_entity_type'        => 'restaurant',
        'legal_name'          => null,
        'vat_number'          => null,
        'tagline'             => $row['tagline'] ?? null,
        'description_short'   => $row['description_short'] ?? null,
        'description_long'    => $row['description_long'] ?? null,
        'founding_year'       => null,
        'employees_count'     => null,
        'borough_id'          => $row['borough_id'] ?? null,
        'borough_name'        => getBoroughName($db, $row['borough_id'] ?? null),
        'address_full'        => $row['address_full'] ?? null,
        'coordinates'         => buildCoordinates($row),
        'contact_email'       => $row['contact_email'] ?? null,
        'contact_phone'       => $row['contact_phone'] ?? null,
        'website_url'         => $row['website_url'] ?? null,
        'booking_url'         => $row['booking_url'] ?? null,
        'social_links'        => [
            'instagram' => $row['social_instagram'] ?? null,
            'facebook'  => $row['social_facebook'] ?? null,
            'linkedin'  => $row['social_linkedin'] ?? null,
        ],
        'tier'                => $row['tier'] ?? 'BASE',
        'is_verified'         => (bool)($row['is_verified'] ?? false),
        'is_active'           => (bool)($row['is_active'] ?? true),
        'b2b_open_for_contact' => (bool)($row['b2b_open_for_contact'] ?? false),
        'founder_name'        => $row['founder_name'] ?? null,
        'founder_quote'       => $row['founder_quote'] ?? null,
        'main_video_url'      => null,
        'virtual_tour_url'    => null,
        'cover_image'         => $row['cover_image'] ?? null,
        'hero_image_index'    => 0,
        'hero_image_alt'      => null,
        'certifications'      => parseJsonOrText($row['certifications'] ?? null, "\n"),
        'b2b_interests'       => parseJsonOrText($row['b2b_interests'] ?? null, ','),
        'awards'              => [],
        'rating'              => (float)($row['rating'] ?? 0),
        'reviews_count'       => (int)($row['reviews_count'] ?? 0),
        // Restaurant-specific fields
        'cuisine_type'        => $row['cuisine_type'] ?? null,
        'price_range'         => $row['price_range'] ?? null,
        'opening_hours'       => $row['opening_hours'] ?? null,
        'closing_day'         => $row['closing_day'] ?? null,
        'specialties'         => parseJsonOrText($row['specialties'] ?? null, ','),
        'menu_highlights'     => parseJsonOrText($row['menu_highlights'] ?? null, '|'),
    ];
    // Images
    $images = fetchEntityImages($db, 'restaurant', $row['id']);
    $out['images'] = $images;
    if (!empty($images[0])) {
        $out['hero_image'] = ['src' => $images[0]['src'], 'alt' => $out['name']];
    } elseif (!empty($out['cover_image'])) {
        $out['hero_image'] = ['src' => $out['cover_image'], 'alt' => $out['name']];
    }
    if (!empty($images[1])) {
        $out['founder_image'] = ['src' => $images[1]['src'], 'alt' => $out['founder_name'] ?? $out['name']];
    } elseif (!empty($out['cover_image'])) {
        $out['founder_image'] = ['src' => $out['cover_image'], 'alt' => $out['founder_name'] ?? $out['name']];
    }
    return $out;
}

// ── Fallback: normalize an accommodation row to company-like format ─────
function accommodationAsCompany(PDO $db, array $row): array {
    $out = [
        'id'                  => $row['id'],
        'slug'                => $row['slug'],
        'name'                => $row['name'],
        'type'                => strtolower($row['type'] ?? 'alloggio'),
        '_entity_type'        => 'accommodation',
        'legal_name'          => null,
        'vat_number'          => null,
        'tagline'             => $row['tagline'] ?? null,
        'description_short'   => $row['description_short'] ?? null,
        'description_long'    => $row['description_long'] ?? null,
        'founding_year'       => null,
        'employees_count'     => null,
        'borough_id'          => $row['borough_id'] ?? null,
        'borough_name'        => getBoroughName($db, $row['borough_id'] ?? null),
        'address_full'        => $row['address_full'] ?? null,
        'coordinates'         => buildCoordinates($row),
        'contact_email'       => $row['contact_email'] ?? null,
        'contact_phone'       => $row['contact_phone'] ?? null,
        'website_url'         => $row['website_url'] ?? null,
        'booking_url'         => $row['booking_url'] ?? null,
        'social_links'        => [
            'instagram' => $row['social_instagram'] ?? null,
            'facebook'  => $row['social_facebook'] ?? null,
            'linkedin'  => $row['social_linkedin'] ?? null,
        ],
        'tier'                => $row['tier'] ?? 'BASE',
        'is_verified'         => (bool)($row['is_verified'] ?? false),
        'is_active'           => (bool)($row['is_active'] ?? true),
        'b2b_open_for_contact' => (bool)($row['b2b_open_for_contact'] ?? false),
        'founder_name'        => $row['founder_name'] ?? null,
        'founder_quote'       => $row['founder_quote'] ?? null,
        'main_video_url'      => $row['main_video_url'] ?? null,
        'virtual_tour_url'    => $row['virtual_tour_url'] ?? null,
        'cover_image'         => $row['cover_image'] ?? null,
        'hero_image_index'    => 0,
        'hero_image_alt'      => null,
        'certifications'      => parseJsonOrText($row['certifications'] ?? null, "\n"),
        'b2b_interests'       => parseJsonOrText($row['b2b_interests'] ?? null, ','),
        'awards'              => [],
        'rating'              => (float)($row['rating'] ?? 0),
        'reviews_count'       => (int)($row['reviews_count'] ?? 0),
        // Accommodation-specific fields
        'rooms'               => isset($row['rooms_count']) ? (int)$row['rooms_count'] : null,
        'max_guests'          => isset($row['max_guests']) ? (int)$row['max_guests'] : null,
        'price_min'           => isset($row['price_per_night_from']) ? (float)$row['price_per_night_from'] : null,
        'stars'               => $row['stars_or_category'] ?? null,
        'amenities'           => parseJsonOrText($row['amenities'] ?? null, ','),
    ];
    // Images
    $images = fetchEntityImages($db, 'accommodation', $row['id']);
    $out['images'] = $images;
    if (!empty($images[0])) {
        $out['hero_image'] = ['src' => $images[0]['src'], 'alt' => $out['name']];
    } elseif (!empty($out['cover_image'])) {
        $out['hero_image'] = ['src' => $out['cover_image'], 'alt' => $out['name']];
    }
    if (!empty($images[1])) {
        $out['founder_image'] = ['src' => $images[1]['src'], 'alt' => $out['founder_name'] ?? $out['name']];
    } elseif (!empty($out['cover_image'])) {
        $out['founder_image'] = ['src' => $out['cover_image'], 'alt' => $out['founder_name'] ?? $out['name']];
    }
    return $out;
}

// ── Fallback: normalize an experience row to company-like format ────────
function experienceAsCompany(PDO $db, array $row): array {
    $out = [
        'id'                  => $row['id'],
        'slug'                => $row['slug'],
        'name'                => $row['title'] ?? $row['name'] ?? '',
        'type'                => 'esperienza',
        '_entity_type'        => 'experience',
        'legal_name'          => null,
        'vat_number'          => null,
        'tagline'             => $row['tagline'] ?? null,
        'description_short'   => $row['description_short'] ?? null,
        'description_long'    => $row['description_long'] ?? null,
        'founding_year'       => null,
        'employees_count'     => null,
        'borough_id'          => $row['borough_id'] ?? null,
        'borough_name'        => getBoroughName($db, $row['borough_id'] ?? null),
        'address_full'        => null,
        'coordinates'         => buildCoordinates($row),
        'contact_email'       => null,
        'contact_phone'       => null,
        'website_url'         => null,
        'booking_url'         => null,
        'social_links'        => ['instagram' => null, 'facebook' => null, 'linkedin' => null],
        'tier'                => 'BASE',
        'is_verified'         => false,
        'is_active'           => (bool)($row['is_active'] ?? true),
        'b2b_open_for_contact' => false,
        'founder_name'        => null,
        'founder_quote'       => null,
        'main_video_url'      => null,
        'virtual_tour_url'    => null,
        'cover_image'         => $row['cover_image'] ?? null,
        'hero_image_index'    => 0,
        'hero_image_alt'      => null,
        'certifications'      => [],
        'b2b_interests'       => [],
        'awards'              => [],
        'rating'              => (float)($row['rating'] ?? 0),
        'reviews_count'       => (int)($row['reviews_count'] ?? 0),
        // Experience-specific fields
        'category'            => $row['category'] ?? null,
        'duration_minutes'    => isset($row['duration_minutes']) ? (int)$row['duration_minutes'] : null,
        'price_per_person'    => isset($row['price_per_person']) ? (float)$row['price_per_person'] : null,
        'difficulty_level'    => $row['difficulty_level'] ?? null,
    ];
    // Images
    $images = fetchEntityImages($db, 'experience', $row['id']);
    $out['images'] = $images;
    if (!empty($images[0])) {
        $out['hero_image'] = ['src' => $images[0]['src'], 'alt' => $out['name']];
    } elseif (!empty($out['cover_image'])) {
        $out['hero_image'] = ['src' => $out['cover_image'], 'alt' => $out['name']];
    }
    return $out;
}

// ── Fallback slug lookup across all entity tables ───────────────────────
function findEntityBySlug(PDO $db, string $lookup, string $field = 'slug'): ?array {
    $col = $field === 'id' ? 'id' : 'slug';

    // 1. restaurants
    $stmt = $db->prepare("SELECT * FROM restaurants WHERE `$col` = ?");
    $stmt->execute([$lookup]);
    $row = $stmt->fetch();
    if ($row) return restaurantAsCompany($db, $row);

    // 2. accommodations
    $stmt = $db->prepare("SELECT * FROM accommodations WHERE `$col` = ?");
    $stmt->execute([$lookup]);
    $row = $stmt->fetch();
    if ($row) return accommodationAsCompany($db, $row);

    // 3. experiences
    $stmt = $db->prepare("SELECT * FROM experiences WHERE `$col` = ?");
    $stmt->execute([$lookup]);
    $row = $stmt->fetch();
    if ($row) return experienceAsCompany($db, $row);

    return null;
}

if ($method === 'GET') {
    if ($id || $slug) {
        if ($slug) {
            $stmt = $db->prepare("SELECT * FROM companies WHERE slug = ?");
            $stmt->execute([$slug]);
        } else {
            $stmt = $db->prepare("SELECT * FROM companies WHERE id = ?");
            $stmt->execute([$id]);
        }
        $row = $stmt->fetch();
        if ($row) {
            echo json_encode(buildCompany($db, $row));
            exit;
        }
        // Fallback: search in restaurants, accommodations, experiences
        $fallback = findEntityBySlug($db, $slug ?? $id, $slug ? 'slug' : 'id');
        if ($fallback) {
            echo json_encode($fallback);
            exit;
        }
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
        exit;
    } else {
        $borough = $_GET['borough'] ?? null;
        if ($borough) {
            $stmt = $db->prepare("SELECT * FROM companies WHERE borough_id = ? AND is_active = 1 ORDER BY name");
            $stmt->execute([$borough]);
        } else {
            $stmt = $db->query("SELECT * FROM companies ORDER BY name ASC");
        }
        $rows = $stmt->fetchAll();
        echo json_encode(array_map(fn($r) => buildCompany($db, $r), $rows));
    }
    exit;
}

requireWriteAccess();
$body = getJsonBody();

if ($method === 'POST') {
    $db->prepare("INSERT INTO companies
        (id, slug, name, legal_name, vat_number, type, tagline, description_short,
         description_long, founding_year, employees_count, borough_id, address_full,
         lat, lng, contact_email, contact_phone, website_url, social_instagram,
         social_facebook, social_linkedin, tier, is_verified, is_active,
         b2b_open_for_contact, founder_name, founder_quote, main_video_url,
         virtual_tour_url, hero_image_index, hero_image_alt, cover_image)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
    ->execute(_companyValues($body));
    _saveCompanyArrays($db, $body);
    http_response_code(201);
    echo json_encode(['ok' => true, 'id' => $body['id']]);
    exit;
}

if ($method === 'PUT' && $id) {
    $db->prepare("UPDATE companies SET
        slug=?, name=?, legal_name=?, vat_number=?, type=?, tagline=?,
        description_short=?, description_long=?, founding_year=?, employees_count=?,
        borough_id=?, address_full=?, lat=?, lng=?, contact_email=?, contact_phone=?,
        website_url=?, social_instagram=?, social_facebook=?, social_linkedin=?,
        tier=?, is_verified=?, is_active=?, b2b_open_for_contact=?, founder_name=?,
        founder_quote=?, main_video_url=?, virtual_tour_url=?, hero_image_index=?,
        hero_image_alt=?, cover_image=? WHERE id=?")
    ->execute(array_merge(array_slice(_companyValues($body), 1), [$id]));
    _saveCompanyArrays($db, array_merge($body, ['id' => $id]));
    echo json_encode(['ok' => true]);
    exit;
}

if ($method === 'DELETE' && $id) {
    foreach (['company_certifications','company_b2b_interests','company_awards'] as $t) {
        $db->prepare("DELETE FROM `$t` WHERE company_id = ?")->execute([$id]);
    }
    $db->prepare("DELETE FROM companies WHERE id = ?")->execute([$id]);
    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);

function _companyValues(array $b): array {
    $sl = $b['social_links'] ?? [];
    return [
        $b['id'], $b['slug'], $b['name'],
        $b['legal_name'] ?? null, $b['vat_number'] ?? null,
        $b['type'] ?? 'MISTO', $b['tagline'] ?? null,
        $b['description_short'] ?? null, $b['description_long'] ?? null,
        $b['founding_year'] ?? null, $b['employees_count'] ?? null,
        $b['borough_id'] ?? null, $b['address_full'] ?? null,
        $b['coordinates']['lat'] ?? null, $b['coordinates']['lng'] ?? null,
        $b['contact_email'] ?? null, $b['contact_phone'] ?? null,
        $b['website_url'] ?? null,
        $sl['instagram'] ?? null, $sl['facebook'] ?? null, $sl['linkedin'] ?? null,
        $b['tier'] ?? 'BASE',
        $b['is_verified'] ? 1 : 0, $b['is_active'] ? 1 : 0,
        $b['b2b_open_for_contact'] ? 1 : 0,
        $b['founder_name'] ?? null, $b['founder_quote'] ?? null,
        $b['main_video_url'] ?? null, $b['virtual_tour_url'] ?? null,
        $b['hero_image_index'] ?? 0, $b['hero_image_alt'] ?? null,
        $b['cover_image'] ?? null,
    ];
}

function _saveCompanyArrays(PDO $db, array $body): void {
    $cid = $body['id'];
    replaceArray($db, 'company_certifications', 'company_id', $cid, $body['certifications'] ?? []);
    replaceArray($db, 'company_b2b_interests',  'company_id', $cid, $body['b2b_interests']  ?? []);

    $db->prepare("DELETE FROM company_awards WHERE company_id = ?")->execute([$cid]);
    $stmt = $db->prepare("INSERT INTO company_awards (company_id, year, title, entity) VALUES (?,?,?,?)");
    foreach ($body['awards'] ?? [] as $aw) {
        $stmt->execute([$cid, $aw['year'] ?? null, $aw['title'] ?? null, $aw['entity'] ?? null]);
    }
}
