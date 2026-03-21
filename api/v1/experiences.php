<?php
require_once __DIR__ . '/../config/db.php';
jsonHeaders();

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = $_GET['id'] ?? null;
$slug   = $_GET['slug'] ?? null;

function buildExperience(PDO $db, array $row): array {
    $eid = $row['id'];

    // Images gallery
    $row['images'] = fetchEntityImages($db, 'experience', $eid);

    // 1-to-many arrays
    $row['languages_available'] = fetchArray($db, 'experience_languages', 'experience_id', $eid, 'lang');
    $row['includes']            = fetchArray($db, 'experience_includes',  'experience_id', $eid);
    $row['excludes']            = fetchArray($db, 'experience_excludes',  'experience_id', $eid);
    $row['what_to_bring']       = fetchArray($db, 'experience_bring',     'experience_id', $eid);
    $row['seasonal_tags']       = fetchArray($db, 'experience_seasonal_tags', 'experience_id', $eid);

    // Timeline steps
    $stmt = $db->prepare("SELECT time_slot AS time, title, description FROM experience_timeline
                          WHERE experience_id = ? ORDER BY sort_order ASC");
    $stmt->execute([$eid]);
    $row['timeline_steps'] = $stmt->fetchAll();

    // Coordinates
    $row['coordinates'] = buildCoordinates($row);
    unset($row['lat'], $row['lng']);

    // Borough name
    $row['borough_name'] = getBoroughName($db, $row['borough_id'] ?? null);

    // Numeric casts
    foreach (['duration_minutes', 'max_participants', 'min_participants', 'reviews_count'] as $f) {
        if (isset($row[$f])) $row[$f] = (int)$row[$f];
    }
    $row['price_per_person'] = isset($row['price_per_person']) ? (float)$row['price_per_person'] : null;
    $row['rating']           = isset($row['rating'])           ? (float)$row['rating']           : 0.0;

    // Boolean casts
    $row['is_active'] = (bool)($row['is_active'] ?? false);

    return $row;
}

if ($method === 'GET') {
    if ($id || $slug) {
        if ($slug) {
            $stmt = $db->prepare("SELECT * FROM experiences WHERE slug = ?");
            $stmt->execute([$slug]);
        } else {
            $stmt = $db->prepare("SELECT * FROM experiences WHERE id = ?");
            $stmt->execute([$id]);
        }
        $row = $stmt->fetch();
        if (!$row) { http_response_code(404); echo json_encode(['error' => 'Not found']); exit; }
        echo json_encode(buildExperience($db, $row));
    } else {
        $category = $_GET['category'] ?? null;
        $borough  = $_GET['borough']  ?? null;
        if ($category && $borough) {
            $stmt = $db->prepare("SELECT * FROM experiences WHERE category = ? AND borough_id = ? AND is_active = 1 ORDER BY title");
            $stmt->execute([$category, $borough]);
        } elseif ($category) {
            $stmt = $db->prepare("SELECT * FROM experiences WHERE category = ? AND is_active = 1 ORDER BY title");
            $stmt->execute([$category]);
        } elseif ($borough) {
            $stmt = $db->prepare("SELECT * FROM experiences WHERE borough_id = ? AND is_active = 1 ORDER BY title");
            $stmt->execute([$borough]);
        } else {
            $stmt = $db->query("SELECT * FROM experiences ORDER BY title ASC");
        }
        echo json_encode(array_map(fn($r) => buildExperience($db, $r), $stmt->fetchAll()));
    }
    exit;
}

requireWriteAccess();
$body = getJsonBody();

if ($method === 'POST') {
    $db->prepare("INSERT INTO experiences
        (id, slug, title, tagline, description_short, description_long, category,
         provider_id, borough_id, lat, lng, duration_minutes, max_participants,
         min_participants, price_per_person, cancellation_policy, difficulty_level,
         accessibility_info, rating, reviews_count, is_active, cover_image)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
    ->execute(_expValues($body));
    _saveExpArrays($db, $body);
    http_response_code(201);
    echo json_encode(['ok' => true, 'id' => $body['id']]);
    exit;
}

if ($method === 'PUT' && $id) {
    $db->prepare("UPDATE experiences SET
        slug=?, title=?, tagline=?, description_short=?, description_long=?, category=?,
        provider_id=?, borough_id=?, lat=?, lng=?, duration_minutes=?, max_participants=?,
        min_participants=?, price_per_person=?, cancellation_policy=?, difficulty_level=?,
        accessibility_info=?, rating=?, reviews_count=?, is_active=?, cover_image=? WHERE id=?")
    ->execute(array_merge(array_slice(_expValues($body), 1), [$id]));
    _saveExpArrays($db, array_merge($body, ['id' => $id]));
    echo json_encode(['ok' => true]);
    exit;
}

if ($method === 'DELETE' && $id) {
    foreach (['experience_languages','experience_includes','experience_excludes',
              'experience_bring','experience_seasonal_tags','experience_timeline'] as $t) {
        $db->prepare("DELETE FROM `$t` WHERE experience_id = ?")->execute([$id]);
    }
    $db->prepare("DELETE FROM experiences WHERE id = ?")->execute([$id]);
    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);

function _expValues(array $b): array {
    return [
        $b['id'], $b['slug'], $b['title'],
        $b['tagline'] ?? null, $b['description_short'] ?? null, $b['description_long'] ?? null,
        $b['category'] ?? 'CULTURA',
        $b['provider_id'] ?? null, $b['borough_id'] ?? null,
        $b['coordinates']['lat'] ?? null, $b['coordinates']['lng'] ?? null,
        $b['duration_minutes'] ?? null, $b['max_participants'] ?? null,
        $b['min_participants'] ?? null, $b['price_per_person'] ?? null,
        $b['cancellation_policy'] ?? null, $b['difficulty_level'] ?? 'FACILE',
        $b['accessibility_info'] ?? null,
        $b['rating'] ?? 0, $b['reviews_count'] ?? 0,
        $b['is_active'] ?? 1,
        $b['cover_image'] ?? null,
    ];
}

function _saveExpArrays(PDO $db, array $body): void {
    $eid = $body['id'];
    // languages
    $db->prepare("DELETE FROM experience_languages WHERE experience_id = ?")->execute([$eid]);
    $stmt = $db->prepare("INSERT INTO experience_languages (experience_id, lang) VALUES (?,?)");
    foreach ($body['languages_available'] ?? [] as $lang) { $stmt->execute([$eid, $lang]); }

    replaceArray($db, 'experience_includes',      'experience_id', $eid, $body['includes']      ?? []);
    replaceArray($db, 'experience_excludes',      'experience_id', $eid, $body['excludes']      ?? []);
    replaceArray($db, 'experience_bring',         'experience_id', $eid, $body['what_to_bring'] ?? []);
    replaceArray($db, 'experience_seasonal_tags', 'experience_id', $eid, $body['seasonal_tags'] ?? []);

    // timeline
    $db->prepare("DELETE FROM experience_timeline WHERE experience_id = ?")->execute([$eid]);
    $stmt = $db->prepare("INSERT INTO experience_timeline (experience_id, time_slot, title, description, icon, sort_order) VALUES (?,?,?,?,?,?)");
    foreach ($body['timeline_steps'] ?? [] as $i => $step) {
        $stmt->execute([$eid, $step['time'] ?? null, $step['title'] ?? null, $step['description'] ?? null, $step['icon'] ?? null, $i]);
    }
}
