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

// JWT Secret — used for user authentication tokens
define('JWT_SECRET',  'mb_jwt_s3cr3t_k3y_2024!@#InnTour');
define('JWT_EXPIRY',  86400 * 7); // 7 giorni

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

// ============================================================
// JWT — Autenticazione utenti frontend
// ============================================================

function jwtEncode(array $payload): string {
    $header = base64url_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload['iat'] = $payload['iat'] ?? time();
    $payload['exp'] = $payload['exp'] ?? time() + JWT_EXPIRY;
    $payloadB64 = base64url_encode(json_encode($payload));
    $sig = base64url_encode(hash_hmac('sha256', "$header.$payloadB64", JWT_SECRET, true));
    return "$header.$payloadB64.$sig";
}

function jwtDecode(string $token): ?array {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;
    [$header, $payload, $sig] = $parts;
    $expectedSig = base64url_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
    if (!hash_equals($expectedSig, $sig)) return null;
    $data = json_decode(base64url_decode($payload), true);
    if (!$data || !isset($data['exp']) || $data['exp'] < time()) return null;
    return $data;
}

function base64url_encode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode(string $data): string {
    return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 3 - (3 + strlen($data)) % 4));
}

// Estrae il JWT dal header Authorization (se presente)
// Ritorna i dati utente o null
function getAuthUser(): ?array {
    $headers = getallheaders();
    $auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    if (!str_starts_with($auth, 'Bearer ')) return null;
    $token = substr($auth, 7);
    // Skip if it's the static API token
    if ($token === API_TOKEN) return null;
    return jwtDecode($token);
}

// Richiede un utente autenticato via JWT, ritorna i dati dal token
function requireJwtAuth(): array {
    $user = getAuthUser();
    if (!$user) {
        http_response_code(401);
        die(json_encode(['error' => 'Autenticazione richiesta']));
    }
    return $user;
}

// Richiede un ruolo specifico (o superiore)
// Gerarchia: admin > operatore > registrato > visitatore
function requireRole(string ...$allowedRoles): array {
    $user = requireJwtAuth();
    $role = $user['role'] ?? 'visitatore';
    // Admin ha sempre accesso
    if ($role === 'admin') return $user;
    if (!in_array($role, $allowedRoles, true)) {
        http_response_code(403);
        die(json_encode(['error' => 'Permessi insufficienti', 'required' => $allowedRoles]));
    }
    return $user;
}

// Verifica che l'utente sia admin O abbia il vecchio Bearer token statico
// Usato per mantenere retrocompatibilità con le API v1 esistenti
function requireWriteAccess(): array|bool {
    $headers = getallheaders();
    $auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    // Legacy: static API token
    if ($auth === 'Bearer ' . API_TOKEN) return true;
    // New: JWT with admin or operatore role
    return requireRole('admin', 'operatore');
}

// Verifica che un operatore possa modificare una specifica entità
function requireEntityAccess(string $entityType, ?string $boroughId = null, ?string $companyId = null): array {
    $user = requireWriteAccess();
    // Static API token or admin → full access
    if ($user === true) return ['role' => 'admin'];
    if (($user['role'] ?? '') === 'admin') return $user;
    // Operatore: verifica assegnazione
    $uid = $user['sub'] ?? '';
    $db = getDB();
    if ($boroughId) {
        $stmt = $db->prepare("SELECT id FROM user_borough_assignments WHERE user_id = ? AND borough_id = ?");
        $stmt->execute([$uid, $boroughId]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            die(json_encode(['error' => 'Non hai accesso a questo borgo']));
        }
    }
    if ($companyId) {
        $stmt = $db->prepare("SELECT id FROM user_company_assignments WHERE user_id = ? AND company_id = ?");
        $stmt->execute([$uid, $companyId]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            die(json_encode(['error' => 'Non hai accesso a questa azienda']));
        }
    }
    return $user;
}

// Assicura che la tabella admin_users esista
function ensureAdminUsersTable(PDO $db): void {
    static $done = false;
    if ($done) return;
    $done = true;
    $db->exec("CREATE TABLE IF NOT EXISTS `admin_users` (
      `id`            VARCHAR(40)   NOT NULL,
      `name`          VARCHAR(200)  NOT NULL,
      `email`         VARCHAR(200)  NOT NULL,
      `password_hash` VARCHAR(255)  NOT NULL DEFAULT '',
      `role`          ENUM('visitatore','registrato','operatore','admin') NOT NULL DEFAULT 'registrato',
      `borough_id`    VARCHAR(100)  DEFAULT NULL,
      `company_id`    VARCHAR(100)  DEFAULT NULL,
      `phone`         VARCHAR(50)   DEFAULT NULL,
      `bio`           TEXT          DEFAULT NULL,
      `avatar_url`    TEXT          DEFAULT NULL,
      `is_active`     TINYINT(1)    NOT NULL DEFAULT 1,
      `email_verified` TINYINT(1)   NOT NULL DEFAULT 0,
      `last_login_at` TIMESTAMP     NULL DEFAULT NULL,
      `created_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `email` (`email`),
      KEY `role` (`role`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
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

// Ritorna il ruolo della sessione admin corrente
function getAdminSessionRole(): string {
    return $_SESSION['admin_role'] ?? 'admin';
}

// Ritorna le info dell'utente della sessione admin corrente
function getAdminSessionUser(): array {
    return [
        'id'   => $_SESSION['admin_user_id'] ?? null,
        'name' => $_SESSION['admin_user_name'] ?? ADMIN_USER,
        'role' => $_SESSION['admin_role'] ?? 'admin',
    ];
}

// Verifica che la sessione admin abbia il ruolo richiesto
function requireAdminRole(string ...$roles): void {
    $role = getAdminSessionRole();
    if ($role === 'admin') return; // admin ha sempre accesso
    if (!in_array($role, $roles, true)) {
        http_response_code(403);
        die('<!DOCTYPE html><html><head><meta charset="UTF-8">
<script src="https://cdn.tailwindcss.com"></script><style>body{background:#0f172a}</style></head>
<body class="min-h-screen flex items-center justify-center p-8">
<div class="max-w-lg w-full bg-red-900/40 border border-red-600 rounded-2xl p-8 text-red-200 font-mono text-sm">
<p class="text-xl font-bold text-red-400 mb-4">Accesso negato</p>
<p class="mb-4">Il tuo ruolo (' . htmlspecialchars($role) . ') non ha i permessi per questa sezione.</p>
<a href="/api/admin/" class="mt-6 inline-block text-red-400 hover:text-white underline">← Torna alla dashboard</a>
</div></body></html>');
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
    if (!is_dir($destDir)) {
        if (!mkdir($destDir, 0755, true) && !is_dir($destDir)) {
            error_log("handleCoverUpload: impossibile creare $destDir");
            return null;
        }
    }
    $dest = $destDir . $filename;

    if (move_uploaded_file($_FILES[$inputName]['tmp_name'], $dest)) {
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

// Assicura che le colonne specificate esistano nella tabella
// $columns = ['col_name' => 'VARCHAR(200) DEFAULT NULL', ...]
function ensureTableColumns(PDO $db, string $table, array $columns): void {
    static $checked = [];
    $key = $table;
    if (isset($checked[$key])) return;
    $checked[$key] = true;
    // Prendi lista colonne esistenti
    $existing = [];
    foreach ($db->query("SHOW COLUMNS FROM `$table`") as $row) {
        $existing[] = strtolower($row['Field']);
    }
    foreach ($columns as $col => $def) {
        if (!in_array(strtolower($col), $existing, true)) {
            $db->exec("ALTER TABLE `$table` ADD COLUMN `$col` $def");
        }
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

// ============================================================
// Entity Images — Gestione gallery per ogni entità
// ============================================================

function ensureEntityImagesTable(PDO $db): void {
    static $done = false;
    if ($done) return;
    $done = true;
    $db->exec("CREATE TABLE IF NOT EXISTS `entity_images` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `entity_type` VARCHAR(50) NOT NULL,
        `entity_id` VARCHAR(100) NOT NULL,
        `src` VARCHAR(500) NOT NULL,
        `alt` VARCHAR(500) DEFAULT '',
        `sort_order` INT DEFAULT 0,
        INDEX idx_entity (`entity_type`, `entity_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function fetchEntityImages(PDO $db, string $entityType, string $entityId): array {
    ensureEntityImagesTable($db);
    $stmt = $db->prepare("SELECT src, alt FROM entity_images WHERE entity_type = ? AND entity_id = ? ORDER BY sort_order ASC");
    $stmt->execute([$entityType, $entityId]);
    return $stmt->fetchAll();
}

function saveEntityImages(PDO $db, string $entityType, string $entityId, array $images): void {
    ensureEntityImagesTable($db);
    $db->prepare("DELETE FROM entity_images WHERE entity_type = ? AND entity_id = ?")->execute([$entityType, $entityId]);
    $stmt = $db->prepare("INSERT INTO entity_images (entity_type, entity_id, src, alt, sort_order) VALUES (?, ?, ?, ?, ?)");
    foreach ($images as $i => $img) {
        $src = is_array($img) ? ($img['src'] ?? '') : $img;
        $alt = is_array($img) ? ($img['alt'] ?? '') : '';
        if ($src) $stmt->execute([$entityType, $entityId, $src, $alt, $i]);
    }
}

function handleMultipleImageUpload(string $inputName, string $entityType, string $entityId): array {
    $paths = [];
    if (!isset($_FILES[$inputName])) return $paths;
    $files = $_FILES[$inputName];
    if (!is_array($files['name'])) return $paths;

    $allowedExt = ['jpg' => 'jpg', 'jpeg' => 'jpg', 'png' => 'png', 'gif' => 'gif', 'webp' => 'webp'];
    $destDir = __DIR__ . '/../uploads/';
    if (!is_dir($destDir)) {
        if (!mkdir($destDir, 0755, true) && !is_dir($destDir)) {
            error_log("handleMultipleImageUpload: impossibile creare $destDir");
            return $paths;
        }
    }
    $safeId = preg_replace('/[^a-z0-9_-]/', '', strtolower($entityId));

    for ($i = 0; $i < count($files['name']); $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK || empty($files['tmp_name'][$i])) continue;
        $origExt = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
        if (!isset($allowedExt[$origExt])) continue;
        $ext = $allowedExt[$origExt];
        $filename = $entityType . '_' . $safeId . '_' . time() . '_' . $i . '.' . $ext;
        $dest = $destDir . $filename;
        if (move_uploaded_file($files['tmp_name'][$i], $dest)) {
            $paths[] = '/api/uploads/' . $filename;
        }
    }
    return $paths;
}

// Recupera borough_name da borough_id
function getBoroughName(PDO $db, ?string $boroughId): string {
    if (!$boroughId) return '';
    static $cache = [];
    if (isset($cache[$boroughId])) return $cache[$boroughId];
    $stmt = $db->prepare("SELECT name FROM boroughs WHERE id = ?");
    $stmt->execute([$boroughId]);
    $row = $stmt->fetch();
    $cache[$boroughId] = $row ? $row['name'] : ucwords(str_replace('-', ' ', $boroughId));
    return $cache[$boroughId];
}

// Costruisce oggetto coordinates da lat/lng
function buildCoordinates($row): ?array {
    $lat = (float)($row['lat'] ?? 0);
    $lng = (float)($row['lng'] ?? 0);
    if ($lat == 0 && $lng == 0) return null;
    return ['lat' => $lat, 'lng' => $lng];
}

// Converte stringa separata da virgola/newline in array
function parseTextToArray(string $text, string $separator = "\n"): array {
    return array_values(array_filter(array_map('trim', explode($separator, $text))));
}

// Converte stringa JSON o testo separato in array
function parseJsonOrText(?string $value, string $separator = ','): array {
    if (!$value || trim($value) === '') return [];
    $decoded = json_decode($value, true);
    if (is_array($decoded)) return $decoded;
    return array_values(array_filter(array_map('trim', explode($separator, $value))));
}

// ============================================================
// Admin Form Helpers — Componenti UI riutilizzabili
// ============================================================

function adminInput(string $name, string $label, ?array $sel, string $type = 'text', bool $full = false, string $step = '', bool $required = false): string {
    $value = htmlspecialchars($sel[$name] ?? '');
    $cls = $full ? 'col-span-2' : '';
    $stepAttr = $step ? " step=\"$step\"" : ($type === 'number' ? ' step="any"' : '');
    $requiredAttr = $required ? ' required' : '';
    $requiredMark = $required ? '<span class="text-red-400 ml-1">*</span>' : '';
    return "<div class=\"$cls\">
        <label class=\"block text-xs text-slate-400 mb-1\">$label$requiredMark</label>
        <input type=\"$type\" name=\"$name\" value=\"$value\"$stepAttr$requiredAttr
          class=\"w-full bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600 focus:outline-none focus:border-emerald-500\">
    </div>";
}

function adminTextarea(string $name, string $label, ?array $sel, int $rows = 3, string $help = '', bool $required = false): string {
    $value = htmlspecialchars($sel[$name] ?? '');
    $helpHtml = $help ? "<p class=\"text-xs text-slate-500 mt-1\">$help</p>" : '';
    $requiredAttr = $required ? ' required' : '';
    $requiredMark = $required ? '<span class="text-red-400 ml-1">*</span>' : '';
    return "<div>
        <label class=\"block text-xs text-slate-400 mb-1\">$label$requiredMark</label>
        <textarea name=\"$name\" rows=\"$rows\"$requiredAttr
          class=\"w-full bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600 focus:outline-none focus:border-emerald-500\">$value</textarea>
        $helpHtml
    </div>";
}

function adminSelect(string $name, string $label, ?array $sel, array $options): string {
    $current = $sel[$name] ?? '';
    $html = "<div>
        <label class=\"block text-xs text-slate-400 mb-1\">$label</label>
        <select name=\"$name\" class=\"w-full bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600 focus:outline-none focus:border-emerald-500\">";
    foreach ($options as $val => $lbl) {
        if (is_int($val)) { $val = $lbl; }
        $selected = $current === $val ? ' selected' : '';
        $html .= "<option value=\"" . htmlspecialchars($val) . "\"$selected>" . htmlspecialchars($lbl) . "</option>";
    }
    $html .= "</select></div>";
    return $html;
}

function adminCheckbox(string $name, string $label, ?array $sel): string {
    $checked = !empty($sel[$name]) ? ' checked' : '';
    return "<label class=\"flex items-center gap-2 text-slate-300 text-sm\">
        <input type=\"checkbox\" name=\"$name\"$checked class=\"rounded\"> $label
    </label>";
}

function adminCoverImage(?array $sel): string {
    $html = '<div>
        <label class="block text-xs text-slate-400 mb-1">Immagine di copertina</label>';
    if (!empty($sel['cover_image'])) {
        $src = htmlspecialchars($sel['cover_image']);
        $html .= "<div class=\"mb-2\"><img src=\"$src\" alt=\"Cover\" class=\"h-32 rounded-lg object-cover\"></div>";
    }
    $html .= '<input type="file" name="cover_image" accept="image/*"
        class="w-full bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600 file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:bg-emerald-600 file:text-white file:text-xs file:cursor-pointer">
    </div>';
    return $html;
}

function adminImageGallery(string $inputName, array $images, string $label = 'Galleria immagini'): string {
    $html = "<div class=\"col-span-2\">
        <label class=\"block text-xs text-slate-400 mb-2\">$label</label>
        <div class=\"grid grid-cols-4 gap-2 mb-3\" id=\"gallery-preview\">";
    foreach ($images as $i => $img) {
        $src = htmlspecialchars($img['src'] ?? '');
        $alt = htmlspecialchars($img['alt'] ?? '');
        $html .= "<div class=\"relative group\">
            <img src=\"$src\" alt=\"$alt\" class=\"h-24 w-full object-cover rounded-lg\">
            <input type=\"hidden\" name=\"existing_images_src[]\" value=\"$src\">
            <input type=\"text\" name=\"existing_images_alt[]\" value=\"$alt\" placeholder=\"Alt text\" class=\"w-full mt-1 bg-slate-600 text-white rounded px-2 py-1 text-xs border border-slate-500\">
            <label class=\"absolute top-1 right-1 bg-red-600 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs cursor-pointer opacity-0 group-hover:opacity-100 transition-opacity\">
                <input type=\"checkbox\" name=\"remove_images[]\" value=\"$i\" class=\"hidden\"> &times;
            </label>
        </div>";
    }
    $html .= "</div>
        <input type=\"file\" name=\"{$inputName}[]\" multiple accept=\"image/*\"
            class=\"w-full bg-slate-700 text-white rounded-lg px-3 py-2 text-sm border border-slate-600 file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:bg-emerald-600 file:text-white file:text-xs file:cursor-pointer\">
        <p class=\"text-xs text-slate-500 mt-1\">Puoi selezionare più immagini. Le nuove si aggiungono a quelle esistenti.</p>
    </div>";
    return $html;
}

function adminMsg(string $msg): string {
    if (!$msg) return '';
    $cls = str_starts_with($msg, '✅') ? 'bg-emerald-900/40 border border-emerald-600 text-emerald-300' : 'bg-red-900/40 border border-red-600 text-red-300';
    return "<div class=\"mb-4 px-4 py-3 rounded-lg text-sm $cls\">" . htmlspecialchars($msg) . "</div>";
}

// Processa gallery images dal form POST
function processGalleryFromPost(PDO $db, string $entityType, string $entityId, string $inputName = 'new_images'): void {
    $images = [];
    $removeIndexes = array_map('intval', $_POST['remove_images'] ?? []);

    // Mantieni immagini esistenti (non rimosse)
    $existingSrc = $_POST['existing_images_src'] ?? [];
    $existingAlt = $_POST['existing_images_alt'] ?? [];
    foreach ($existingSrc as $i => $src) {
        if (in_array($i, $removeIndexes)) continue;
        $images[] = ['src' => $src, 'alt' => $existingAlt[$i] ?? ''];
    }

    // Aggiungi nuove immagini uploadate
    $newPaths = handleMultipleImageUpload($inputName, $entityType, $entityId);
    foreach ($newPaths as $path) {
        $images[] = ['src' => $path, 'alt' => ''];
    }

    saveEntityImages($db, $entityType, $entityId, $images);
}
