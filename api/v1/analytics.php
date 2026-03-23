<?php
/**
 * MetaBorghi — Analytics API
 * POST /api/v1/analytics.php  — traccia una visualizzazione (pubblico)
 * GET  /api/v1/analytics.php  — statistiche aggregate (richiede auth)
 */
require_once __DIR__ . '/../config/db.php';
jsonHeaders();

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// ── POST: traccia una page view (pubblico, no auth) ────────────
if ($method === 'POST') {
    $body = getJsonBody();
    $entityType = $body['entity_type'] ?? '';
    $entityId   = $body['entity_id']   ?? '';

    if (!$entityType || !$entityId) {
        http_response_code(400);
        echo json_encode(['error' => 'entity_type and entity_id required']);
        exit;
    }

    $allowed = ['borough','company','experience','craft','food','accommodation','restaurant'];
    if (!in_array($entityType, $allowed)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid entity_type']);
        exit;
    }

    // Hash dell'IP per privacy
    $ip     = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $ipHash = hash('sha256', $ip . date('Y-m-d'));

    $sessionId = $body['session_id'] ?? null;
    $pageUrl   = $body['page_url']   ?? null;
    $referrer  = $body['referrer']   ?? null;
    $ua        = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $db->prepare("INSERT INTO page_views (entity_type, entity_id, page_url, referrer, user_agent, ip_hash, session_id)
                  VALUES (?, ?, ?, ?, ?, ?, ?)")
       ->execute([$entityType, $entityId, $pageUrl, $referrer, $ua, $ipHash, $sessionId]);

    // Aggiorna daily_stats con upsert
    $today = date('Y-m-d');
    $db->prepare("INSERT INTO daily_stats (stat_date, entity_type, entity_id, views_count, unique_views)
                  VALUES (?, ?, ?, 1, 1)
                  ON DUPLICATE KEY UPDATE views_count = views_count + 1,
                  unique_views = (SELECT COUNT(DISTINCT ip_hash) FROM page_views
                                  WHERE entity_type = ? AND entity_id = ? AND DATE(viewed_at) = ?)")
       ->execute([$today, $entityType, $entityId, $entityType, $entityId, $today]);

    echo json_encode(['ok' => true]);
    exit;
}

// ── GET: statistiche aggregate (richiede auth) ─────────────────
if ($method === 'GET') {
    requireAuth();

    $period = $_GET['period'] ?? '30'; // giorni
    $type   = $_GET['type']   ?? null;

    $days = max(1, min(365, (int)$period));
    $dateFrom = date('Y-m-d', strtotime("-{$days} days"));

    // Totali per tipo di entità
    $sql = "SELECT entity_type, SUM(views_count) as total_views, SUM(unique_views) as total_unique
            FROM daily_stats WHERE stat_date >= ?";
    $params = [$dateFrom];
    if ($type) {
        $sql .= " AND entity_type = ?";
        $params[] = $type;
    }
    $sql .= " GROUP BY entity_type ORDER BY total_views DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $byType = $stmt->fetchAll();

    // Top 10 entità più viste
    $sql2 = "SELECT entity_type, entity_id, SUM(views_count) as total_views, SUM(unique_views) as total_unique
             FROM daily_stats WHERE stat_date >= ?";
    $params2 = [$dateFrom];
    if ($type) {
        $sql2 .= " AND entity_type = ?";
        $params2[] = $type;
    }
    $sql2 .= " GROUP BY entity_type, entity_id ORDER BY total_views DESC LIMIT 10";
    $stmt2 = $db->prepare($sql2);
    $stmt2->execute($params2);
    $topEntities = $stmt2->fetchAll();

    // Trend giornaliero
    $sql3 = "SELECT stat_date, SUM(views_count) as views, SUM(unique_views) as uniques
             FROM daily_stats WHERE stat_date >= ?";
    $params3 = [$dateFrom];
    if ($type) {
        $sql3 .= " AND entity_type = ?";
        $params3[] = $type;
    }
    $sql3 .= " GROUP BY stat_date ORDER BY stat_date ASC";
    $stmt3 = $db->prepare($sql3);
    $stmt3->execute($params3);
    $dailyTrend = $stmt3->fetchAll();

    // Totale complessivo
    $totalViews  = array_sum(array_column($byType, 'total_views'));
    $totalUnique = array_sum(array_column($byType, 'total_unique'));

    echo json_encode([
        'period_days'  => $days,
        'date_from'    => $dateFrom,
        'total_views'  => (int)$totalViews,
        'total_unique' => (int)$totalUnique,
        'by_type'      => $byType,
        'top_entities' => $topEntities,
        'daily_trend'  => $dailyTrend,
    ]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
