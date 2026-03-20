<?php
// ============================================================
// MetaBorghi — Configurazione Database
// Hostinger MySQL Remote
// ============================================================

define('DB_HOST',     'localhost');      // Su Hostinger: generalmente localhost
define('DB_NAME',     'u468374447_metaborghi');     // Nome database su phpMyAdmin
define('DB_USER',     'u468374447_admin');        // Utente MySQL Hostinger
define('DB_PASS', '8TTusangol!');
define('DB_CHARSET',  'utf8mb4');

// Token API per autenticazione endpoint di scrittura e export
define('API_TOKEN',   'kshdfertwyuejmfhdgetw285&%$£9WED');

// Credenziali admin panel
define('ADMIN_USER',  'admin');
define('ADMIN_PASS',  '8TTusangol!');

// Percorso assoluto della cartella assets/ della SPA
// Su Hostinger: /home/u123456789/public_html/assets/
define('ASSETS_PATH', '/home/u468374447/domains/metaborghi.org/public_html/assets/');

// ============================================================
// Connessione PDO — usata da tutti i file API
// ============================================================
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            $msg = htmlspecialchars($e->getMessage());
            // In admin panel context (session active) show an HTML error, not raw JSON
            if (session_status() === PHP_SESSION_ACTIVE) {
                die('<!DOCTYPE html><html><head><meta charset="UTF-8">
<script src="https://cdn.tailwindcss.com"></script>
<style>body{background:#0f172a}</style></head>
<body class="min-h-screen flex items-center justify-center p-8">
<div class="max-w-lg w-full bg-red-900/40 border border-red-600 rounded-2xl p-8 text-red-200 font-mono text-sm">
<p class="text-xl font-bold text-red-400 mb-4">❌ Errore connessione database</p>
<p class="mb-4">' . $msg . '</p>
<p class="text-red-300 text-xs">Verifica le credenziali in <strong>api/config/db.php</strong>:<br>
DB_HOST, DB_NAME, DB_USER, DB_PASS devono corrispondere ai valori Hostinger.</p>
<a href="/api/admin/login.php" class="mt-6 inline-block text-red-400 hover:text-white underline">← Torna al login</a>
</div></body></html>');
            }
            die(json_encode(['error' => 'Database connection failed']));
        }
    }
    return $pdo;
}

// ============================================================
// Helpers
// ============================================================

// Verifica Bearer token per endpoint di scrittura
function requireAuth(): void {
    $headers = getallheaders();
    $auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    if ($auth !== 'Bearer ' . API_TOKEN) {
        http_response_code(401);
        die(json_encode(['error' => 'Unauthorized']));
    }
}

// Verifica sessione admin panel
function requireAdminSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['admin_logged_in'])) {
        header('Location: /api/admin/login.php');
        exit;
    }
}

// Header JSON + CORS
function jsonHeaders(): void {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

// Legge body JSON della request
function getJsonBody(): array {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?? [];
}

// Recupera array 1-to-many per un'entità
function fetchArray(PDO $db, string $table, string $fk, string $id, string $col = 'value'): array {
    $stmt = $db->prepare("SELECT `$col` FROM `$table` WHERE `$fk` = ? ORDER BY sort_order ASC");
    $stmt->execute([$id]);
    return array_column($stmt->fetchAll(), $col);
}

// Upload immagine di copertina — restituisce il path relativo o null
function handleCoverUpload(string $inputName, string $entityType, string $entityId): ?string {
    if (!isset($_FILES[$inputName]) || empty($_FILES[$inputName]['tmp_name']) || $_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    // Rileva MIME con fallback per hosting senza finfo
    $allowedMime = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    $allowedExt  = ['jpg' => 'jpg', 'jpeg' => 'jpg', 'png' => 'png', 'gif' => 'gif', 'webp' => 'webp'];

    $mime = null;
    if (function_exists('mime_content_type')) {
        $mime = @mime_content_type($_FILES[$inputName]['tmp_name']);
    }
    if (!$mime && class_exists('finfo')) {
        $fi   = new finfo(FILEINFO_MIME_TYPE);
        $mime = $fi->file($_FILES[$inputName]['tmp_name']);
    }

    $ext = null;
    if ($mime && isset($allowedMime[$mime])) {
        $ext = $allowedMime[$mime];
    } else {
        // Fallback: usa l'estensione del file originale
        $origExt = strtolower(pathinfo($_FILES[$inputName]['name'], PATHINFO_EXTENSION));
        if (isset($allowedExt[$origExt])) {
            $ext = $allowedExt[$origExt];
        }
    }
    if (!$ext) return null;

    $safeId   = preg_replace('/[^a-z0-9_-]/', '', strtolower($entityId));
    $filename = $entityType . '_' . $safeId . '_' . time() . '.' . $ext;
    $destDir  = __DIR__ . '/../uploads/';
    if (!is_dir($destDir)) @mkdir($destDir, 0755, true);
    $dest = $destDir . $filename;

    if (@move_uploaded_file($_FILES[$inputName]['tmp_name'], $dest)) {
        return '/api/uploads/' . $filename;
    }
    return null;
}

// Assicura che la colonna cover_image esista in una tabella (auto-migration)
function ensureCoverImageColumn(PDO $db, string $table): void {
    static $checked = [];
    if (isset($checked[$table])) return;
    $checked[$table] = true;
    try {
        $db->query("SELECT `cover_image` FROM `$table` LIMIT 0");
    } catch (PDOException $e) {
        $db->exec("ALTER TABLE `$table` ADD COLUMN `cover_image` VARCHAR(500) DEFAULT NULL");
    }
}

// Sostituisce array 1-to-many
function replaceArray(PDO $db, string $table, string $fk, string $id, array $values, string $col = 'value'): void {
    $db->prepare("DELETE FROM `$table` WHERE `$fk` = ?")->execute([$id]);
    $stmt = $db->prepare("INSERT INTO `$table` (`$fk`, `$col`, sort_order) VALUES (?, ?, ?)");
    foreach ($values as $i => $v) {
        $stmt->execute([$id, $v, $i]);
    }
}
