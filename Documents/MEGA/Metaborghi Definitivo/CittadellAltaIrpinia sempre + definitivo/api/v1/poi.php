<?php
require_once __DIR__ . '/../config/db.php';
jsonHeaders();

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = $_GET['id'] ?? null;
$borough = $_GET['borough'] ?? null;

if ($method === 'GET') {
    if ($id) {
        $stmt = $db->prepare(
            "SELECT p.*, b.name as borough_name
             FROM points_of_interest p
             LEFT JOIN boroughs b ON b.id = p.borough_id
             WHERE p.id = ?"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            http_response_code(404);
            echo json_encode(['error' => 'Not found']);
            exit;
        }
        echo json_encode(buildPoi($db, $row));
    } else {
        if ($borough) {
            $stmt = $db->prepare(
                "SELECT p.*, b.name as borough_name
                 FROM points_of_interest p
                 LEFT JOIN boroughs b ON b.id = p.borough_id
                 WHERE p.borough_id = ?
                 ORDER BY p.sort_order ASC, p.name_it ASC"
            );
            $stmt->execute([$borough]);
        } else {
            $stmt = $db->query(
                "SELECT p.*, b.name as borough_name
                 FROM points_of_interest p
                 LEFT JOIN boroughs b ON b.id = p.borough_id
                 ORDER BY p.borough_id, p.sort_order ASC"
            );
        }
        echo json_encode(array_map(fn($r) => buildPoi($db, $r), $stmt->fetchAll()));
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);

function buildPoi(PDO $db, array $row): array {
    $images = [];
    try {
        $raw = $row['images'] ?? null;
        if ($raw) {
            $decoded = json_decode($raw, true);
            $images = is_array($decoded) ? $decoded : [];
        }
    } catch (Exception $e) { $images = []; }

    return [
        'id'           => $row['id'],
        'borough_id'   => $row['borough_id'],
        'borough_name' => $row['borough_name'] ?? null,
        'category'     => $row['category'] ?? null,
        'sort_order'   => (int)($row['sort_order'] ?? 0),
        'name_it'      => $row['name_it'] ?? '',
        'name_en'      => $row['name_en'] ?? null,
        'name_irp'     => $row['name_irp'] ?? null,
        'desc_it'      => $row['desc_it'] ?? null,
        'desc_en'      => $row['desc_en'] ?? null,
        'desc_irp'     => $row['desc_irp'] ?? null,
        'tags'         => $row['tags'] ? array_filter(array_map('trim', explode(',', $row['tags']))) : [],
        'cover_image'  => $row['cover_image'] ?? null,
        'images'       => $images,
        'has_audio'    => !empty($row['audio_it']) || !empty($row['audio_en']) || !empty($row['audio_irp']),
        'has_video'    => !empty($row['video_it']) || !empty($row['video_en']),
        'url'          => "/borghi/{$row['borough_id']}/{$row['id']}",
    ];
}
