<?php
/**
 * MetaBorghi — Static Generator
 * Rigenera i 4 file JS della SPA a partire dal database MySQL.
 *
 * Chiamata: GET /api/export/generate.php?token=API_TOKEN[&target=boroughs|companies|experiences|crafts]
 * Senza &target rigenera tutti e 4 i file.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/_generate_functions.php';
jsonHeaders();

// Autenticazione via query string (comoda per il pulsante admin)
$token = $_GET['token'] ?? $_SERVER['HTTP_X_API_TOKEN'] ?? '';
if ($token !== API_TOKEN) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}

$target   = $_GET['target'] ?? 'all';
$db       = getDB();
$results  = [];

if ($target === 'all' || $target === 'boroughs') {
    $results['boroughs'] = generateBoroughs($db);
}
if ($target === 'all' || $target === 'companies') {
    $results['companies'] = generateCompanies($db);
}
if ($target === 'all' || $target === 'experiences') {
    $results['experiences'] = generateExperiences($db);
}
if ($target === 'all' || $target === 'crafts') {
    $results['crafts'] = generateCrafts($db);
}
if ($target === 'all' || $target === 'food_products') {
    $results['food_products'] = generateFoodProducts($db);
}
if ($target === 'all' || $target === 'accommodations') {
    $results['accommodations'] = generateAccommodations($db);
}
if ($target === 'all' || $target === 'restaurants') {
    $results['restaurants'] = generateRestaurants($db);
}

echo json_encode(['ok' => true, 'generated' => $results]);

