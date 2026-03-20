<?php
require_once __DIR__ . '/../config/db.php';
jsonHeaders();

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = $_GET['id'] ?? null;

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
    return $row;
}

if ($method === 'GET') {
    if ($id) {
        $stmt = $db->prepare("SELECT * FROM companies WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) { http_response_code(404); echo json_encode(['error' => 'Not found']); exit; }
        echo json_encode(buildCompany($db, $row));
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

requireAuth();
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
