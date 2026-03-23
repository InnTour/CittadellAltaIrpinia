<?php
/**
 * MetaBorghi — Wishlist API
 * GET    /api/v1/wishlist.php                        — Lista wishlist utente
 * POST   /api/v1/wishlist.php                        — Aggiungi a wishlist
 * DELETE /api/v1/wishlist.php?item_type=X&item_id=Y  — Rimuovi da wishlist
 *
 * Richiede autenticazione JWT (ruolo minimo: registrato)
 */
require_once __DIR__ . '/../config/db.php';
jsonHeaders();

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// Assicura tabella wishlist
$db->exec("CREATE TABLE IF NOT EXISTS `user_wishlist` (
  `id`        INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`   VARCHAR(100) NOT NULL,
  `item_type` ENUM('borough','experience','craft','food_product','accommodation','restaurant','company') NOT NULL,
  `item_id`   VARCHAR(100) NOT NULL,
  `added_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `user_id` (`user_id`),
  KEY `item_type_id` (`item_type`, `item_id`),
  UNIQUE KEY `uq_wish` (`user_id`, `item_type`, `item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$user = requireRole('registrato', 'operatore', 'admin');
$userId = $user['sub'];

// ── GET: lista wishlist ───────────────────────────────────────────
if ($method === 'GET') {
    $type = $_GET['item_type'] ?? null;

    $sql = "SELECT item_type, item_id, added_at FROM user_wishlist WHERE user_id = ?";
    $params = [$userId];
    if ($type) {
        $sql .= " AND item_type = ?";
        $params[] = $type;
    }
    $sql .= " ORDER BY added_at DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll();

    // Arricchisci con nome/titolo dell'entità
    foreach ($items as &$item) {
        $item['entity'] = _fetchEntitySummary($db, $item['item_type'], $item['item_id']);
    }
    unset($item);

    echo json_encode(['ok' => true, 'items' => $items, 'count' => count($items)]);
    exit;
}

// ── POST: aggiungi a wishlist ─────────────────────────────────────
if ($method === 'POST') {
    $body     = getJsonBody();
    $itemType = $body['item_type'] ?? '';
    $itemId   = $body['item_id'] ?? '';

    $allowed = ['borough','experience','craft','food_product','accommodation','restaurant','company'];
    if (!in_array($itemType, $allowed, true) || !$itemId) {
        http_response_code(400);
        echo json_encode(['error' => 'item_type e item_id sono obbligatori']);
        exit;
    }

    // Upsert: ignora se già presente
    $db->prepare("INSERT IGNORE INTO user_wishlist (user_id, item_type, item_id) VALUES (?, ?, ?)")
       ->execute([$userId, $itemType, $itemId]);

    echo json_encode(['ok' => true, 'message' => 'Aggiunto alla wishlist']);
    exit;
}

// ── DELETE: rimuovi da wishlist ────────────────────────────────────
if ($method === 'DELETE') {
    $itemType = $_GET['item_type'] ?? '';
    $itemId   = $_GET['item_id'] ?? '';

    if (!$itemType || !$itemId) {
        // Prova anche dal body JSON
        $body = getJsonBody();
        $itemType = $body['item_type'] ?? $itemType;
        $itemId   = $body['item_id'] ?? $itemId;
    }

    if (!$itemType || !$itemId) {
        http_response_code(400);
        echo json_encode(['error' => 'item_type e item_id sono obbligatori']);
        exit;
    }

    $db->prepare("DELETE FROM user_wishlist WHERE user_id = ? AND item_type = ? AND item_id = ?")
       ->execute([$userId, $itemType, $itemId]);

    echo json_encode(['ok' => true, 'message' => 'Rimosso dalla wishlist']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);

// ── Helper: recupera info minime dell'entità ──────────────────────
function _fetchEntitySummary(PDO $db, string $type, string $id): ?array {
    $tableMap = [
        'borough'       => ['boroughs',       'name',  'slug'],
        'experience'    => ['experiences',     'title', 'slug'],
        'craft'         => ['craft_products',  'name',  'slug'],
        'food_product'  => ['food_products',   'name',  'slug'],
        'accommodation' => ['accommodations',  'name',  'slug'],
        'restaurant'    => ['restaurants',     'name',  'slug'],
        'company'       => ['companies',       'name',  'slug'],
    ];
    if (!isset($tableMap[$type])) return null;
    [$table, $nameCol, $slugCol] = $tableMap[$type];
    $stmt = $db->prepare("SELECT `$nameCol` AS name, `$slugCol` AS slug, cover_image FROM `$table` WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}
