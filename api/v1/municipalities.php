<?php
require_once __DIR__ . '/../config/db.php';
jsonHeaders();

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = $_GET['id'] ?? null;

function buildMunicipality(array $row): array {
    if (isset($row['population']))  $row['population']  = (int)$row['population'];
    if (isset($row['annual_fee']))  $row['annual_fee']  = (float)$row['annual_fee'];
    $row['pnrr_funded'] = (bool)($row['pnrr_funded'] ?? false);
    return $row;
}

if ($method === 'GET') {
    if ($id) {
        $stmt = $db->prepare("SELECT * FROM b2g_municipalities WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) { http_response_code(404); echo json_encode(['error' => 'Not found']); exit; }
        echo json_encode(buildMunicipality($row));
    } else {
        $status = $_GET['status'] ?? null;
        if ($status) {
            $stmt = $db->prepare("SELECT * FROM b2g_municipalities WHERE subscription_status = ? ORDER BY municipality_name");
            $stmt->execute([$status]);
        } else {
            $stmt = $db->query("SELECT * FROM b2g_municipalities ORDER BY municipality_name ASC");
        }
        echo json_encode(array_map('buildMunicipality', $stmt->fetchAll()));
    }
    exit;
}

requireAuth();
$body = getJsonBody();

if ($method === 'POST') {
    $db->prepare("INSERT INTO b2g_municipalities
        (id, borough_id, municipality_name, province, region,
         mayor_name, mayor_email, contact_person, contact_email, contact_phone,
         pec_email, website_url, population, tier, subscription_status,
         subscription_start, subscription_end, annual_fee,
         pnrr_funded, pnrr_measure, notes, services_enabled, cover_image)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
    ->execute(_munValues($body));
    http_response_code(201);
    echo json_encode(['ok' => true, 'id' => $body['id']]);
    exit;
}

if ($method === 'PUT' && $id) {
    $db->prepare("UPDATE b2g_municipalities SET
        borough_id=?, municipality_name=?, province=?, region=?,
        mayor_name=?, mayor_email=?, contact_person=?, contact_email=?, contact_phone=?,
        pec_email=?, website_url=?, population=?, tier=?, subscription_status=?,
        subscription_start=?, subscription_end=?, annual_fee=?,
        pnrr_funded=?, pnrr_measure=?, notes=?, services_enabled=?, cover_image=? WHERE id=?")
    ->execute(array_merge(array_slice(_munValues($body), 1), [$id]));
    echo json_encode(['ok' => true]);
    exit;
}

if ($method === 'DELETE' && $id) {
    $db->prepare("DELETE FROM b2g_municipalities WHERE id = ?")->execute([$id]);
    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);

function _munValues(array $b): array {
    return [
        $b['id'],
        $b['borough_id'] ?? null,
        $b['municipality_name'],
        $b['province'] ?? null,
        $b['region'] ?? 'Campania',
        $b['mayor_name'] ?? null,
        $b['mayor_email'] ?? null,
        $b['contact_person'] ?? null,
        $b['contact_email'] ?? null,
        $b['contact_phone'] ?? null,
        $b['pec_email'] ?? null,
        $b['website_url'] ?? null,
        $b['population'] ?? null,
        $b['tier'] ?? 'BASE',
        $b['subscription_status'] ?? 'LEAD',
        $b['subscription_start'] ?? null,
        $b['subscription_end'] ?? null,
        $b['annual_fee'] ?? null,
        ($b['pnrr_funded'] ?? false) ? 1 : 0,
        $b['pnrr_measure'] ?? null,
        $b['notes'] ?? null,
        $b['services_enabled'] ?? null,
        $b['cover_image'] ?? null,
    ];
}
