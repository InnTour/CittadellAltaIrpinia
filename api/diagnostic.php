<?php
// ============================================================
// MetaBorghi — Diagnostica connessione DB
// ELIMINARE dopo l'uso (espone informazioni sensibili)
// ============================================================
header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/config/db.php';

echo '<!DOCTYPE html><html><head><meta charset="UTF-8">
<style>
  body{font-family:monospace;background:#0f172a;color:#e2e8f0;padding:2rem}
  .ok{color:#4ade80} .err{color:#f87171} .info{color:#94a3b8}
  pre{background:#1e293b;padding:1rem;border-radius:.5rem;font-size:.85rem}
</style></head><body>';

echo '<h2>🔍 MetaBorghi — Diagnostica DB</h2>';

// 1. Costanti caricate
echo '<h3>1. Costanti db.php</h3><pre>';
echo 'DB_HOST   = ' . DB_HOST   . "\n";
echo 'DB_NAME   = ' . DB_NAME   . "\n";
echo 'DB_USER   = ' . DB_USER   . "\n";
echo 'DB_PASS   = ' . str_repeat('*', strlen(DB_PASS)) . ' (' . strlen(DB_PASS) . ' caratteri)' . "\n";
echo 'ASSETS_PATH = ' . ASSETS_PATH . "\n";
echo '</pre>';

// 2. Test connessione PDO
echo '<h3>2. Test connessione PDO</h3>';
$hosts = [DB_HOST, '127.0.0.1', 'localhost'];
foreach (array_unique($hosts) as $h) {
    $dsn = 'mysql:host=' . $h . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        echo '<p class="ok">✅ Connessione OK con host <strong>' . htmlspecialchars($h) . '</strong></p>';
        // Test tabelle
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        echo '<pre class="ok">Tabelle trovate (' . count($tables) . '):<br>' . implode('<br>', $tables) . '</pre>';
    } catch (PDOException $e) {
        echo '<p class="err">❌ Host <strong>' . htmlspecialchars($h) . '</strong>: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
}

// 3. ASSETS_PATH
echo '<h3>3. Verifica ASSETS_PATH</h3>';
if (is_dir(ASSETS_PATH)) {
    $files = glob(ASSETS_PATH . '*.js');
    echo '<p class="ok">✅ Directory esistente — file JS trovati: ' . count($files) . '</p>';
} else {
    echo '<p class="err">❌ Directory non trovata: <code>' . htmlspecialchars(ASSETS_PATH) . '</code></p>';
    echo '<p class="info">__DIR__ = ' . __DIR__ . '</p>';
}

// 4. PHP info rilevante
echo '<h3>4. PHP info</h3><pre>';
echo 'PHP version: ' . PHP_VERSION . "\n";
echo 'PDO drivers: ' . implode(', ', PDO::getAvailableDrivers()) . "\n";
echo 'session.save_path: ' . ini_get('session.save_path') . "\n";
echo '</pre>';

echo '<hr><p class="err"><strong>⚠️ Eliminare questo file dopo la diagnostica!</strong></p>';
echo '</body></html>';
